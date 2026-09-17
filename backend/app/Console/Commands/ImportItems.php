<?php

namespace App\Console\Commands;

use App\Enums\GemColor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class ImportItems extends Command
{
    /**
     * Referência default pinada pra manter o import reproduzível — atualize
     * manualmente se quiser dados mais recentes do AzerothCore-wotlk.
     */
    private const DEFAULT_REF = '2fb90e3b1f6d3cea2a552f12a69aa5e24d195061';

    private const REPO_RAW_BASE = 'https://raw.githubusercontent.com/azerothcore/azerothcore-wotlk';

    /**
     * Mapeamento item_id -> nome do ícone (ex.: "inv_sword_04"). O
     * AzerothCore não redistribui esse dado (vem do cliente do jogo, ver
     * método resolveIcons()); o nexus-devs/wow-classic-items (MIT) publica
     * um JSON já pronto com essa relação, então usamos o dele em vez de
     * raspar o Wowhead nós mesmos.
     */
    private const ICONS_DEFAULT_REF = '8339771805564c6de7850ff9428be8e841f69622';

    private const ICONS_REPO_RAW_BASE = 'https://raw.githubusercontent.com/nexus-devs/wow-classic-items';

    /**
     * @var string
     */
    protected $signature = 'items:import
        {--ref='.self::DEFAULT_REF.' : commit/branch/tag do azerothcore-wotlk}
        {--icons-ref='.self::ICONS_DEFAULT_REF.' : commit/branch/tag do nexus-devs/wow-classic-items}';

    /**
     * @var string
     */
    protected $description = 'Importa item_template do AzerothCore-wotlk (GPL-2.0, github.com/azerothcore/azerothcore-wotlk) pra tabela items';

    public function handle(): int
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('items:import só funciona com MySQL/MariaDB — o dump é MySQL. Conexão atual: '.DB::connection()->getDriverName());

            return self::FAILURE;
        }

        $ref = (string) $this->option('ref');
        $optionsFile = $this->writeMysqlOptionsFile();

        try {
            $this->info('Baixando item_template.sql...');
            $this->loadDump($optionsFile, $ref, 'item_template.sql');

            $this->info('Transformando pra tabela items...');
            $count = $this->transform();

            $this->info('Baixando mapeamento de ícones...');
            $iconCount = $this->resolveIcons((string) $this->option('icons-ref'));

            $this->info('Resolvendo cor de gemas a partir da descrição...');
            $gemCount = $this->resolveGems();

            $this->info("Pronto: {$count} itens importados, {$iconCount} ícones resolvidos, {$gemCount} gemas identificadas.");
        } finally {
            DB::unprepared('DROP TABLE IF EXISTS `item_template`');
            File::delete($optionsFile);
        }

        return self::SUCCESS;
    }

    private function loadDump(string $optionsFile, string $ref, string $file): void
    {
        $url = self::REPO_RAW_BASE."/{$ref}/data/sql/base/db_world/{$file}";

        $response = Http::timeout(120)->get($url);
        $response->throw();

        $config = $this->connectionConfig();

        $result = Process::timeout(300)
            ->input($response->body())
            ->run(['mysql', '--defaults-extra-file='.$optionsFile, $config['database']]);

        if (! $result->successful()) {
            throw new RuntimeException("Falha ao carregar {$file}: ".$result->errorOutput());
        }
    }

    private function connectionConfig(): array
    {
        return config('database.connections.'.config('database.default'));
    }

    private function writeMysqlOptionsFile(): string
    {
        $config = $this->connectionConfig();

        $path = tempnam(sys_get_temp_dir(), 'armory-mysql-');

        File::put($path, implode("\n", [
            '[client]',
            'host='.$config['host'],
            'port='.$config['port'],
            'user='.$config['username'],
            'password='.$config['password'],
        ]));

        chmod($path, 0600);

        return $path;
    }

    /**
     * Upsert por item_id em vez de apagar e reinserir: uma vez que algum
     * personagem tem algo equipado, character_items.item_id referencia o id
     * (surrogate) da linha em items, e a FK é RESTRICT — um DELETE FROM
     * items quebraria o comando (e reimportar precisa continuar funcionando
     * depois que a base já está em uso de verdade).
     */
    private function transform(): int
    {
        return DB::transaction(function () {
            DB::statement('
                INSERT INTO items (
                    item_id, name, icon, slot, quality, item_level,
                    socket_color_1, socket_color_2, socket_color_3,
                    created_at, updated_at
                )
                SELECT
                    t.entry,
                    t.name,
                    NULL,
                    t.InventoryType,
                    t.Quality,
                    t.ItemLevel,
                    t.socketColor_1,
                    t.socketColor_2,
                    t.socketColor_3,
                    NOW(),
                    NOW()
                FROM item_template t
                WHERE t.name != \'\'
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    slot = VALUES(slot),
                    quality = VALUES(quality),
                    item_level = VALUES(item_level),
                    socket_color_1 = VALUES(socket_color_1),
                    socket_color_2 = VALUES(socket_color_2),
                    socket_color_3 = VALUES(socket_color_3),
                    updated_at = VALUES(updated_at)
            ');

            return DB::table('items')->count();
        });
    }

    /**
     * Cor da gema não vem pronta em nenhum dump SQL do AzerothCore — o
     * `gemproperties_dbc.sql` do repositório existe só de schema, sem
     * dados (essa info normalmente vem do DBC do cliente, não do banco do
     * servidor). Mas o próprio `item_template.description` da gema já diz
     * em texto o que ela aceita (ex.: "Matches a Red Socket.", "Only fits
     * in a meta gem slot."), então derivamos o bitmask dali em vez de
     * depender de mais uma fonte externa.
     */
    private function resolveGems(): int
    {
        $gems = DB::table('item_template')
            ->where('GemProperties', '!=', 0)
            ->where('description', '!=', '')
            ->select('entry', 'description')
            ->get();

        $updates = [];

        foreach ($gems as $gem) {
            $color = $this->parseGemColor($gem->description);

            if ($color !== null) {
                $updates[$gem->entry] = $color;
            }
        }

        foreach (array_chunk($updates, 500, true) as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $itemId => $color) {
                    DB::table('items')->where('item_id', $itemId)->update(['gem_color' => $color]);
                }
            });
        }

        return count($updates);
    }

    /**
     * @return int|null Bitmask de GemColor, ou null se a descrição não bate
     *                  com nenhum padrão reconhecido (socket de profissão
     *                  tipo "tonk Overdrive", item de montaria, etc. —
     *                  esses ficam sem gem_color, não aparecem na busca de
     *                  gema do boneco).
     */
    private function parseGemColor(string $description): ?int
    {
        if (str_contains($description, 'meta gem slot')) {
            return GemColor::Meta->value;
        }

        if (str_contains($description, 'any socket') || str_contains($description, 'any Socket')) {
            return GemColor::Red->value | GemColor::Yellow->value | GemColor::Blue->value;
        }

        $mask = 0;
        $mask |= str_contains($description, 'Red') ? GemColor::Red->value : 0;
        $mask |= str_contains($description, 'Yellow') ? GemColor::Yellow->value : 0;
        $mask |= str_contains($description, 'Blue') ? GemColor::Blue->value : 0;

        return $mask !== 0 ? $mask : null;
    }

    /**
     * Baixa o data.json do wow-classic-items (item_id -> nome do ícone) e
     * atualiza a coluna icon via join numa tabela temporária — bem mais
     * rápido que um UPDATE por item.
     */
    private function resolveIcons(string $ref): int
    {
        $url = self::ICONS_REPO_RAW_BASE."/{$ref}/data/json/data.json";

        $response = Http::timeout(120)->get($url);
        $response->throw();

        $icons = collect($response->json())
            ->filter(fn (mixed $entry) => is_array($entry) && ! empty($entry['itemId']) && ! empty($entry['icon']))
            ->map(fn (array $entry) => ['item_id' => $entry['itemId'], 'icon' => $entry['icon']])
            ->unique('item_id');

        DB::unprepared('CREATE TEMPORARY TABLE item_icons (item_id INT UNSIGNED PRIMARY KEY, icon VARCHAR(255) NOT NULL)');

        try {
            $icons->chunk(1000)->each(fn ($chunk) => DB::table('item_icons')->insert($chunk->all()));

            DB::update('
                UPDATE items
                INNER JOIN item_icons ON item_icons.item_id = items.item_id
                SET items.icon = item_icons.icon
            ');

            // Conta quantos casaram, não quantos o UPDATE de fato mudou —
            // reimportar sem nada novo deixa o valor igual ao que já tinha,
            // e o MySQL só reporta linha "changed", não "matched".
            return DB::table('items')
                ->join('item_icons', 'item_icons.item_id', '=', 'items.item_id')
                ->count();
        } finally {
            DB::unprepared('DROP TEMPORARY TABLE IF EXISTS item_icons');
        }
    }
}

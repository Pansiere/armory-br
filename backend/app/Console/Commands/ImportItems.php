<?php

namespace App\Console\Commands;

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

            $this->info("Pronto: {$count} itens importados, {$iconCount} ícones resolvidos.");
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

        $config = config('database.connections.'.config('database.default'));

        $result = Process::timeout(300)
            ->input($response->body())
            ->run(['mysql', '--defaults-extra-file='.$optionsFile, $config['database']]);

        if (! $result->successful()) {
            throw new RuntimeException("Falha ao carregar {$file}: ".$result->errorOutput());
        }
    }

    private function writeMysqlOptionsFile(): string
    {
        $config = config('database.connections.'.config('database.default'));

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

    private function transform(): int
    {
        return DB::transaction(function () {
            DB::statement('DELETE FROM items');

            DB::statement('
                INSERT INTO items (item_id, name, icon, slot, quality, item_level, created_at, updated_at)
                SELECT
                    t.entry,
                    t.name,
                    NULL,
                    t.InventoryType,
                    t.Quality,
                    t.ItemLevel,
                    NOW(),
                    NOW()
                FROM item_template t
                WHERE t.name != \'\'
            ');

            return DB::table('items')->count();
        });
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

            return DB::update('
                UPDATE items
                INNER JOIN item_icons ON item_icons.item_id = items.item_id
                SET items.icon = item_icons.icon
            ');
        } finally {
            DB::unprepared('DROP TEMPORARY TABLE IF EXISTS item_icons');
        }
    }
}

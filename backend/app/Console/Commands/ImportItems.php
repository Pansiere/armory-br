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
     * @var string
     */
    protected $signature = 'items:import {--ref='.self::DEFAULT_REF.' : commit/branch/tag do azerothcore-wotlk}';

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

            $this->info("Pronto: {$count} itens importados.");
            $this->warn('Ícone não vem nesse dump (é dado extraído do cliente do jogo, o AzerothCore não redistribui isso no git) — fica NULL por enquanto.');
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
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A migration anterior (finalize_character_spec_columns) tentou dropar
     * a unique antiga (character_id, slot) antes de dropar a coluna
     * character_id. Só que o MySQL, ao dropar uma coluna que faz parte de
     * um índice composto, não apaga o índice — ele só encolhe o índice pra
     * sobrar as colunas restantes, MANTENDO o nome original. Resultado: em
     * produção sobrou um índice único chamado
     * `character_items_character_id_slot_unique` indexando só `slot`
     * sozinho — impedindo qualquer dois personagens (de usuários
     * diferentes, inclusive) de terem algo no mesmo slot ao mesmo tempo, o
     * que quebra o app pra qualquer usuário além do primeiro. `dropUnique`
     * aqui é hasIndex-guarded porque um banco criado do zero (fresh
     * migrate) nunca passa por esse estado — só quem já rodou a migration
     * antiga antes desse fix precisa dele.
     */
    public function up(): void
    {
        if ($this->indexExists('character_items', 'character_items_character_id_slot_unique')) {
            Schema::table('character_items', function ($table) {
                $table->dropUnique('character_items_character_id_slot_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sem-op — reintroduzir um índice único errado (que só serviu por
        // acidente de como o MySQL trata DROP COLUMN em índice composto)
        // não é algo que faça sentido reverter.
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

        return $result !== [];
    }
};

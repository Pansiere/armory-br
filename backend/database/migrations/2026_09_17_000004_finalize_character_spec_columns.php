<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MODIFY direto em vez de Schema::change() — esse projeto não tem
        // doctrine/dbal instalado (Laravel 13 não exige mais pra maioria dos
        // casos, mas character_spec_id já tem FK, e SQL cru evita qualquer
        // dúvida sobre suporte). Nesse ponto já rodou o backfill da migration
        // anterior, então nenhuma linha deveria violar o NOT NULL.
        //
        // SQLite (só usado em teste/dev, nunca em produção — README) não
        // entende MODIFY e não tem um jeito nativo simples de forçar NOT
        // NULL numa coluna existente; a constraint em si só importa pra
        // produção (MySQL), a validação da aplicação já cobre o teste.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE character_items MODIFY character_spec_id BIGINT UNSIGNED NOT NULL');
        }

        // Passos separados de propósito: a unique antiga (character_id,
        // slot) dá suporte à FK de character_id — MySQL recusa dropar a
        // unique enquanto a FK existir ("needed in a foreign key
        // constraint"). Solta a FK primeiro.
        Schema::table('character_items', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
        });

        // A ordem entre dropUnique e dropColumn muda por banco — os dois
        // discordam de quem vem primeiro:
        //
        // - SQLite recusa dropar a coluna enquanto uma unique ainda
        //   referenciar ela ("error in index ... after drop column"), então
        //   a unique tem que sumir ANTES da coluna.
        // - MySQL, ao contrário: dropar uma coluna que faz parte de um
        //   índice composto não apaga o índice — só encolhe pras colunas
        //   que sobraram, mantendo o NOME original (aqui sobraria um índice
        //   único só em `slot`, sozinho, ainda chamado
        //   character_items_character_id_slot_unique — trava qualquer dois
        //   personagens de terem algo no mesmo slot ao mesmo tempo). Por
        //   isso no MySQL a coluna tem que sumir ANTES, pra depois dropar
        //   por nome o que sobrou do índice.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('character_items', function (Blueprint $table) {
                $table->dropColumn('character_id');
            });

            Schema::table('character_items', function (Blueprint $table) {
                $table->dropUnique('character_items_character_id_slot_unique');
                $table->unique(['character_spec_id', 'slot']);
            });
        } else {
            Schema::table('character_items', function (Blueprint $table) {
                $table->dropUnique(['character_id', 'slot']);
                $table->dropColumn('character_id');
                $table->unique(['character_spec_id', 'slot']);
            });
        }

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('spec');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->string('spec')->nullable();
        });

        Schema::table('character_items', function (Blueprint $table) {
            $table->dropUnique(['character_spec_id', 'slot']);

            // Volta a coluna, mas não repopula os valores (derivaria de
            // character_spec_id -> character_specs.character_id) — reverter
            // essa migration é operação de emergência, não fluxo normal.
            $table->foreignId('character_id')->nullable()->after('id')->constrained();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE character_items MODIFY character_spec_id BIGINT UNSIGNED NULL');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // hasColumn de propósito: a unique antiga (character_id, slot) serve
        // de suporte pra FK de character_id, então não dá pra derrubá-la
        // ainda (MySQL recusa "Cannot drop index ... needed in a foreign key
        // constraint") — só sai junto com a FK, na migration que finaliza
        // (depois do backfill). Por isso essa migration só adiciona a coluna.
        if (! Schema::hasColumn('character_items', 'character_spec_id')) {
            Schema::table('character_items', function (Blueprint $table) {
                $table->foreignId('character_spec_id')->nullable()->after('character_id')
                    ->constrained()->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('character_spec_id');
        });
    }
};

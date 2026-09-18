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
        Schema::table('items', function (Blueprint $table) {
            // Preenchido sob demanda (não no items:import) — o Wowhead só
            // devolve isso um item por vez, e a tabela tem dezenas de
            // milhares de linhas. Ver App\Support\WowheadTooltipResolver.
            $table->text('tooltip_html')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('tooltip_html');
        });
    }
};

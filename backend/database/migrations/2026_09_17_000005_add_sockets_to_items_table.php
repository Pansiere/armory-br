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
            // Bitmask (1=meta, 2=vermelho, 4=amarelo, 8=azul — conferido
            // contra os valores reais do item_template do AzerothCore, não
            // chutado; 14 = vermelho+amarelo+azul, ou seja "prismático").
            // 0 = sem socket ali. Só faz sentido em itens equipáveis.
            $table->unsignedTinyInteger('socket_color_1')->default(0)->after('item_level');
            $table->unsignedTinyInteger('socket_color_2')->default(0)->after('socket_color_1');
            $table->unsignedTinyInteger('socket_color_3')->default(0)->after('socket_color_2');

            // Mesmo bitmask, mas do lado da gema: quais cores de socket ela
            // aceita (derivado do texto de description do item_template —
            // seção da ImportItems::resolveGems()). Null = não é gema.
            $table->unsignedTinyInteger('gem_color')->nullable()->after('socket_color_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['socket_color_1', 'socket_color_2', 'socket_color_3', 'gem_color']);
        });
    }
};

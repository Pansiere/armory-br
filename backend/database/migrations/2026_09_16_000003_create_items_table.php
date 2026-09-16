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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_id')->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedTinyInteger('slot');
            $table->unsignedTinyInteger('quality');
            $table->unsignedSmallInteger('item_level')->default(0);
            $table->timestamps();

            $table->index('name');
        });

        // Índice full-text pra busca instantânea (seção 8) — só existe em
        // MySQL/MariaDB, o dump de itens é MySQL mesmo.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('items', function (Blueprint $table) {
                $table->fullText('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

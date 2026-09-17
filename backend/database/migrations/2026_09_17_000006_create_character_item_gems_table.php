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
        Schema::create('character_item_gems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('socket_position');
            $table->foreignId('item_id')->constrained();
            $table->timestamps();

            $table->unique(['character_item_id', 'socket_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_item_gems');
    }
};

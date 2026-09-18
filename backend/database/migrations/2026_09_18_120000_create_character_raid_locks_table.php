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
        Schema::create('character_raid_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('raid');
            $table->unsignedTinyInteger('size');
            $table->boolean('heroic')->default(false);
            $table->timestamp('locked_at');
            $table->timestamps();

            $table->unique(['character_id', 'raid', 'size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_raid_locks');
    }
};

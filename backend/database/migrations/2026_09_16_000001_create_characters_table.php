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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('faction');
            $table->string('class');
            $table->string('spec')->nullable();
            $table->string('race')->nullable();
            $table->unsignedTinyInteger('level')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'faction', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};

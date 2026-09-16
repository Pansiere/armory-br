<?php

namespace Database\Factories;

use App\Enums\Profession;
use App\Models\Character;
use App\Models\CharacterProfession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterProfession>
 */
class CharacterProfessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'name' => fake()->randomElement(Profession::cases()),
            'skill_level' => fake()->numberBetween(1, Profession::MAX_SKILL_LEVEL),
        ];
    }
}

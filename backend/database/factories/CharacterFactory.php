<?php

namespace Database\Factories;

use App\Enums\CharacterClass;
use App\Enums\Faction;
use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->unique()->userName()),
            'faction' => fake()->randomElement(Faction::cases()),
            'class' => fake()->randomElement(CharacterClass::cases()),
            'spec' => null,
            'position' => 0,
            'is_public' => false,
        ];
    }
}

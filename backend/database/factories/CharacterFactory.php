<?php

namespace Database\Factories;

use App\Enums\CharacterClass;
use App\Enums\Faction;
use App\Enums\Spec;
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
            'position' => 0,
            'is_public' => false,
        ];
    }

    /**
     * Spec primária é obrigatória (seção sobre dual spec) — a factory cria
     * uma condizente com a classe sorteada, senão qualquer teste que
     * dependa de equipamento (que agora pertence a uma spec) quebraria.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Character $character) {
            $character->specs()->create([
                'spec' => Spec::forClass($character->class)[0],
                'position' => 1,
            ]);
        });
    }
}

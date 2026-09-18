<?php

use App\Enums\Faction;
use App\Enums\Profession;
use App\Models\Character;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('tem ícone pra todas as profissões (as 14 do 3.3.5)', function () {
    foreach (Profession::cases() as $profession) {
        expect($profession->icon())->toBeString()->not->toBeEmpty()
            ->and($profession->iconUrl())->toStartWith('https://wow.zamimg.com/images/wow/icons/');
    }
});

it('manda o ícone da profissão do personagem pro dashboard', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['faction' => Faction::Horde]);
    $character->professions()->create(['name' => Profession::Cooking->value, 'skill_level' => 300]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('horde.0.professions.0.icon_url', Profession::Cooking->iconUrl())
        );
});

<?php

use App\Enums\CharacterClass;
use App\Enums\Race;
use App\Models\Character;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('tem ícone pras 10 classes e pras 10 raças do 3.3.5', function () {
    foreach (CharacterClass::cases() as $class) {
        expect($class->iconUrl())->toStartWith('https://wow.zamimg.com/images/wow/icons/');
    }

    foreach (Race::cases() as $race) {
        expect($race->iconUrl())->toStartWith('https://wow.zamimg.com/images/wow/icons/');
    }
});

it('manda o ícone de classe e raça do personagem pro dashboard', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create([
        'class' => CharacterClass::Warrior,
        'race' => Race::Orc,
        'faction' => 'horde',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->where('horde.0.class_icon_url', CharacterClass::Warrior->iconUrl())
            ->where('horde.0.race_icon_url', Race::Orc->iconUrl())
        );
});

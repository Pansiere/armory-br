<?php

use App\Models\Character;
use App\Models\User;

it('impede um usuário de ver, editar ou apagar personagem de outro', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $character = Character::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->get("/characters/{$character->id}/edit")
        ->assertForbidden();

    $this->actingAs($intruder)
        ->put("/characters/{$character->id}", [
            'name' => 'Hackeado',
            'faction' => $character->faction->value,
            'class' => $character->class->value,
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete("/characters/{$character->id}")
        ->assertForbidden();

    expect($character->fresh())
        ->name->not->toBe('Hackeado')
        ->and(Character::find($character->id))->not->toBeNull();
});

it('rejeita reorder que tente mexer em personagem de outro usuário', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $mine = Character::factory()->for($user)->create(['faction' => 'alliance', 'position' => 0]);
    $notMine = Character::factory()->for($stranger)->create(['faction' => 'alliance', 'position' => 0]);

    $this->actingAs($user)
        ->patch('/characters/reorder', [
            'alliance' => [$mine->id, $notMine->id],
            'horde' => [],
        ])
        ->assertForbidden();

    expect($notMine->fresh())
        ->user_id->toBe($stranger->id)
        ->position->toBe(0);
});

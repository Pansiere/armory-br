<?php

use App\Models\Character;
use App\Models\CharacterProfession;
use App\Models\User;

it('apaga o usuário e todos os seus dados ao excluir a conta', function () {
    $user = User::factory()->create(['password' => 'senha-forte']);
    $character = Character::factory()->for($user)->create();
    $profession = CharacterProfession::factory()->for($character)->create();

    $this->actingAs($user)
        ->delete('/account', ['password' => 'senha-forte'])
        ->assertRedirect('/');

    expect(User::find($user->id))->toBeNull()
        ->and(Character::find($character->id))->toBeNull()
        ->and(CharacterProfession::find($profession->id))->toBeNull();

    $this->assertGuest();
});

it('não apaga a conta se a senha de confirmação estiver errada', function () {
    $user = User::factory()->create(['password' => 'senha-forte']);

    $this->actingAs($user)
        ->delete('/account', ['password' => 'senha-errada'])
        ->assertSessionHasErrors('password');

    expect(User::find($user->id))->not->toBeNull();
});

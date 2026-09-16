<?php

use App\Enums\Faction;
use App\Models\Character;
use App\Models\User;

it('persiste a nova ordem e facção após o drag-and-drop', function () {
    $user = User::factory()->create();

    $a = Character::factory()->for($user)->create(['faction' => 'alliance', 'position' => 0]);
    $b = Character::factory()->for($user)->create(['faction' => 'alliance', 'position' => 1]);
    $c = Character::factory()->for($user)->create(['faction' => 'horde', 'position' => 0]);

    // B é arrastado da Aliança para a Horda, ficando à frente de C.
    $this->actingAs($user)
        ->patch('/characters/reorder', [
            'alliance' => [$a->id],
            'horde' => [$b->id, $c->id],
        ])
        ->assertRedirect();

    expect($a->fresh())->faction->toBe(Faction::Alliance)->position->toBe(0);
    expect($b->fresh())->faction->toBe(Faction::Horde)->position->toBe(0);
    expect($c->fresh())->faction->toBe(Faction::Horde)->position->toBe(1);
});

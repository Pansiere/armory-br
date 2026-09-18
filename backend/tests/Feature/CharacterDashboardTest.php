<?php

use App\Models\Character;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('manda equipmentSlots pro dashboard, pro boneco somente-leitura do card (issue #20)', function () {
    $user = User::factory()->create();
    Character::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('equipmentSlots', 19)
            ->where('equipmentSlots.0.value', 'head')
        );
});

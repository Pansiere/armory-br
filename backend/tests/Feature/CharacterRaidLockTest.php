<?php

use App\Models\Character;
use App\Models\User;
use App\Support\RaidResetSchedule;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('marca CD de uma raid pro personagem', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    $this->actingAs($user)
        ->put("/characters/{$character->id}/raid-locks/icc/25", ['heroic' => true])
        ->assertRedirect();

    $lock = $character->raidLocks()->where('raid', 'icc')->where('size', 25)->first();

    expect($lock)->not->toBeNull();
    expect($lock->heroic)->toBeTrue();
});

it('troca de normal pra heroico marcando de novo (mesmo lockout)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    $this->actingAs($user)->put("/characters/{$character->id}/raid-locks/icc/25", ['heroic' => false]);
    $this->actingAs($user)->put("/characters/{$character->id}/raid-locks/icc/25", ['heroic' => true]);

    expect($character->raidLocks()->where('raid', 'icc')->where('size', 25)->count())->toBe(1);
    expect($character->raidLocks()->where('raid', 'icc')->where('size', 25)->first()->heroic)->toBeTrue();
});

it('desmarca CD de uma raid', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $character->raidLocks()->create(['raid' => 'icc', 'size' => 25, 'heroic' => true, 'locked_at' => now()]);

    $this->actingAs($user)
        ->delete("/characters/{$character->id}/raid-locks/icc/25")
        ->assertRedirect();

    expect($character->raidLocks()->count())->toBe(0);
});

it('rejeita raid ou tamanho inválido', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    $this->actingAs($user)
        ->put("/characters/{$character->id}/raid-locks/naxxramas/25", ['heroic' => false])
        ->assertNotFound();

    $this->actingAs($user)
        ->put("/characters/{$character->id}/raid-locks/icc/40", ['heroic' => false])
        ->assertNotFound();
});

it('não deixa mexer no CD de personagem de outro usuário', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $character = Character::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->put("/characters/{$character->id}/raid-locks/icc/25", ['heroic' => false])
        ->assertForbidden();

    expect($character->raidLocks()->count())->toBe(0);
});

it('só mostra no dashboard os CDs ainda válidos (antes do próximo reset)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['faction' => 'alliance']);

    $lastReset = RaidResetSchedule::lastReset();

    $character->raidLocks()->create([
        'raid' => 'icc', 'size' => 25, 'heroic' => false,
        'locked_at' => $lastReset->clone()->addHour(),
    ]);
    $character->raidLocks()->create([
        'raid' => 'toc', 'size' => 10, 'heroic' => true,
        'locked_at' => $lastReset->clone()->subHour(), // antes do reset mais recente: já expirou
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('alliance.0.raid_locks', 1)
            ->where('alliance.0.raid_locks.0.raid', 'icc')
        );
});

it('considera o lock expirado depois do próximo reset semanal', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();

    $lastReset = RaidResetSchedule::lastReset();

    $lock = $character->raidLocks()->create([
        'raid' => 'icc', 'size' => 25, 'heroic' => false,
        'locked_at' => $lastReset->clone()->addHour(),
    ]);

    expect($lock->isActive())->toBeTrue();

    Carbon::setTestNow($lastReset->clone()->addWeek()->addHour());

    expect($lock->fresh()->isActive())->toBeFalse();
});

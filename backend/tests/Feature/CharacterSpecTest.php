<?php

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use App\Models\Character;
use App\Models\CharacterSpec;
use App\Models\Item;
use App\Models\User;

function baseCharacterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Testrand',
        'faction' => 'alliance',
        'class' => 'warrior',
        'specs' => ['warrior_fury'],
        'race' => 'human',
        'level' => 60,
        'professions' => [],
    ], $overrides);
}

it('exige pelo menos uma spec pra criar personagem', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/characters', baseCharacterPayload(['specs' => []]))
        ->assertSessionHasErrors('specs');
});

it('rejeita spec que não é da classe escolhida', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/characters', baseCharacterPayload(['class' => 'warrior', 'specs' => ['mage_frost']]))
        ->assertSessionHasErrors('specs.0');
});

it('cria personagem com dual spec', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/characters', baseCharacterPayload(['specs' => ['warrior_fury', 'warrior_protection']]))
        ->assertRedirect(route('dashboard'));

    $character = Character::where('name', 'Testrand')->firstOrFail();

    expect($character->specs)->toHaveCount(2)
        ->and($character->specs[0]->spec->value)->toBe('warrior_fury')
        ->and($character->specs[0]->position)->toBe(1)
        ->and($character->specs[1]->spec->value)->toBe('warrior_protection')
        ->and($character->specs[1]->position)->toBe(2);
});

it('preserva o equipamento da spec ao salvar o formulário sem trocar de spec', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['class' => 'warrior']);
    $spec = $character->primarySpec();
    $spec->update(['spec' => 'warrior_fury']);

    $item = Item::create(['item_id' => 501, 'name' => 'Machado de teste', 'slot' => InventorySlot::MainHand, 'quality' => ItemQuality::Rare, 'item_level' => 150]);
    $spec->items()->create(['slot' => 'main_hand', 'item_id' => $item->id]);

    // Salva o formulário mudando só o nível — a spec (posição 1) continua
    // warrior_fury, então o equipamento não pode sumir.
    $this->actingAs($user)
        ->put("/characters/{$character->id}", baseCharacterPayload(['specs' => ['warrior_fury'], 'level' => 61]))
        ->assertRedirect(route('dashboard'));

    $spec->refresh();
    expect($spec->items)->toHaveCount(1)
        ->and($spec->items->first()->item_id)->toBe($item->id);
});

it('limpa o equipamento daquela posição quando a spec troca de árvore', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['class' => 'warrior']);
    $spec = $character->primarySpec();
    $spec->update(['spec' => 'warrior_fury']);

    $item = Item::create(['item_id' => 502, 'name' => 'Machado de teste 2', 'slot' => InventorySlot::MainHand, 'quality' => ItemQuality::Rare, 'item_level' => 150]);
    $spec->items()->create(['slot' => 'main_hand', 'item_id' => $item->id]);
    $oldSpecId = $spec->id;

    $this->actingAs($user)
        ->put("/characters/{$character->id}", baseCharacterPayload(['specs' => ['warrior_protection']]))
        ->assertRedirect(route('dashboard'));

    $character->refresh();
    expect($character->specs)->toHaveCount(1)
        ->and($character->specs[0]->spec->value)->toBe('warrior_protection')
        ->and($character->specs[0]->id)->not->toBe($oldSpecId)
        ->and($character->specs[0]->items)->toHaveCount(0);

    // A linha antiga (e o item que apontava pra ela) realmente sumiu via cascade.
    expect(CharacterSpec::find($oldSpecId))->toBeNull();
});

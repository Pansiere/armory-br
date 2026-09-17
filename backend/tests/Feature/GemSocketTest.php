<?php

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use App\Models\Character;
use App\Models\Item;
use App\Models\User;

function equipHelmWithSockets(Character $character, int $redSocket = 2, int $metaSocket = 1): array
{
    $spec = $character->primarySpec();

    $helm = Item::create([
        'item_id' => 90001, 'name' => 'Elmo de teste', 'slot' => InventorySlot::Head,
        'quality' => ItemQuality::Epic, 'item_level' => 200,
        'socket_color_1' => $metaSocket, 'socket_color_2' => $redSocket,
    ]);

    $spec->items()->create(['slot' => 'head', 'item_id' => $helm->id]);

    return [$spec->items()->where('slot', 'head')->first(), $helm];
}

it('encaixa uma gema que combina com a cor do socket', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    [$characterItem] = equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $redGem = Item::create([
        'item_id' => 90002, 'name' => 'Rubi de teste', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 2,
    ]);

    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head/gems/2", ['item_id' => $redGem->id])
        ->assertRedirect();

    expect($characterItem->fresh()->gems)->toHaveCount(1)
        ->and($characterItem->fresh()->gems->first()->item_id)->toBe($redGem->id);
});

it('rejeita gema que não combina com a cor do socket', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $blueGem = Item::create([
        'item_id' => 90003, 'name' => 'Safira de teste', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 8,
    ]);

    // Socket 2 desse elmo é vermelho (2) — uma gema azul (8) não bate.
    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head/gems/2", ['item_id' => $blueGem->id])
        ->assertStatus(422);
});

it('gema meta só encaixa em socket meta', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $metaGem = Item::create([
        'item_id' => 90004, 'name' => 'Gema meta de teste', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Rare, 'item_level' => 1, 'gem_color' => 1,
    ]);

    // Socket 1 é meta — deve funcionar.
    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head/gems/1", ['item_id' => $metaGem->id])
        ->assertRedirect();

    // Socket 2 é vermelho — meta não serve nele.
    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head/gems/2", ['item_id' => $metaGem->id])
        ->assertStatus(422);
});

it('rejeita item que não é gema', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $notAGem = Item::create([
        'item_id' => 90005, 'name' => 'Poção de teste', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Common, 'item_level' => 1,
    ]);

    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head/gems/2", ['item_id' => $notAGem->id])
        ->assertStatus(422);
});

it('remove a gema do socket', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    [$characterItem] = equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $gem = Item::create([
        'item_id' => 90006, 'name' => 'Gema de teste', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 2,
    ]);
    $characterItem->gems()->create(['socket_position' => 2, 'item_id' => $gem->id]);

    $this->actingAs($user)
        ->delete("/characters/{$character->id}/equipment/{$spec}/head/gems/2")
        ->assertRedirect();

    expect($characterItem->fresh()->gems)->toHaveCount(0);
});

it('limpa as gemas antigas quando o item do slot é trocado por outro', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    [$characterItem, $oldHelm] = equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $gem = Item::create([
        'item_id' => 90007, 'name' => 'Gema de teste 2', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 2,
    ]);
    $characterItem->gems()->create(['socket_position' => 2, 'item_id' => $gem->id]);

    $newHelm = Item::create([
        'item_id' => 90008, 'name' => 'Outro elmo', 'slot' => InventorySlot::Head,
        'quality' => ItemQuality::Rare, 'item_level' => 180,
    ]);

    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head", ['item_id' => $newHelm->id])
        ->assertRedirect();

    // A linha de character_items é a mesma (updateOrCreate por slot), mas
    // as gemas do elmo antigo não podem sobreviver na troca.
    expect($characterItem->fresh()->item_id)->toBe($newHelm->id)
        ->and($characterItem->fresh()->gems)->toHaveCount(0);
});

it('preserva as gemas quando o mesmo item é reequipado (sem trocar)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    [$characterItem, $helm] = equipHelmWithSockets($character);
    $spec = $character->primarySpec()->spec->value;

    $gem = Item::create([
        'item_id' => 90009, 'name' => 'Gema de teste 3', 'slot' => InventorySlot::NonEquip,
        'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 2,
    ]);
    $characterItem->gems()->create(['socket_position' => 2, 'item_id' => $gem->id]);

    $this->actingAs($user)
        ->put("/characters/{$character->id}/equipment/{$spec}/head", ['item_id' => $helm->id])
        ->assertRedirect();

    expect($characterItem->fresh()->gems)->toHaveCount(1);
});

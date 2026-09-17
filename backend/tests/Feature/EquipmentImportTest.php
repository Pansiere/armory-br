<?php

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use App\Models\Character;
use App\Models\Item;
use App\Models\User;

it('monta o boneco a partir de um perfil do SimulationCraft, ignorando as linhas que não são de equipamento', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $head = Item::create(['item_id' => 101, 'name' => 'Elmo de teste', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);
    $weapon = Item::create(['item_id' => 102, 'name' => 'Espada de teste', 'slot' => InventorySlot::MainHand, 'quality' => ItemQuality::Rare, 'item_level' => 190]);

    $profile = <<<'TXT'
        level=80
        race=human
        professions=Tailoring=525/Enchanting=525

        head=elmo_de_teste,id=101,gems=
        main_hand=espada_de_teste,id=102,enchant=berserking
        TXT;

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    expect($character->fresh()->items)->toHaveCount(2);
    expect($character->items()->where('slot', 'head')->first()->item_id)->toBe($head->id);
    expect($character->items()->where('slot', 'main_hand')->first()->item_id)->toBe($weapon->id);
});

it('monta o boneco a partir de links de item crus (item:ID)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $trinket = Item::create(['item_id' => 201, 'name' => 'Berloque de teste', 'slot' => InventorySlot::Trinket, 'quality' => ItemQuality::Uncommon, 'item_level' => 100]);

    $pasted = 'Peguei esse |cff0070dditem:201:0:0:0:0:0:0:0|h[Berloque de teste]|h|r ontem.';

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $pasted])
        ->assertRedirect();

    expect($character->fresh()->items)->toHaveCount(1);
    expect($character->items()->first()->item_id)->toBe($trinket->id);
});

it('prioriza o slot explícito do SimC sobre o palpite por tipo quando os dois formatos aparecem juntos', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $ring = Item::create(['item_id' => 301, 'name' => 'Anel de teste', 'slot' => InventorySlot::Finger, 'quality' => ItemQuality::Rare, 'item_level' => 150]);
    $otherRing = Item::create(['item_id' => 302, 'name' => 'Outro anel', 'slot' => InventorySlot::Finger, 'quality' => ItemQuality::Rare, 'item_level' => 150]);

    // finger2 explícito no SimC deve ganhar o slot ring_2 mesmo que o link
    // cru do mesmo item apareça antes no texto.
    $profile = "item:{$ring->item_id}\nfinger2=outro_anel,id={$otherRing->item_id}";

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    expect($character->items()->where('slot', 'ring_2')->first()->item_id)->toBe($otherRing->id);
    expect($character->items()->where('slot', 'ring_1')->first()->item_id)->toBe($ring->id);
});

it('não deixa importar equipamento numa spec que o personagem não tem', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['class' => 'warrior']);

    // A factory só cria a spec primária (warrior_arms, primeira da classe) —
    // warlock_affliction não existe nesse personagem e nem é da classe dele.
    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/warlock_affliction/import", ['text' => 'item:12345'])
        ->assertNotFound();
});

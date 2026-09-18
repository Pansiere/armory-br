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

it('monta o boneco a partir de um export do addon WowSims Exporter (JSON, com slots vazios entre os preenchidos)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $head = Item::create(['item_id' => 401, 'name' => 'Elmo WowSims', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);
    $weapon = Item::create(['item_id' => 402, 'name' => 'Espada WowSims', 'slot' => InventorySlot::MainHand, 'quality' => ItemQuality::Rare, 'item_level' => 190]);

    // LibParse (a lib de JSON embutida no addon) sempre serializa gear.items
    // como array, com `null` explícito em cada posição vazia entre o índice
    // 0 e o maior índice preenchido — nunca como objeto com chaves esparsas.
    // Índice 0 = posição 1 = Head; índice 14 = posição 15 = MainHand.
    $gearItems = array_fill(0, 15, null);
    $gearItems[0] = ['id' => $head->item_id, 'enchant' => 0];
    $gearItems[14] = ['id' => $weapon->item_id, 'enchant' => 3789];

    $export = json_encode([
        'name' => 'Testchar',
        'class' => 'warrior',
        'gear' => ['items' => $gearItems],
    ]);

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $export])
        ->assertRedirect();

    expect($character->fresh()->items)->toHaveCount(2);
    expect($character->items()->where('slot', 'head')->first()->item_id)->toBe($head->id);
    expect($character->items()->where('slot', 'main_hand')->first()->item_id)->toBe($weapon->id);
});

it('monta o boneco a partir de um export do WowSims Exporter (JSON, itens como array sequencial sem buracos)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $head = Item::create(['item_id' => 403, 'name' => 'Elmo WowSims 2', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);
    $neck = Item::create(['item_id' => 404, 'name' => 'Colar WowSims', 'slot' => InventorySlot::Neck, 'quality' => ItemQuality::Rare, 'item_level' => 180]);

    // Array 0-indexado sem buraco nenhum: só acontece quando os slots
    // preenchidos são consecutivos a partir da posição 1 (Head) — índice 0
    // do array = posição 1 do itemLayout = Head, índice 1 = posição 2 = Neck.
    $export = json_encode([
        'gear' => [
            'items' => [
                ['id' => $head->item_id, 'enchant' => 0],
                ['id' => $neck->item_id, 'enchant' => 0],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $export])
        ->assertRedirect();

    expect($character->fresh()->items)->toHaveCount(2);
    expect($character->items()->where('slot', 'head')->first()->item_id)->toBe($head->id);
    expect($character->items()->where('slot', 'neck')->first()->item_id)->toBe($neck->id);
});

it('encaixa as gemas do campo gems= do perfil SimC nos sockets certos, na ordem', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    // Socket 1 meta (1), socket 2 vermelho (2) — mesma convenção do GemSocketTest.
    $head = Item::create([
        'item_id' => 501, 'name' => 'Elmo com socket', 'slot' => InventorySlot::Head,
        'quality' => ItemQuality::Epic, 'item_level' => 200,
        'socket_color_1' => 1, 'socket_color_2' => 2,
    ]);
    $metaGem = Item::create(['item_id' => 502, 'name' => 'Gema meta', 'slot' => InventorySlot::NonEquip, 'quality' => ItemQuality::Rare, 'item_level' => 1, 'gem_color' => 1]);
    $redGem = Item::create(['item_id' => 503, 'name' => 'Gema vermelha', 'slot' => InventorySlot::NonEquip, 'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 2]);

    $profile = "head=elmo_com_socket,id={$head->item_id},gems={$metaGem->item_id}/{$redGem->item_id}";

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    $characterItem = $character->items()->where('slot', 'head')->first();
    expect($characterItem->gems)->toHaveCount(2);
    expect($characterItem->gems->firstWhere('socket_position', 1)->item_id)->toBe($metaGem->id);
    expect($characterItem->gems->firstWhere('socket_position', 2)->item_id)->toBe($redGem->id);
});

it('ignora silenciosamente uma gema do import cuja cor não combina com o socket', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    // Socket único, vermelho (2).
    $head = Item::create([
        'item_id' => 504, 'name' => 'Elmo com socket vermelho', 'slot' => InventorySlot::Head,
        'quality' => ItemQuality::Epic, 'item_level' => 200, 'socket_color_1' => 2,
    ]);
    $blueGem = Item::create(['item_id' => 505, 'name' => 'Gema azul', 'slot' => InventorySlot::NonEquip, 'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 8]);

    $profile = "head=elmo,id={$head->item_id},gems={$blueGem->item_id}";

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    // O item equipa normalmente; só a gema incompatível é que não entra.
    $characterItem = $character->items()->where('slot', 'head')->first();
    expect($characterItem->item_id)->toBe($head->id);
    expect($characterItem->gems)->toHaveCount(0);
});

it('atualiza nome, raça (e a facção implícita) e nível a partir do export', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['name' => 'NomeAntigo', 'faction' => 'horde', 'level' => 1]);
    $spec = $character->primarySpec()->spec->value;

    $profile = <<<'TXT'
        name=NomeNovo
        race=night_elf
        level=80
        TXT;

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    $character->refresh();
    expect($character->name)->toBe('NomeNovo');
    expect($character->race->value)->toBe('night_elf');
    expect($character->level)->toBe(80);
    // Night Elf é Aliança — a facção cadastrada (Horde) deveria ter sido
    // corrigida junto, senão o personagem fica com raça e facção incoerentes.
    expect($character->faction->value)->toBe('alliance');
});

it('sincroniza profissões a partir do export, sem duplicar ao colar de novo', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $profile = "profession=alchemy:450\nprofession=herbalism:375";

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    expect($character->professions)->toHaveCount(2);

    // Recolar o mesmo texto não deve duplicar.
    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => $profile])
        ->assertRedirect();

    expect($character->fresh()->professions)->toHaveCount(2);
});

it('remove profissão largada: reimportar sem ela some com o registro antigo', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create();
    $spec = $character->primarySpec()->spec->value;

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => "profession=mining:450\nprofession=herbalism:450"])
        ->assertRedirect();

    expect($character->professions)->toHaveCount(2);

    // Trocou mining por skinning no jogo — o novo export não lista mining.
    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => "profession=skinning:1\nprofession=herbalism:450"])
        ->assertRedirect();

    $names = $character->fresh()->professions->pluck('name.value')->all();
    expect($names)->toContain('skinning')->toContain('herbalism')->not->toContain('mining');
});

it('não mexe em nome/raça/nível/profissões quando o texto colado não tem essas linhas', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['name' => 'Original', 'level' => 42]);
    $spec = $character->primarySpec()->spec->value;
    $character->professions()->create(['name' => 'mining', 'skill_level' => 300]);

    $head = Item::create(['item_id' => 601, 'name' => 'Elmo', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);

    $this->actingAs($user)
        ->post("/characters/{$character->id}/equipment/{$spec}/import", ['text' => "head=elmo,id={$head->item_id}"])
        ->assertRedirect();

    $character->refresh();
    expect($character->name)->toBe('Original');
    expect($character->level)->toBe(42);
    expect($character->professions)->toHaveCount(1);
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

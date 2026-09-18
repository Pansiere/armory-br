<?php

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use App\Models\Item;
use App\Models\User;

function createCharacterPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Personagem Teste',
        'faction' => 'horde',
        'class' => 'warrior',
        'specs' => ['warrior_arms'],
        'is_public' => false,
    ], $overrides);
}

it('cria o personagem normalmente quando não cola nenhum texto (caminho manual continua igual)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/characters', createCharacterPayload())
        ->assertRedirect(route('dashboard'));

    $character = $user->characters()->firstOrFail();
    expect($character->name)->toBe('Personagem Teste');
});

it('cola o texto já na criação e o personagem nasce com nome, raça, facção, nível, profissão e equipamento', function () {
    $user = User::factory()->create();

    $helm = Item::create(['item_id' => 701, 'name' => 'Elmo de teste', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);

    $text = <<<'TXT'
        name=Personagem Importado
        race=orc
        level=80
        profession=mining:450

        head=elmo_de_teste,id=701
        TXT;

    $response = $this->actingAs($user)->post('/characters', createCharacterPayload([
        'name' => 'Nome do formulário (deve ser sobrescrito pelo texto)',
        'faction' => 'alliance',
        'text' => $text,
    ]));

    $character = $user->characters()->firstOrFail();
    $response->assertRedirect(route('characters.edit', $character));

    expect($character->name)->toBe('Personagem Importado');
    expect($character->race->value)->toBe('orc');
    // Orc é Horda — mesmo o form tendo mandado "alliance", o texto colado
    // (via raça) deve prevalecer, igual já acontece no import da edição.
    expect($character->faction->value)->toBe('horde');
    expect($character->level)->toBe(80);
    expect($character->professions)->toHaveCount(1);
    expect($character->professions->first()->name->value)->toBe('mining');

    $primarySpec = $character->specs()->where('position', 1)->firstOrFail();
    expect($primarySpec->items)->toHaveCount(1);
    expect($primarySpec->items->first()->item_id)->toBe($helm->id);
});

it('cola só equipamento (sem metadado) na criação e mantém nome/raça/nível do formulário', function () {
    $user = User::factory()->create();

    $helm = Item::create(['item_id' => 702, 'name' => 'Elmo 2', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Rare, 'item_level' => 180]);

    $response = $this->actingAs($user)->post('/characters', createCharacterPayload([
        'name' => 'Fica Assim Mesmo',
        'race' => 'orc',
        'level' => 60,
        'text' => "head=elmo_2,id={$helm->item_id}",
    ]));

    $character = $user->characters()->firstOrFail();
    $response->assertRedirect(route('characters.edit', $character));

    expect($character->name)->toBe('Fica Assim Mesmo');
    expect($character->race->value)->toBe('orc');
    expect($character->level)->toBe(60);

    $primarySpec = $character->specs()->where('position', 1)->firstOrFail();
    expect($primarySpec->items)->toHaveCount(1);
});

it('texto colado em branco na criação não é tratado como import (volta pra dashboard igual ao manual)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/characters', createCharacterPayload(['text' => '   ']))
        ->assertRedirect(route('dashboard'));
});

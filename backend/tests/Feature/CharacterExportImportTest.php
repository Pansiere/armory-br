<?php

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use App\Models\Character;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * O endpoint recebe um arquivo de verdade (multipart), não os campos
 * direto no corpo do request — ver a mudança em importJson() que lê e
 * decodifica o arquivo em vez de confiar em `$request->validate()` puro.
 */
function importJsonFile(array $payload): UploadedFile
{
    return UploadedFile::fake()->createWithContent('character.json', json_encode($payload));
}

it('exporta o personagem com o item_id do jogo (não o id interno da tabela items)', function () {
    $user = User::factory()->create();
    $character = Character::factory()->for($user)->create(['level' => 80]);
    $character->professions()->create(['name' => 'mining', 'skill_level' => 450]);
    $spec = $character->primarySpec();

    // item_id do jogo (801) propositalmente diferente do id interno (autoincrement).
    $helm = Item::create(['item_id' => 801, 'name' => 'Elmo Export', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);
    $spec->items()->create(['slot' => 'head', 'item_id' => $helm->id]);

    $response = $this->actingAs($user)->get("/characters/{$character->id}/export");

    $response->assertOk();
    $response->assertHeader('Content-Disposition');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');

    $json = $response->json();
    expect($json['name'])->toBe($character->name);
    expect($json['level'])->toBe(80);
    expect($json['professions'])->toHaveCount(1)
        ->and($json['professions'][0])->toBe(['name' => 'mining', 'skill_level' => 450]);
    expect($json['specs'])->toHaveCount(1);
    expect($json['specs'][0]['equipment'][0])->toMatchArray(['slot' => 'head', 'item_id' => 801]);
});

it('não deixa exportar o personagem de outro usuário', function () {
    $owner = User::factory()->create();
    $character = Character::factory()->for($owner)->create();

    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get("/characters/{$character->id}/export")
        ->assertForbidden();
});

it('importa um JSON completo e recria nome, raça, facção, nível, profissão e equipamento com gema', function () {
    $user = User::factory()->create();

    $helm = Item::create([
        'item_id' => 802, 'name' => 'Elmo Import', 'slot' => InventorySlot::Head,
        'quality' => ItemQuality::Epic, 'item_level' => 200, 'socket_color_1' => 2,
    ]);
    $gem = Item::create(['item_id' => 803, 'name' => 'Gema Import', 'slot' => InventorySlot::NonEquip, 'quality' => ItemQuality::Uncommon, 'item_level' => 1, 'gem_color' => 2]);

    $payload = [
        'name' => 'Personagem Restaurado',
        'faction' => 'horde',
        'class' => 'warrior',
        'race' => 'orc',
        'level' => 80,
        'professions' => [['name' => 'mining', 'skill_level' => 450]],
        'specs' => [
            [
                'spec' => 'warrior_arms',
                'position' => 1,
                'equipment' => [
                    ['slot' => 'head', 'item_id' => 802, 'gems' => [['socket_position' => 1, 'item_id' => 803]]],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)->post('/characters/import-json', ['file' => importJsonFile($payload)]);

    $character = $user->characters()->firstOrFail();
    $response->assertRedirect(route('characters.edit', $character));

    expect($character->name)->toBe('Personagem Restaurado');
    expect($character->race->value)->toBe('orc');
    expect($character->faction->value)->toBe('horde');
    expect($character->level)->toBe(80);
    expect($character->professions)->toHaveCount(1);

    $spec = $character->specs()->where('position', 1)->firstOrFail();
    expect($spec->spec->value)->toBe('warrior_arms');
    expect($spec->items)->toHaveCount(1);

    $characterItem = $spec->items->first();
    expect($characterItem->item_id)->toBe($helm->id);
    expect($characterItem->gems)->toHaveCount(1);
    expect($characterItem->gems->first()->item_id)->toBe($gem->id);
});

it('importa dual spec com o mesmo slot equipado nas duas specs sem falhar a validação', function () {
    $user = User::factory()->create();

    $helm1 = Item::create(['item_id' => 804, 'name' => 'Elmo Spec 1', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);
    $helm2 = Item::create(['item_id' => 805, 'name' => 'Elmo Spec 2', 'slot' => InventorySlot::Head, 'quality' => ItemQuality::Epic, 'item_level' => 200]);

    $payload = [
        'name' => 'Dual Spec Import',
        'faction' => 'horde',
        'class' => 'warrior',
        'specs' => [
            ['spec' => 'warrior_arms', 'position' => 1, 'equipment' => [['slot' => 'head', 'item_id' => 804]]],
            ['spec' => 'warrior_fury', 'position' => 2, 'equipment' => [['slot' => 'head', 'item_id' => 805]]],
        ],
    ];

    $response = $this->actingAs($user)->post('/characters/import-json', ['file' => importJsonFile($payload)]);

    $character = $user->characters()->firstOrFail();
    $response->assertRedirect(route('characters.edit', $character));

    $spec1 = $character->specs()->where('position', 1)->firstOrFail();
    $spec2 = $character->specs()->where('position', 2)->firstOrFail();
    expect($spec1->items->first()->item_id)->toBe($helm1->id);
    expect($spec2->items->first()->item_id)->toBe($helm2->id);
});

it('ignora silenciosamente um item que não existe na base local ao importar', function () {
    $user = User::factory()->create();

    $payload = [
        'name' => 'Item Desconhecido',
        'faction' => 'horde',
        'class' => 'warrior',
        'specs' => [
            ['spec' => 'warrior_arms', 'position' => 1, 'equipment' => [['slot' => 'head', 'item_id' => 999999]]],
        ],
    ];

    $this->actingAs($user)->post('/characters/import-json', ['file' => importJsonFile($payload)])->assertRedirect();

    $character = $user->characters()->firstOrFail();
    expect($character->primarySpec()->items)->toHaveCount(0);
});

it('rejeita import com campo obrigatório faltando', function () {
    $user = User::factory()->create();

    $file = importJsonFile(['faction' => 'horde', 'class' => 'warrior', 'specs' => [['spec' => 'warrior_arms', 'position' => 1]]]);

    $this->actingAs($user)
        ->post('/characters/import-json', ['file' => $file])
        ->assertSessionHasErrors('name');
});

it('rejeita arquivo que não é JSON válido', function () {
    $user = User::factory()->create();

    $file = UploadedFile::fake()->createWithContent('character.json', 'isso não é json{{{');

    $this->actingAs($user)
        ->post('/characters/import-json', ['file' => $file])
        ->assertSessionHasErrors('file');
});

it('faz o ciclo completo: exporta um personagem de verdade e reimporta idêntico', function () {
    $user = User::factory()->create();
    $original = Character::factory()->for($user)->create([
        'name' => 'Original Roundtrip', 'faction' => 'alliance', 'class' => 'mage', 'race' => 'gnome', 'level' => 70,
    ]);
    $original->professions()->create(['name' => 'tailoring', 'skill_level' => 400]);
    $spec = $original->primarySpec();
    $spec->update(['spec' => 'mage_fire']);

    $staff = Item::create(['item_id' => 806, 'name' => 'Cajado Roundtrip', 'slot' => InventorySlot::MainHand, 'quality' => ItemQuality::Rare, 'item_level' => 150]);
    $spec->items()->create(['slot' => 'main_hand', 'item_id' => $staff->id]);

    $exported = $this->actingAs($user)->get("/characters/{$original->id}/export")->json();

    $this->actingAs($user)->post('/characters/import-json', ['file' => importJsonFile($exported)])->assertRedirect();

    $restored = $user->characters()->where('name', 'Original Roundtrip')->latest('id')->firstOrFail();
    expect($restored->id)->not->toBe($original->id);
    expect($restored->faction->value)->toBe('alliance');
    expect($restored->race->value)->toBe('gnome');
    expect($restored->level)->toBe(70);
    expect($restored->professions->first()->name->value)->toBe('tailoring');
    expect($restored->primarySpec()->spec->value)->toBe('mage_fire');
    expect($restored->primarySpec()->items->first()->item_id)->toBe($staff->id);
});

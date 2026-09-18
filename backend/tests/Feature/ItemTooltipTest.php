<?php

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use App\Models\Item;
use Illuminate\Support\Facades\Http;

it('busca a tooltip no wowhead e guarda no item na primeira vez', function () {
    $item = Item::create([
        'item_id' => 54573, 'name' => 'Glowing Twilight Scale',
        'slot' => InventorySlot::Trinket, 'quality' => ItemQuality::Epic, 'item_level' => 271,
    ]);

    Http::fake([
        'nether.wowhead.com/wotlk/tooltip/item/54573*' => Http::response([
            'name' => 'Glowing Twilight Scale',
            'quality' => 4,
            'icon' => 'inv_misc_rubysanctum1',
            'tooltip' => '<b class="q4">Glowing Twilight Scale</b>',
            'spells' => [],
        ]),
    ]);

    $response = $this->getJson("/items/{$item->id}/tooltip");

    $response->assertOk()->assertJson(['tooltip_html' => '<b class="q4">Glowing Twilight Scale</b>']);
    expect($item->fresh()->tooltip_html)->toBe('<b class="q4">Glowing Twilight Scale</b>');
    Http::assertSentCount(1);
});

it('não bate no wowhead de novo se a tooltip já está guardada', function () {
    $item = Item::create([
        'item_id' => 54573, 'name' => 'Glowing Twilight Scale',
        'slot' => InventorySlot::Trinket, 'quality' => ItemQuality::Epic, 'item_level' => 271,
        'tooltip_html' => '<b class="q4">Já em cache</b>',
    ]);

    Http::fake();

    $response = $this->getJson("/items/{$item->id}/tooltip");

    $response->assertOk()->assertJson(['tooltip_html' => '<b class="q4">Já em cache</b>']);
    Http::assertNothingSent();
});

it('devolve tooltip_html nulo sem quebrar se o wowhead falhar', function () {
    $item = Item::create([
        'item_id' => 999999, 'name' => 'Item de teste',
        'slot' => InventorySlot::Trinket, 'quality' => ItemQuality::Common, 'item_level' => 1,
    ]);

    Http::fake([
        'nether.wowhead.com/*' => Http::response(null, 500),
    ]);

    $response = $this->getJson("/items/{$item->id}/tooltip");

    $response->assertOk()->assertJson(['tooltip_html' => null]);
    expect($item->fresh()->tooltip_html)->toBeNull();
});

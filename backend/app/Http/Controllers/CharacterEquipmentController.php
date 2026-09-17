<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CharacterEquipmentController extends Controller
{
    public function update(Request $request, Character $character, string $slot): RedirectResponse
    {
        Gate::authorize('update', $character);

        $equipmentSlot = EquipmentSlot::tryFrom($slot);
        abort_if($equipmentSlot === null, 404);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
        ]);

        $item = Item::findOrFail($validated['item_id']);

        $acceptedSlots = array_map(
            fn ($inventorySlot) => $inventorySlot->value,
            $equipmentSlot->acceptedInventorySlots(),
        );

        abort_unless(in_array($item->slot->value, $acceptedSlots, true), 422, 'Esse item não cabe nesse slot.');

        $character->items()->updateOrCreate(
            ['slot' => $equipmentSlot->value],
            ['item_id' => $item->id],
        );

        return back();
    }

    public function destroy(Character $character, string $slot): RedirectResponse
    {
        Gate::authorize('update', $character);

        $equipmentSlot = EquipmentSlot::tryFrom($slot);
        abort_if($equipmentSlot === null, 404);

        $character->items()->where('slot', $equipmentSlot->value)->delete();

        return back();
    }

    /**
     * Monta o boneco inteiro a partir do texto que addons de WotLK exportam
     * (seção 7.2). Não depende de um formato de addon específico — só
     * procura por links de item (`item:ID`, o jeito universal do WoW
     * representar um item em texto) e encaixa cada um no primeiro slot do
     * boneco que aceita aquele tipo de item. Idempotente: colar de novo só
     * atualiza os mesmos slots, nunca duplica.
     */
    public function import(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
        ]);

        preg_match_all('/item:(\d+)/i', $validated['text'], $matches);
        // Sem array_unique de propósito: um personagem pode ter o mesmo item
        // em dois slots (dois anéis iguais, duas armas iguais na
        // dual-empunhadura) — cada ocorrência do link deve poder preencher
        // seu próprio slot, e firstAvailableFor() já pula slot já usado.
        $itemIds = array_map('intval', $matches[1]);

        if ($itemIds === []) {
            return back()->with('equipmentImport', ['equipped' => 0, 'ignored' => 0]);
        }

        $items = Item::query()->whereIn('item_id', $itemIds)->get()->keyBy('item_id');

        $summary = DB::transaction(function () use ($character, $itemIds, $items) {
            $equipped = 0;
            $ignored = 0;
            $filledSlots = [];

            foreach ($itemIds as $itemId) {
                $item = $items->get($itemId);

                $slot = $item ? EquipmentSlot::firstAvailableFor($item->slot, $filledSlots) : null;

                if ($slot === null) {
                    $ignored++;

                    continue;
                }

                $filledSlots[] = $slot;
                $equipped++;

                $character->items()->updateOrCreate(
                    ['slot' => $slot->value],
                    ['item_id' => $item->id],
                );
            }

            return ['equipped' => $equipped, 'ignored' => $ignored];
        });

        return back()->with('equipmentImport', $summary);
    }
}

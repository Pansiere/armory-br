<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Models\Character;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}

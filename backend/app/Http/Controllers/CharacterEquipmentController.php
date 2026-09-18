<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Enums\GemColor;
use App\Enums\Spec;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterSpec;
use App\Models\Item;
use App\Support\CharacterImportParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CharacterEquipmentController extends Controller
{
    public function update(Request $request, Character $character, string $spec, string $slot): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);

        $equipmentSlot = EquipmentSlot::tryFrom($slot);
        abort_if($equipmentSlot === null, 404);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
        ]);

        $item = Item::findOrFail((int) $validated['item_id']);

        $acceptedSlots = array_map(
            fn ($inventorySlot) => $inventorySlot->value,
            $equipmentSlot->acceptedInventorySlots(),
        );

        abort_unless(in_array($item->slot->value, $acceptedSlots, true), 422, 'Esse item não cabe nesse slot.');

        $characterSpec->equipItem($equipmentSlot, $item);

        return back();
    }

    public function destroy(Character $character, string $spec, string $slot): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);

        $equipmentSlot = EquipmentSlot::tryFrom($slot);
        abort_if($equipmentSlot === null, 404);

        $characterSpec->items()->where('slot', $equipmentSlot->value)->delete();

        return back();
    }

    /**
     * Encaixa uma gema num dos sockets do item equipado nesse slot.
     */
    public function updateGem(Request $request, Character $character, string $spec, string $slot, int $position): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);
        $characterItem = $this->resolveEquippedItem($characterSpec, $slot);

        abort_unless(in_array($position, [1, 2, 3], true), 404);

        $validated = $request->validate([
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
        ]);

        $gem = Item::findOrFail((int) $validated['item_id']);
        abort_unless($gem->isGem(), 422, 'Esse item não é uma gema.');

        $socketColor = $characterItem->item->socketColors()[$position - 1] ?? null;
        abort_if($socketColor === null, 422, 'Esse item não tem esse socket.');
        abort_unless(GemColor::gemFitsSocket($gem->gem_color, $socketColor), 422, 'Essa gema não combina com esse socket.');

        $characterItem->gems()->updateOrCreate(
            ['socket_position' => $position],
            ['item_id' => $gem->id],
        );

        return back();
    }

    public function destroyGem(Character $character, string $spec, string $slot, int $position): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);
        $characterItem = $this->resolveEquippedItem($characterSpec, $slot);

        $characterItem->gems()->where('socket_position', $position)->delete();

        return back();
    }

    /**
     * Acha a spec do personagem pelo valor do enum na URL — 404 se o
     * personagem não tem essa spec (não pode equipar em spec que não é dele).
     */
    private function resolveSpec(Character $character, string $spec): CharacterSpec
    {
        $specEnum = Spec::tryFrom($spec);
        abort_if($specEnum === null, 404);

        return $character->specs()->where('spec', $specEnum->value)->firstOrFail();
    }

    private function resolveEquippedItem(CharacterSpec $characterSpec, string $slot): CharacterItem
    {
        $equipmentSlot = EquipmentSlot::tryFrom($slot);
        abort_if($equipmentSlot === null, 404);

        return $characterSpec->items()->where('slot', $equipmentSlot->value)->firstOrFail();
    }

    /**
     * Monta o boneco inteiro (e atualiza nome/raça/nível/profissões, se o
     * texto trouxer) a partir do texto que addons de WotLK exportam — ver
     * App\Support\CharacterImportParser pros formatos reconhecidos.
     */
    public function import(Request $request, Character $character, string $spec, CharacterImportParser $parser): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
        ]);

        // Metadado antes do equipamento: texto só com metadados (sem
        // nenhum slot reconhecido) ainda deve atualizar nome/raça/nível/
        // profissões — applyEquipment() com nada reconhecido só devolve
        // ['equipped' => 0, 'ignored' => 0] sem quebrar nada.
        $parser->applyMeta($character, $parser->parseMeta($validated['text']));
        $summary = $parser->applyEquipment($characterSpec, $parser->parseEquipment($validated['text']));

        return back()->with('equipmentImport', $summary);
    }
}

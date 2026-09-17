<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Enums\Spec;
use App\Models\Character;
use App\Models\CharacterSpec;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $item = Item::findOrFail($validated['item_id']);

        $acceptedSlots = array_map(
            fn ($inventorySlot) => $inventorySlot->value,
            $equipmentSlot->acceptedInventorySlots(),
        );

        abort_unless(in_array($item->slot->value, $acceptedSlots, true), 422, 'Esse item não cabe nesse slot.');

        $characterSpec->items()->updateOrCreate(
            ['slot' => $equipmentSlot->value],
            ['item_id' => $item->id],
        );

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
     * Acha a spec do personagem pelo valor do enum na URL — 404 se o
     * personagem não tem essa spec (não pode equipar em spec que não é dele).
     */
    private function resolveSpec(Character $character, string $spec): CharacterSpec
    {
        $specEnum = Spec::tryFrom($spec);
        abort_if($specEnum === null, 404);

        return $character->specs()->where('spec', $specEnum->value)->firstOrFail();
    }

    /**
     * Mapeia o nome do slot como o addon SimulationCraft escreve numa linha
     * de perfil (`head=`, `main_hand=`, ...) pro slot correspondente do
     * boneco. Alguns nomes têm sinônimo (`shoulder`/`shoulders`) porque o
     * SimC aceita ambos dependendo da versão/expansão.
     *
     * @var array<string, EquipmentSlot>
     */
    private const SIMC_SLOT_MAP = [
        'head' => EquipmentSlot::Head,
        'neck' => EquipmentSlot::Neck,
        'shoulder' => EquipmentSlot::Shoulders,
        'shoulders' => EquipmentSlot::Shoulders,
        'back' => EquipmentSlot::Back,
        'chest' => EquipmentSlot::Chest,
        'shirt' => EquipmentSlot::Shirt,
        'tabard' => EquipmentSlot::Tabard,
        'wrist' => EquipmentSlot::Wrists,
        'wrists' => EquipmentSlot::Wrists,
        'hand' => EquipmentSlot::Hands,
        'hands' => EquipmentSlot::Hands,
        'waist' => EquipmentSlot::Waist,
        'legs' => EquipmentSlot::Legs,
        'feet' => EquipmentSlot::Feet,
        'finger1' => EquipmentSlot::Ring1,
        'ring1' => EquipmentSlot::Ring1,
        'finger2' => EquipmentSlot::Ring2,
        'ring2' => EquipmentSlot::Ring2,
        'trinket1' => EquipmentSlot::Trinket1,
        'trinket2' => EquipmentSlot::Trinket2,
        'main_hand' => EquipmentSlot::MainHand,
        'off_hand' => EquipmentSlot::OffHand,
        'ranged' => EquipmentSlot::Ranged,
    ];

    /**
     * Monta o boneco inteiro a partir do texto que addons de WotLK exportam
     * (seção 7.2). Reconhece dois formatos, sem exigir um addon específico:
     *
     * 1. Perfil do SimulationCraft (comando `/simc` no jogo) — linhas tipo
     *    `head=algum_item,id=12345,...`. Recomendado no README porque o
     *    addon já roda em qualquer servidor (lê só a API do cliente) e o
     *    nome do slot vem explícito na própria linha, então esse caminho é
     *    resolvido primeiro e manda no slot.
     * 2. Links de item crus (`item:ID`, o jeito universal do WoW representar
     *    um item em texto — o que sobra ao colar do chat, de um shift-clique
     *    ou de qualquer outro addon). Preenche o primeiro slot do boneco que
     *    aceita aquele tipo de item, pulando os que o passo 1 já ocupou.
     *
     * Idempotente: colar de novo só atualiza os mesmos slots, nunca duplica.
     */
    public function import(Request $request, Character $character, string $spec): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
        ]);

        $text = $validated['text'];

        preg_match_all('/^\s*([a-z_0-9]+)\s*=.*?\bid=\s*(\d+)/im', $text, $simcMatches, PREG_SET_ORDER);

        $simcBySlot = [];
        foreach ($simcMatches as $match) {
            $slot = self::SIMC_SLOT_MAP[strtolower($match[1])] ?? null;

            if ($slot !== null) {
                $simcBySlot[$slot->value] = (int) $match[2];
            }
        }

        preg_match_all('/item:(\d+)/i', $text, $linkMatches);
        // Sem array_unique de propósito: um personagem pode ter o mesmo item
        // em dois slots (dois anéis iguais, duas armas iguais na
        // dual-empunhadura) — cada ocorrência do link deve poder preencher
        // seu próprio slot, e firstAvailableFor() já pula slot já usado.
        $linkItemIds = array_map('intval', $linkMatches[1]);

        if ($simcBySlot === [] && $linkItemIds === []) {
            return back()->with('equipmentImport', ['equipped' => 0, 'ignored' => 0]);
        }

        $allItemIds = array_unique([...array_values($simcBySlot), ...$linkItemIds]);
        $items = Item::query()->whereIn('item_id', $allItemIds)->get()->keyBy('item_id');

        $summary = DB::transaction(function () use ($characterSpec, $simcBySlot, $linkItemIds, $items) {
            $equipped = 0;
            $ignored = 0;
            $filledSlots = [];

            foreach ($simcBySlot as $slotValue => $itemId) {
                $item = $items->get($itemId);

                if ($item === null) {
                    $ignored++;

                    continue;
                }

                $filledSlots[] = EquipmentSlot::from($slotValue);
                $equipped++;

                $characterSpec->items()->updateOrCreate(
                    ['slot' => $slotValue],
                    ['item_id' => $item->id],
                );
            }

            foreach ($linkItemIds as $itemId) {
                $item = $items->get($itemId);

                $slot = $item ? EquipmentSlot::firstAvailableFor($item->slot, $filledSlots) : null;

                if ($slot === null) {
                    $ignored++;

                    continue;
                }

                $filledSlots[] = $slot;
                $equipped++;

                $characterSpec->items()->updateOrCreate(
                    ['slot' => $slot->value],
                    ['item_id' => $item->id],
                );
            }

            return ['equipped' => $equipped, 'ignored' => $ignored];
        });

        return back()->with('equipmentImport', $summary);
    }
}

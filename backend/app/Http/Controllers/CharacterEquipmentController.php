<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Enums\GemColor;
use App\Enums\Spec;
use App\Models\Character;
use App\Models\CharacterItem;
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

        $item = Item::findOrFail((int) $validated['item_id']);

        $acceptedSlots = array_map(
            fn ($inventorySlot) => $inventorySlot->value,
            $equipmentSlot->acceptedInventorySlots(),
        );

        abort_unless(in_array($item->slot->value, $acceptedSlots, true), 422, 'Esse item não cabe nesse slot.');

        $this->equipItem($characterSpec, $equipmentSlot->value, $item);

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
     * Equipa o item no slot, cuidando pra não deixar gema "grudada" errada:
     * como a linha de character_items é reaproveitada (updateOrCreate por
     * slot, não delete+recreate — senão a reordenação via drag-and-drop e
     * afins ficariam mais complicadas), trocar de item no mesmo slot exige
     * limpar as gemas antigas manualmente, já que o item novo pode nem ter
     * os mesmos sockets do antigo.
     */
    private function equipItem(CharacterSpec $characterSpec, string $slotValue, Item $item): void
    {
        $existing = $characterSpec->items()->where('slot', $slotValue)->first();

        if ($existing && $existing->item_id !== $item->id) {
            $existing->gems()->delete();
        }

        $characterSpec->items()->updateOrCreate(
            ['slot' => $slotValue],
            ['item_id' => $item->id],
        );
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
     * O addon SimulationCraft oficial não roda em 3.3.5a (só declara
     * suporte a Interface de retail moderno) — o WowSims Exporter
     * (github.com/wowsims/exporter) é a alternativa mantida ativamente
     * para esse client, mas exporta em JSON em vez do texto do SimC.
     * `gear.items` é uma tabela Lua indexada pela POSIÇÃO do slot (não pelo
     * nome), na ordem declarada em EquipmentSpec.lua do addon — daí esse
     * mapa ser por posição, não por chave string como o SIMC_SLOT_MAP.
     *
     * @var array<int, EquipmentSlot>
     */
    private const WOWSIMS_GEAR_POSITION_MAP = [
        1 => EquipmentSlot::Head,
        2 => EquipmentSlot::Neck,
        3 => EquipmentSlot::Shoulders,
        4 => EquipmentSlot::Back,
        5 => EquipmentSlot::Chest,
        6 => EquipmentSlot::Wrists,
        7 => EquipmentSlot::Hands,
        8 => EquipmentSlot::Waist,
        9 => EquipmentSlot::Legs,
        10 => EquipmentSlot::Feet,
        11 => EquipmentSlot::Ring1,
        12 => EquipmentSlot::Ring2,
        13 => EquipmentSlot::Trinket1,
        14 => EquipmentSlot::Trinket2,
        15 => EquipmentSlot::MainHand,
        16 => EquipmentSlot::OffHand,
        17 => EquipmentSlot::Ranged,
    ];

    /**
     * Decodifica um export do addon WowSims Exporter (JSON) pra
     * slot => item_id. Retorna [] se o texto colado não for esse formato.
     *
     * `gear.items` pode serializar como array 0-indexado (sem buracos) ou
     * como objeto com chaves string 1..17 (quando algum slot está vazio) —
     * a lib de JSON do addon decide isso pela forma da tabela Lua de
     * origem, então tratamos os dois casos.
     *
     * @return array<string, int>
     */
    private function parseWowSimsExport(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded) || ! is_array($decoded['gear']['items'] ?? null)) {
            return [];
        }

        $items = $decoded['gear']['items'];
        $isZeroIndexed = array_is_list($items);

        $bySlot = [];
        foreach ($items as $key => $itemData) {
            if (! is_array($itemData) || ! isset($itemData['id'])) {
                continue;
            }

            $position = $isZeroIndexed ? ((int) $key + 1) : (int) $key;
            $slot = self::WOWSIMS_GEAR_POSITION_MAP[$position] ?? null;

            if ($slot !== null) {
                $bySlot[$slot->value] = (int) $itemData['id'];
            }
        }

        return $bySlot;
    }

    /**
     * Monta o boneco inteiro a partir do texto que addons de WotLK exportam
     * (seção 7.2). Reconhece três formatos, sem exigir um addon específico:
     *
     * 1. Perfil do SimulationCraft (comando `/simc` no jogo) — linhas tipo
     *    `head=algum_item,id=12345,...`. O nome do slot vem explícito na
     *    própria linha, então esse caminho é resolvido primeiro e manda
     *    no slot.
     * 2. Export do addon WowSims Exporter (JSON) — ver parseWowSimsExport().
     *    Único addon com porte real pra 3.3.5a hoje; o SimulationCraft
     *    oficial não roda nesse client.
     * 3. Links de item crus (`item:ID`, o jeito universal do WoW representar
     *    um item em texto — o que sobra ao colar do chat, de um shift-clique
     *    ou de qualquer outro addon). Preenche o primeiro slot do boneco que
     *    aceita aquele tipo de item, pulando os que os passos 1-2 já ocuparam.
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

        // Texto de perfil do SimC não é JSON válido, então isso só preenche
        // algo quando o formato colado é realmente o do WowSims Exporter.
        $simcBySlot = [...$simcBySlot, ...$this->parseWowSimsExport($text)];

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

                $this->equipItem($characterSpec, $slotValue, $item);
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

                $this->equipItem($characterSpec, $slot->value, $item);
            }

            return ['equipped' => $equipped, 'ignored' => $ignored];
        });

        return back()->with('equipmentImport', $summary);
    }
}

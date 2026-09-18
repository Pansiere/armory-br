<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentSlot;
use App\Enums\GemColor;
use App\Enums\Profession;
use App\Enums\Race;
use App\Enums\Spec;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterSpec;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    private function equipItem(CharacterSpec $characterSpec, string $slotValue, Item $item): CharacterItem
    {
        $existing = $characterSpec->items()->where('slot', $slotValue)->first();

        if ($existing && $existing->item_id !== $item->id) {
            $existing->gems()->delete();
        }

        return $characterSpec->items()->updateOrCreate(
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
     * `gear.items` sempre serializa como array JSON, com `null` explícito
     * nas posições sem item (confirmado no código-fonte de LibParse, a lib
     * de JSON embutida no addon: IsArray() usa pairs(), não ipairs(), então
     * uma tabela Lua só de chaves numéricas positivas vira array mesmo com
     * buracos — WriteTable() escreve `null` pra cada posição intermediária
     * ausente até o maior índice presente).
     *
     * @return array<string, int>
     */
    private function parseWowSimsExport(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded) || ! is_array($decoded['gear']['items'] ?? null)) {
            return [];
        }

        $bySlot = [];
        foreach ($decoded['gear']['items'] as $index => $itemData) {
            if (! is_array($itemData) || ! isset($itemData['id'])) {
                continue;
            }

            $slot = self::WOWSIMS_GEAR_POSITION_MAP[(int) $index + 1] ?? null;

            if ($slot !== null) {
                $bySlot[$slot->value] = (int) $itemData['id'];
            }
        }

        return $bySlot;
    }

    /**
     * Perfil do SimulationCraft, linha a linha (não preg_match_all na string
     * inteira): precisamos associar um eventual `gems=68780/68793` ao `id=`
     * da MESMA linha, e isso é ambíguo sem saber onde cada linha começa e
     * termina.
     *
     * @return array{0: array<string, int>, 1: array<string, list<int>>} [slot => item_id, slot => [gem_item_id, ...]]
     */
    private function parseSimcProfile(string $text): array
    {
        $bySlot = [];
        $gemsBySlot = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            if (! preg_match('/^\s*([a-z_0-9]+)\s*=.*?\bid=\s*(\d+)/i', $line, $match)) {
                continue;
            }

            $slot = self::SIMC_SLOT_MAP[strtolower($match[1])] ?? null;

            if ($slot === null) {
                continue;
            }

            $bySlot[$slot->value] = (int) $match[2];

            if (preg_match('/\bgems=([\d\/]+)/i', $line, $gemsMatch)) {
                $gemsBySlot[$slot->value] = array_map('intval', explode('/', $gemsMatch[1]));
            }
        }

        return [$bySlot, $gemsBySlot];
    }

    /**
     * Encaixa as gemas exportadas nos sockets do item, na ordem em que
     * vieram (posição 1, 2, 3). Uma gema que não existe no banco, que não é
     * gema de verdade, ou que não combina com a cor do socket é ignorada
     * silenciosamente — igual a um item desconhecido no resto do import,
     * isso não deve derrubar o restante da importação.
     *
     * @param  list<int>  $gemItemIds  IDs de item (não de linha do banco) das gemas, na ordem dos sockets.
     * @param  Collection<int, Item>  $items  Itens já carregados, indexados por item_id.
     */
    private function attachGemsFromExport(CharacterItem $characterItem, array $gemItemIds, Collection $items): void
    {
        $socketColors = $characterItem->item->socketColors();

        foreach ($gemItemIds as $index => $gemItemId) {
            $position = $index + 1;
            $gem = $items->get($gemItemId);
            $socketColor = $socketColors[$position - 1] ?? null;

            if ($gem === null || ! $gem->isGem() || $socketColor === null) {
                continue;
            }

            if (! GemColor::gemFitsSocket($gem->gem_color, $socketColor)) {
                continue;
            }

            $characterItem->gems()->updateOrCreate(
                ['socket_position' => $position],
                ['item_id' => $gem->id],
            );
        }
    }

    /**
     * Atualiza nome/raça/nível/profissões do personagem a partir das linhas
     * `name=`, `race=`, `level=` e `profession=slug:skill_level` que o
     * addon ArmoryBRExport inclui no início do export — convenção própria,
     * não existe no SimC original. Cada campo é independente: se não
     * aparecer no texto (outra fonte, versão antiga do addon), o valor
     * atual do personagem não é mexido.
     *
     * Profissões são sincronizadas por completo (removidas as que não
     * vierem) só quando pelo menos uma linha `profession=` aparece — feito
     * assim porque, diferente de equipamento/gemas, o addon sempre lista o
     * conjunto COMPLETO de profissões atuais quando suporta esse campo, então
     * a ausência de uma profissão que existia antes significa que ela foi
     * largada de verdade no jogo, não que o texto é parcial.
     */
    private function updateCharacterMetaFromExport(Character $character, string $text): void
    {
        if (preg_match('/^\s*name\s*=\s*(.+?)\s*$/im', $text, $match)) {
            $character->name = $match[1];
        }

        if (preg_match('/^\s*race\s*=\s*([a-z_]+)\s*$/im', $text, $match)) {
            $race = Race::tryFrom($match[1]);

            if ($race !== null) {
                $character->race = $race;
                // A facção é implícita na raça — evita ficar inconsistente
                // (ex.: texto colado sem querer no personagem errado).
                $character->faction = $race->faction();
            }
        }

        if (preg_match('/^\s*level\s*=\s*(\d+)\s*$/im', $text, $match)) {
            $character->level = (int) $match[1];
        }

        $character->save();

        preg_match_all('/^\s*profession\s*=\s*([a-z_]+):(\d+)\s*$/im', $text, $professionMatches, PREG_SET_ORDER);

        if ($professionMatches === []) {
            return;
        }

        $character->professions()->delete();
        $primaryCount = 0;
        $seen = [];

        foreach ($professionMatches as $match) {
            $profession = Profession::tryFrom($match[1]);

            if ($profession === null || in_array($profession, $seen, true)) {
                continue;
            }

            if ($profession->isPrimary()) {
                if ($primaryCount >= Profession::MAX_PRIMARY_PER_CHARACTER) {
                    continue;
                }

                $primaryCount++;
            }

            $seen[] = $profession;

            $character->professions()->create([
                'name' => $profession->value,
                'skill_level' => min((int) $match[2], Profession::MAX_SKILL_LEVEL),
            ]);
        }
    }

    /**
     * Monta o boneco inteiro a partir do texto que addons de WotLK exportam
     * (seção 7.2), e também atualiza nome/raça/nível/profissões do
     * personagem se o texto trouxer essas linhas (ver
     * updateCharacterMetaFromExport()). Reconhece três formatos de
     * equipamento, sem exigir um addon específico:
     *
     * 1. Perfil do SimulationCraft (comando `/simc` no jogo, ou o addon
     *    próprio ArmoryBRExport — ver wotlk-addons) — linhas tipo
     *    `head=algum_item,id=12345,gems=68780/68793`. O nome do slot vem
     *    explícito na própria linha, então esse caminho é resolvido
     *    primeiro e manda no slot. `gems=` é opcional e específico do
     *    ArmoryBRExport: IDs de gema separados por `/`, na ordem dos
     *    sockets do item.
     * 2. Export do addon WowSims Exporter (JSON) — ver parseWowSimsExport().
     *    Não tem porte funcional pra 3.3.5a hoje (crasha ao carregar nesse
     *    client), mas o parser continua aceitando o formato pro dia em que
     *    isso mudar.
     * 3. Links de item crus (`item:ID`, o jeito universal do WoW representar
     *    um item em texto — o que sobra ao colar do chat, de um shift-clique
     *    ou de qualquer outro addon). Preenche o primeiro slot do boneco que
     *    aceita aquele tipo de item, pulando os que os passos 1-2 já ocuparam.
     *    Não carrega gema (links crus não têm essa informação).
     *
     * Idempotente: colar de novo só atualiza os mesmos slots/sockets
     * mencionados no texto, nunca duplica e nunca apaga gema de um slot que
     * o texto colado não menciona.
     */
    public function import(Request $request, Character $character, string $spec): RedirectResponse
    {
        Gate::authorize('update', $character);

        $characterSpec = $this->resolveSpec($character, $spec);

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
        ]);

        $text = $validated['text'];

        // Processado antes do "sem equipamento reconhecido, retorna cedo"
        // logo abaixo — texto só com metadados (sem nenhum slot reconhecido)
        // ainda deve atualizar nome/raça/nível/profissões.
        $this->updateCharacterMetaFromExport($character, $text);

        [$simcBySlot, $gemsBySlot] = $this->parseSimcProfile($text);

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

        $allGemIds = array_merge(...array_values($gemsBySlot ?: [[]]));
        $allItemIds = array_unique([...array_values($simcBySlot), ...$linkItemIds, ...$allGemIds]);
        $items = Item::query()->whereIn('item_id', $allItemIds)->get()->keyBy('item_id');

        $summary = DB::transaction(function () use ($characterSpec, $simcBySlot, $gemsBySlot, $linkItemIds, $items) {
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

                $characterItem = $this->equipItem($characterSpec, $slotValue, $item);

                if (isset($gemsBySlot[$slotValue])) {
                    $this->attachGemsFromExport($characterItem, $gemsBySlot[$slotValue], $items);
                }
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

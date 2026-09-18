<?php

namespace App\Http\Controllers;

use App\Enums\CharacterClass;
use App\Enums\EquipmentSlot;
use App\Enums\Faction;
use App\Enums\Profession;
use App\Enums\Race;
use App\Enums\Raid;
use App\Enums\Spec;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use App\Support\CharacterImportParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CharacterController extends Controller
{
    public function index(Request $request): Response
    {
        $characters = $request->user()
            ->characters()
            ->with(['specs.items.item', 'specs.items.gems.item', 'professions', 'raidLocks'])
            ->orderBy('position')
            ->get()
            ->groupBy(fn (Character $character) => $character->faction->value);

        return Inertia::render('dashboard', [
            'alliance' => CharacterResource::collection($characters->get(Faction::Alliance->value, collect())),
            'horde' => CharacterResource::collection($characters->get(Faction::Horde->value, collect())),
            'equipmentSlots' => $this->equipmentSlotOptions(),
            'raids' => collect(Raid::cases())->map(fn (Raid $raid) => [
                'value' => $raid->value,
                'label' => $raid->label(),
                'shortLabel' => $raid->shortLabel(),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('characters/create', $this->formOptions());
    }

    public function store(Request $request, CharacterImportParser $parser): RedirectResponse
    {
        $validated = $this->validateCharacter($request, withImportText: true);

        [$character, $summary] = DB::transaction(function () use ($validated, $request, $parser) {
            $nextPosition = $request->user()->characters()
                ->where('faction', $validated['faction'])
                ->max('position') + 1;

            $character = $request->user()->characters()->create([
                'name' => $validated['name'],
                'faction' => $validated['faction'],
                'class' => $validated['class'],
                'race' => $validated['race'] ?? null,
                'level' => $validated['level'] ?? null,
                'is_public' => $validated['is_public'] ?? false,
                'position' => $nextPosition,
            ]);

            $this->syncSpecs($character, $validated['specs']);
            $this->syncProfessions($character, $validated['professions'] ?? []);

            $text = trim($validated['text'] ?? '');

            if ($text === '') {
                return [$character, null];
            }

            // Import na criação (issue #23) sempre mira a spec primária —
            // o personagem acabou de nascer, não existe aba ativa ainda pra
            // escolher (mesma regra do import na edição: o addon só exporta
            // o equipamento ativo, então dual spec precisa de um segundo
            // colar depois, na tela de edição).
            $primarySpec = $character->specs()->where('position', 1)->firstOrFail();

            $parser->applyMeta($character, $parser->parseMeta($text));
            $summary = $parser->applyEquipment($primarySpec, $parser->parseEquipment($text));

            return [$character, $summary];
        });

        // Sem import: volta pra dashboard, igual sempre foi. Com import:
        // manda pra edição — assim dá pra conferir/ajustar o que o texto
        // colado trouxe (e é lá que o resumo "N itens equipados" já
        // aparece) em vez de forçar um clique a mais.
        if ($summary === null) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('characters.edit', $character)->with('equipmentImport', $summary);
    }

    /**
     * Baixa o personagem inteiro como JSON (issue #18) — backup de
     * verdade, já que apagar é permanente (LGPD, sem soft-delete), ou
     * base pra duplicar um personagem. Usa o `item_id` do jogo (não o
     * `id` interno da tabela `items`) pra ficar portável: a base de itens
     * pode ser truncada e reimportada (`items:import`) e os ids internos
     * mudam, mas o `item_id` do jogo é estável.
     */
    public function export(Character $character): JsonResponse
    {
        Gate::authorize('update', $character);

        $character->load(['specs.items.item', 'specs.items.gems.item', 'professions']);

        $data = [
            'name' => $character->name,
            'faction' => $character->faction->value,
            'class' => $character->class->value,
            'race' => $character->race?->value,
            'level' => $character->level,
            'professions' => $character->professions->map(fn ($profession) => [
                'name' => $profession->name->value,
                'skill_level' => $profession->skill_level,
            ])->all(),
            'specs' => $character->specs->map(fn ($characterSpec) => [
                'spec' => $characterSpec->spec->value,
                'position' => $characterSpec->position,
                'equipment' => $characterSpec->items->map(fn ($characterItem) => [
                    'slot' => $characterItem->slot->value,
                    'item_id' => $characterItem->item->item_id,
                    'gems' => $characterItem->gems->map(fn ($gem) => [
                        'socket_position' => $gem->socket_position,
                        'item_id' => $gem->item->item_id,
                    ])->all(),
                ])->all(),
            ])->all(),
        ];

        $filename = Str::slug($character->name, '_').'-armory-br.json';

        return response()->json($data, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recria um personagem a partir do JSON exportado por export() —
     * diferente do import de texto colado (issue #23), que nunca traz
     * classe/specs porque nenhum addon exporta isso do jeito que a gente
     * precisa. O JSON da própria Armory BR é o dump completo, então cobre
     * 100% do personagem numa importação só.
     */
    public function importJson(Request $request): RedirectResponse
    {
        $request->validate([
            // Tamanho de sobra pra um personagem com as 2 specs cheias de
            // equipamento — o real gira em torno de poucos KB.
            'file' => ['required', 'file', 'max:512'],
        ]);

        $decoded = json_decode((string) $request->file('file')->get(), true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'file' => 'Esse arquivo não é um JSON válido.',
            ]);
        }

        $validated = $this->validateCharacterExport($decoded);

        $character = DB::transaction(function () use ($validated, $request) {
            $nextPosition = $request->user()->characters()
                ->where('faction', $validated['faction'])
                ->max('position') + 1;

            $character = $request->user()->characters()->create([
                'name' => $validated['name'],
                'faction' => $validated['faction'],
                'class' => $validated['class'],
                'race' => $validated['race'] ?? null,
                'level' => $validated['level'] ?? null,
                'position' => $nextPosition,
            ]);

            foreach ($validated['professions'] ?? [] as $profession) {
                $character->professions()->create($profession);
            }

            $parser = app(CharacterImportParser::class);

            foreach ($validated['specs'] as $specData) {
                $characterSpec = $character->specs()->create([
                    'spec' => $specData['spec'],
                    'position' => $specData['position'],
                ]);

                $parser->applyEquipment($characterSpec, $this->equipmentFromExport($specData['equipment'] ?? []));
            }

            return $character;
        });

        return redirect()->route('characters.edit', $character);
    }

    /**
     * Converte o array `equipment` do JSON exportado (uma lista, uma
     * entrada por slot) pro formato que CharacterImportParser::applyEquipment()
     * já sabe aplicar (mapas por slot) — reusa a mesma lógica testada de
     * resolver item/gema e checar cor do socket, em vez de duplicar.
     *
     * @param  array<int, array{slot: string, item_id: int, gems?: array<int, array{socket_position: int, item_id: int}>}>  $equipment
     * @return array{bySlot: array<string, int>, gemsBySlot: array<string, list<int>>, rawLinkItemIds: list<int>}
     */
    private function equipmentFromExport(array $equipment): array
    {
        $bySlot = [];
        $gemsBySlot = [];

        foreach ($equipment as $entry) {
            $bySlot[$entry['slot']] = $entry['item_id'];

            if (! empty($entry['gems'])) {
                $gemsBySlot[$entry['slot']] = collect($entry['gems'])
                    ->sortBy('socket_position')
                    ->pluck('item_id')
                    ->all();
            }
        }

        return ['bySlot' => $bySlot, 'gemsBySlot' => $gemsBySlot, 'rawLinkItemIds' => []];
    }

    public function edit(Character $character): Response
    {
        Gate::authorize('update', $character);

        return Inertia::render('characters/edit', [
            ...$this->formOptions(),
            'character' => new CharacterResource($character->load(['specs.items.item', 'specs.items.gems.item', 'professions'])),
        ]);
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        Gate::authorize('update', $character);

        $validated = $this->validateCharacter($request);

        DB::transaction(function () use ($character, $validated, $request) {
            $position = $character->position;

            // Trocar de facção pelo formulário (não pelo drag-and-drop, que
            // já recalcula tudo em reorder()) manda o personagem pro fim da
            // nova coluna — senão ele mantém a position da coluna antiga e
            // pode colidir com quem já está na posição de destino.
            if ($validated['faction'] !== $character->faction->value) {
                $position = $request->user()->characters()
                    ->where('faction', $validated['faction'])
                    ->max('position') + 1;
            }

            $character->update([
                'name' => $validated['name'],
                'faction' => $validated['faction'],
                'class' => $validated['class'],
                'race' => $validated['race'] ?? null,
                'level' => $validated['level'] ?? null,
                'is_public' => $validated['is_public'] ?? false,
                'position' => $position,
            ]);

            $this->syncSpecs($character, $validated['specs']);
            $this->syncProfessions($character, $validated['professions'] ?? []);
        });

        return redirect()->route('dashboard');
    }

    public function destroy(Character $character): RedirectResponse
    {
        Gate::authorize('delete', $character);

        $character->delete();

        return redirect()->route('dashboard');
    }

    /**
     * Persiste a nova ordem/facção de todos os personagens após um
     * arrastar-e-soltar. Autosave — sem botão "salvar".
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alliance' => ['present', 'array'],
            'alliance.*' => ['integer', 'distinct'],
            'horde' => ['present', 'array'],
            'horde.*' => ['integer', 'distinct'],
        ]);

        $orderedIds = [...$validated['alliance'], ...$validated['horde']];

        $characters = $request->user()->characters()->whereKey($orderedIds)->get()->keyBy('id');

        abort_unless($characters->count() === count($orderedIds), 403);

        DB::transaction(function () use ($validated, $characters) {
            foreach (['alliance' => Faction::Alliance, 'horde' => Faction::Horde] as $key => $faction) {
                foreach ($validated[$key] as $position => $id) {
                    $characters[$id]->update([
                        'faction' => $faction,
                        'position' => $position,
                    ]);
                }
            }
        });

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'factions' => collect(Faction::cases())->map(fn (Faction $faction) => [
                'value' => $faction->value,
                'label' => $faction->label(),
            ]),
            'classes' => collect(CharacterClass::cases())->map(fn (CharacterClass $class) => [
                'value' => $class->value,
                'label' => $class->label(),
                'color' => $class->color(),
                'needsTextOutline' => $class->needsTextOutline(),
                'iconUrl' => $class->iconUrl(),
            ]),
            'specs' => collect(Spec::cases())->map(fn (Spec $spec) => [
                'value' => $spec->value,
                'label' => $spec->label(),
                'class' => $spec->characterClass()->value,
                'iconUrl' => $spec->iconUrl(),
            ]),
            'professions' => collect(Profession::cases())->map(fn (Profession $profession) => [
                'value' => $profession->value,
                'label' => $profession->label(),
                'isPrimary' => $profession->isPrimary(),
                'iconUrl' => $profession->iconUrl(),
            ]),
            'races' => collect(Race::cases())->map(fn (Race $race) => [
                'value' => $race->value,
                'label' => $race->label(),
                'faction' => $race->faction()->value,
                'iconUrl' => $race->iconUrl(),
            ]),
            'equipmentSlots' => $this->equipmentSlotOptions(),
        ];
    }

    /**
     * Extraído de formOptions() porque o dashboard (index()) também precisa
     * disso — pro boneco somente-leitura no modal do card (seção da issue
     * #20) — sem precisar do resto (classes/raças/specs/profissões), que
     * só as telas de criar/editar personagem usam.
     *
     * @return array<int, array{value: string, label: string, column: string}>
     */
    private function equipmentSlotOptions(): array
    {
        return collect(EquipmentSlot::cases())->map(fn (EquipmentSlot $slot) => [
            'value' => $slot->value,
            'label' => $slot->label(),
            'column' => $slot->column(),
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCharacter(Request $request, bool $withImportText = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            ...($withImportText ? ['text' => ['nullable', 'string', 'max:20000']] : []),
            'faction' => ['required', new Enum(Faction::class)],
            'class' => ['required', new Enum(CharacterClass::class)],
            'specs' => ['required', 'array', 'min:1', 'max:2'],
            'specs.*' => ['distinct', new Enum(Spec::class), function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                $spec = Spec::tryFrom($value);
                $class = CharacterClass::tryFrom($request->input('class', ''));

                if ($spec && $class && $spec->characterClass() !== $class) {
                    $fail('Essa especialização não é dessa classe.');
                }
            }],
            'race' => ['nullable', new Enum(Race::class), function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                $race = Race::tryFrom($value ?? '');
                $faction = Faction::tryFrom($request->input('faction', ''));

                if ($race && $faction && $race->faction() !== $faction) {
                    $fail('Essa raça não é dessa facção.');
                }
            }],
            'level' => ['nullable', 'integer', 'min:1', 'max:80'],
            'is_public' => ['nullable', 'boolean'],
            'professions' => ['nullable', 'array', function (string $attribute, mixed $value, \Closure $fail) {
                $primaryCount = collect($value)
                    ->filter(fn (mixed $item) => is_array($item) && Profession::tryFrom($item['name'] ?? '')?->isPrimary() === true)
                    ->count();

                if ($primaryCount > Profession::MAX_PRIMARY_PER_CHARACTER) {
                    $fail('Máximo de '.Profession::MAX_PRIMARY_PER_CHARACTER.' profissões primárias por personagem.');
                }
            }],
            'professions.*.name' => ['required', new Enum(Profession::class), 'distinct'],
            'professions.*.skill_level' => ['nullable', 'integer', 'min:0', 'max:'.Profession::MAX_SKILL_LEVEL],
        ]);
    }

    /**
     * Valida o JSON recebido em importJson() (já decodificado pra array)
     * contra o formato de export() — entrada de arquivo controlada pelo
     * usuário (pode vir de fora, com campo faltando ou tipo errado, ou até
     * editado à mão), então valida cada campo/array aninhado
     * explicitamente em vez de confiar na forma. `Validator::make` em vez
     * de `$request->validate()` porque os dados não vêm dos campos do
     * request — vêm de dentro do arquivo enviado.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateCharacterExport(array $data): array
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'faction' => ['required', new Enum(Faction::class)],
            'class' => ['required', new Enum(CharacterClass::class)],
            'race' => ['nullable', new Enum(Race::class), function (string $attribute, mixed $value, \Closure $fail) use ($data) {
                $race = Race::tryFrom($value ?? '');
                $faction = Faction::tryFrom($data['faction'] ?? '');

                if ($race && $faction && $race->faction() !== $faction) {
                    $fail('Essa raça não é dessa facção.');
                }
            }],
            'level' => ['nullable', 'integer', 'min:1', 'max:80'],
            'professions' => ['nullable', 'array'],
            'professions.*.name' => ['required', new Enum(Profession::class), 'distinct'],
            'professions.*.skill_level' => ['nullable', 'integer', 'min:0', 'max:'.Profession::MAX_SKILL_LEVEL],
            'specs' => ['required', 'array', 'min:1', 'max:2'],
            'specs.*.spec' => ['required', 'distinct', new Enum(Spec::class), function (string $attribute, mixed $value, \Closure $fail) use ($data) {
                $spec = Spec::tryFrom($value);
                $class = CharacterClass::tryFrom($data['class'] ?? '');

                if ($spec && $class && $spec->characterClass() !== $class) {
                    $fail('Essa especialização não é dessa classe.');
                }
            }],
            'specs.*.position' => ['required', 'distinct', Rule::in([1, 2])],
            'specs.*.equipment' => ['nullable', 'array'],
            // Sem `distinct` aqui de propósito: `specs.*.equipment.*.slot`
            // tem dois wildcards, e o `distinct` do Laravel nessa
            // profundidade achata a checagem através de TODAS as specs em
            // vez de isolar por spec — rejeitaria "cabeça" equipado nas
            // duas specs do dual spec, que é válido e esperado (confirmado
            // testando: sem isso o import de dual spec falhava a
            // validação). Um slot duplicado dentro da MESMA spec já é
            // inofensivo do jeito que equipmentFromExport() aplica (o
            // último da lista simplesmente ganha o slot).
            'specs.*.equipment.*.slot' => ['required', new Enum(EquipmentSlot::class)],
            'specs.*.equipment.*.item_id' => ['required', 'integer'],
            'specs.*.equipment.*.gems' => ['nullable', 'array', 'max:3'],
            'specs.*.equipment.*.gems.*.socket_position' => ['required', 'integer', Rule::in([1, 2, 3])],
            'specs.*.equipment.*.gems.*.item_id' => ['required', 'integer'],
        ])->validate();
    }

    /**
     * Sincroniza as 1-2 specs do personagem preservando a linha (e o
     * equipamento que aponta pra ela via character_spec_id) quando a spec
     * daquela posição não mudou. Só recria a linha — o que derruba o
     * equipamento junto, via cascade — quando a spec do slot realmente troca
     * ou é removida (faz sentido: gear de uma build que não existe mais
     * nesse slot). Por isso não dá pra fazer como syncProfessions (apagar
     * tudo e recriar): isso zeraria o equipamento a cada salvamento do
     * formulário, mesmo sem trocar de spec.
     *
     * @param  array<int, string>  $specs
     */
    private function syncSpecs(Character $character, array $specs): void
    {
        $existingByPosition = $character->specs()->get()->keyBy('position');

        foreach ($specs as $index => $spec) {
            $position = $index + 1;
            $current = $existingByPosition->get($position);

            if ($current && $current->spec->value === $spec) {
                continue;
            }

            $current?->delete();
            $character->specs()->create(['spec' => $spec, 'position' => $position]);
        }

        $character->specs()->whereNotIn('position', range(1, count($specs)))->delete();
    }

    /**
     * @param  array<int, array{name: string, skill_level?: int|null}>  $professions
     */
    private function syncProfessions(Character $character, array $professions): void
    {
        $character->professions()->delete();

        foreach ($professions as $profession) {
            $character->professions()->create([
                'name' => $profession['name'],
                'skill_level' => $profession['skill_level'] ?? null,
            ]);
        }
    }
}

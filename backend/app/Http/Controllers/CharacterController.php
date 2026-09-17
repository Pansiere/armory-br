<?php

namespace App\Http\Controllers;

use App\Enums\CharacterClass;
use App\Enums\EquipmentSlot;
use App\Enums\Faction;
use App\Enums\Profession;
use App\Enums\Race;
use App\Enums\Spec;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class CharacterController extends Controller
{
    public function index(Request $request): Response
    {
        $characters = $request->user()
            ->characters()
            ->with(['specs.items.item', 'professions'])
            ->orderBy('position')
            ->get()
            ->groupBy(fn (Character $character) => $character->faction->value);

        return Inertia::render('dashboard', [
            'alliance' => CharacterResource::collection($characters->get(Faction::Alliance->value, collect())),
            'horde' => CharacterResource::collection($characters->get(Faction::Horde->value, collect())),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('characters/create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCharacter($request);

        DB::transaction(function () use ($validated, $request) {
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
        });

        return redirect()->route('dashboard');
    }

    public function edit(Character $character): Response
    {
        Gate::authorize('update', $character);

        return Inertia::render('characters/edit', [
            ...$this->formOptions(),
            'character' => new CharacterResource($character->load(['specs.items.item', 'professions'])),
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
            ]),
            'races' => collect(Race::cases())->map(fn (Race $race) => [
                'value' => $race->value,
                'label' => $race->label(),
                'faction' => $race->faction()->value,
            ]),
            'equipmentSlots' => collect(EquipmentSlot::cases())->map(fn (EquipmentSlot $slot) => [
                'value' => $slot->value,
                'label' => $slot->label(),
                'column' => $slot->column(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCharacter(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
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

import FactionIcon from '@/components/faction-icon';
import InputError from '@/components/input-error';
import InputLabel from '@/components/input-label';
import TextInput from '@/components/text-input';
import { cn } from '@/lib/utils';
import type { Character, CharacterFormOptions } from '@/types/character';
import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

type ProfessionRow = { name: string; skill_level: string };

type CharacterFormData = {
    name: string;
    faction: string;
    class: string;
    specs: string[];
    race: string;
    level: string;
    is_public: boolean;
    professions: ProfessionRow[];
    text: string;
};

const selectClassName =
    'mt-1 w-full rounded-md border border-tavern-700 bg-tavern-950 px-3 py-2 text-parchment-100 focus:border-parchment-300 focus:outline-none';

export default function CharacterForm({
    options,
    character,
    action,
    method,
    submitLabel,
}: {
    options: CharacterFormOptions;
    character?: Character;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
}) {
    const { data, setData, post, put, transform, processing, errors } =
        useForm<CharacterFormData>({
            name: character?.name ?? '',
            faction: character?.faction ?? options.factions[0]?.value ?? '',
            class: character?.class ?? options.classes[0]?.value ?? '',
            specs: character?.specs.map((spec) => spec.value) ?? [],
            race: character?.race ?? '',
            level: character?.level?.toString() ?? '',
            is_public: character?.is_public ?? false,
            professions: (character?.professions ?? []).map((profession) => ({
                name: profession.name,
                skill_level: profession.skill_level?.toString() ?? '',
            })),
            text: '',
        });

    const isCreating = method === 'post';

    const primaryCount = data.professions.filter(
        (row) =>
            options.professions.find((p) => p.value === row.name)?.isPrimary,
    ).length;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        transform(({ text, ...formData }) => ({
            ...formData,
            specs: formData.specs.filter((spec) => spec !== ''),
            race: formData.race === '' ? null : formData.race,
            level: formData.level === '' ? null : Number(formData.level),
            professions: formData.professions
                .filter((row) => row.name !== '')
                .map((row) => ({
                    name: row.name,
                    skill_level:
                        row.skill_level === '' ? null : Number(row.skill_level),
                })),
            // `text` só existe na criação (issue #23) — na edição isso é
            // outro formulário (EquipmentImport, na tela de edição).
            ...(isCreating ? { text } : {}),
        }));

        if (method === 'put') {
            put(action);
        } else {
            post(action);
        }
    };

    const racesForFaction = options.races.filter(
        (race) => race.faction === data.faction,
    );
    const specsForClass = options.specs.filter(
        (spec) => spec.class === data.class,
    );
    const specIcon = (value: string | undefined) =>
        options.specs.find((spec) => spec.value === value)?.iconUrl;
    const professionIcon = (value: string) =>
        options.professions.find((profession) => profession.value === value)
            ?.iconUrl;
    const raceIcon = (value: string) =>
        options.races.find((race) => race.value === value)?.iconUrl;

    function updateFaction(faction: string) {
        const raceStillValid = options.races.some(
            (race) => race.value === data.race && race.faction === faction,
        );

        setData({
            ...data,
            faction,
            race: raceStillValid ? data.race : '',
        });
    }

    function updateClass(newClass: string) {
        const stillValid = data.specs.filter((spec) =>
            options.specs.some(
                (option) => option.value === spec && option.class === newClass,
            ),
        );

        setData({ ...data, class: newClass, specs: stillValid });
    }

    function updatePrimarySpec(value: string) {
        // Se a spec 2 virou igual à nova spec 1, esvazia a 2 — não faz
        // sentido escolher a mesma árvore duas vezes.
        const secondary = data.specs[1] === value ? undefined : data.specs[1];
        setData('specs', [value, ...(secondary ? [secondary] : [])]);
    }

    function updateSecondarySpec(value: string) {
        const specs = [data.specs[0], value === '' ? undefined : value].filter(
            (spec): spec is string => spec !== undefined,
        );
        setData('specs', specs);
    }

    function addProfession() {
        setData('professions', [
            ...data.professions,
            { name: '', skill_level: '' },
        ]);
    }

    function removeProfession(index: number) {
        setData(
            'professions',
            data.professions.filter((_, i) => i !== index),
        );
    }

    function updateProfession(index: number, patch: Partial<ProfessionRow>) {
        setData(
            'professions',
            data.professions.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );
    }

    return (
        <form onSubmit={submit} className="space-y-6">
            {isCreating && (
                <div className="border-tavern-700 bg-tavern-900 rounded-lg border p-4">
                    <h3 className="font-heading text-parchment-100 text-sm font-semibold">
                        Colar personagem (atalho)
                    </h3>
                    <p className="text-parchment-300 mt-1 text-xs">
                        Tem o texto exportado pelo addon{' '}
                        <strong className="text-parchment-100">
                            ArmoryBRExport
                        </strong>{' '}
                        (ou{' '}
                        <code className="bg-tavern-950 text-parchment-200 rounded px-1 py-0.5">
                            /simc
                        </code>
                        , se um dia existir um port pra 3.3.5a)? Cole aqui e a
                        gente já preenche nome, raça, nível, profissões e
                        equipamento — só a classe e a especialização embaixo
                        continuam sendo escolha manual, já que o texto não traz
                        isso. Sem addon, funciona também com o link de cada item
                        (shift-clique no jogo), só que aí sem
                        nome/raça/nível/profissão. Totalmente opcional — dá pra
                        cadastrar preenchendo os campos abaixo na mão, como
                        sempre.
                    </p>
                    <textarea
                        value={data.text}
                        onChange={(e) => setData('text', e.target.value)}
                        rows={3}
                        placeholder="Cole aqui pra preencher automaticamente (opcional)..."
                        className="border-tavern-700 bg-tavern-950 text-parchment-100 focus:border-parchment-300 mt-2 w-full rounded-md border px-3 py-2 text-sm focus:outline-none"
                    />
                    <InputError message={errors.text} />
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="name">Nome</InputLabel>
                    <TextInput
                        id="name"
                        autoFocus
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="faction">Facção</InputLabel>
                    <div className="mt-1 flex items-center gap-2">
                        <FactionIcon
                            className={cn(
                                'h-8 w-8 shrink-0',
                                data.faction === 'horde'
                                    ? 'text-horde'
                                    : 'text-alliance',
                            )}
                        />
                        <select
                            id="faction"
                            value={data.faction}
                            onChange={(e) => updateFaction(e.target.value)}
                            className={selectClassName + ' mt-0'}
                        >
                            {options.factions.map((faction) => (
                                <option
                                    key={faction.value}
                                    value={faction.value}
                                >
                                    {faction.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <InputError message={errors.faction} />
                </div>

                <div>
                    <InputLabel htmlFor="class">Classe</InputLabel>
                    <div className="mt-1 flex items-center gap-2">
                        <img
                            src={
                                options.classes.find(
                                    (option) => option.value === data.class,
                                )?.iconUrl
                            }
                            alt=""
                            className="border-tavern-700 h-8 w-8 shrink-0 rounded-sm border"
                        />
                        <select
                            id="class"
                            value={data.class}
                            onChange={(e) => updateClass(e.target.value)}
                            className={selectClassName + ' mt-0'}
                        >
                            {options.classes.map((classOption) => (
                                <option
                                    key={classOption.value}
                                    value={classOption.value}
                                >
                                    {classOption.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <InputError message={errors.class} />
                </div>

                <div>
                    <InputLabel htmlFor="spec_1">Especialização</InputLabel>
                    <div className="mt-1 flex items-center gap-2">
                        {specIcon(data.specs[0]) && (
                            <img
                                src={specIcon(data.specs[0])}
                                alt=""
                                className="border-tavern-700 h-8 w-8 shrink-0 rounded-sm border"
                            />
                        )}
                        <select
                            id="spec_1"
                            value={data.specs[0] ?? ''}
                            onChange={(e) => updatePrimarySpec(e.target.value)}
                            className={selectClassName + ' mt-0'}
                        >
                            <option value="" disabled>
                                Selecione...
                            </option>
                            {specsForClass.map((spec) => (
                                <option key={spec.value} value={spec.value}>
                                    {spec.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <InputError message={errors.specs} />
                </div>

                <div>
                    <InputLabel htmlFor="spec_2">
                        Segunda especialização (dual spec)
                    </InputLabel>
                    <div className="mt-1 flex items-center gap-2">
                        {specIcon(data.specs[1]) && (
                            <img
                                src={specIcon(data.specs[1])}
                                alt=""
                                className="border-tavern-700 h-8 w-8 shrink-0 rounded-sm border"
                            />
                        )}
                        <select
                            id="spec_2"
                            value={data.specs[1] ?? ''}
                            onChange={(e) =>
                                updateSecondarySpec(e.target.value)
                            }
                            className={selectClassName + ' mt-0'}
                        >
                            <option value="">Nenhuma</option>
                            {specsForClass
                                .filter((spec) => spec.value !== data.specs[0])
                                .map((spec) => (
                                    <option key={spec.value} value={spec.value}>
                                        {spec.label}
                                    </option>
                                ))}
                        </select>
                    </div>
                    <InputError message={errors.specs} />
                </div>

                <div>
                    <InputLabel htmlFor="race">Raça</InputLabel>
                    <div className="mt-1 flex items-center gap-2">
                        {raceIcon(data.race) && (
                            <img
                                src={raceIcon(data.race)}
                                alt=""
                                className="border-tavern-700 h-8 w-8 shrink-0 rounded-sm border"
                            />
                        )}
                        <select
                            id="race"
                            value={data.race}
                            onChange={(e) => setData('race', e.target.value)}
                            className={selectClassName + ' mt-0'}
                        >
                            <option value="">Não informar</option>
                            {racesForFaction.map((race) => (
                                <option key={race.value} value={race.value}>
                                    {race.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <InputError message={errors.race} />
                </div>

                <div>
                    <InputLabel htmlFor="level">Nível</InputLabel>
                    <TextInput
                        id="level"
                        type="number"
                        min={1}
                        max={80}
                        placeholder="1-80"
                        value={data.level}
                        onChange={(e) => setData('level', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.level} />
                </div>
            </div>

            <div className="border-tavern-700 bg-tavern-900 rounded-md border p-4">
                <label className="text-parchment-100 flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={data.is_public}
                        onChange={(e) => setData('is_public', e.target.checked)}
                        className="border-tavern-700 bg-tavern-950 h-4 w-4 rounded"
                    />
                    Tornar este personagem público
                </label>
                <p className="text-parchment-300 mt-1 text-xs">
                    Gera um link que qualquer pessoa pode ver, sem precisar de
                    login. Fica desligado por padrão.
                </p>

                {character?.is_public && character.public_url && (
                    <p className="mt-2 text-xs">
                        <a
                            href={character.public_url}
                            target="_blank"
                            rel="noreferrer"
                            className="text-parchment-100 font-medium underline"
                        >
                            {character.public_url}
                        </a>
                    </p>
                )}
            </div>

            <div>
                <div className="mb-2 flex items-center justify-between">
                    <InputLabel>Profissões</InputLabel>
                    <span className="text-parchment-300 text-xs">
                        {primaryCount}/2 primárias
                    </span>
                </div>

                <div className="space-y-2">
                    {data.professions.map((row, index) => (
                        <div key={index} className="flex items-center gap-2">
                            {professionIcon(row.name) && (
                                <img
                                    src={professionIcon(row.name)}
                                    alt=""
                                    className="border-tavern-700 h-8 w-8 shrink-0 rounded-sm border"
                                />
                            )}
                            <select
                                value={row.name}
                                onChange={(e) =>
                                    updateProfession(index, {
                                        name: e.target.value,
                                    })
                                }
                                className="border-tavern-700 bg-tavern-950 text-parchment-100 focus:border-parchment-300 flex-1 rounded-md border px-3 py-2 text-sm focus:outline-none"
                            >
                                <option value="">Selecione...</option>
                                {options.professions.map((profession) => (
                                    <option
                                        key={profession.value}
                                        value={profession.value}
                                        disabled={
                                            profession.isPrimary &&
                                            primaryCount >= 2 &&
                                            row.name !== profession.value
                                        }
                                    >
                                        {profession.label}
                                        {profession.isPrimary
                                            ? ''
                                            : ' (secundária)'}
                                    </option>
                                ))}
                            </select>

                            <TextInput
                                type="number"
                                min={0}
                                max={450}
                                placeholder="Nível"
                                value={row.skill_level}
                                onChange={(e) =>
                                    updateProfession(index, {
                                        skill_level: e.target.value,
                                    })
                                }
                                className="w-24"
                            />

                            <button
                                type="button"
                                onClick={() => removeProfession(index)}
                                className="border-tavern-700 text-parchment-300 hover:text-horde rounded-md border px-3 text-sm"
                            >
                                Remover
                            </button>
                        </div>
                    ))}
                </div>

                {data.professions.length < options.professions.length && (
                    <button
                        type="button"
                        onClick={addProfession}
                        className="text-parchment-100 mt-2 text-sm font-medium underline"
                    >
                        + Adicionar profissão
                    </button>
                )}

                <InputError message={errors.professions} />
            </div>

            <button
                type="submit"
                disabled={processing}
                className="bg-alliance hover:bg-alliance-dim rounded-md px-5 py-2 font-medium text-white transition disabled:opacity-50"
            >
                {submitLabel}
            </button>
        </form>
    );
}

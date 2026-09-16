import InputError from '@/components/input-error';
import InputLabel from '@/components/input-label';
import TextInput from '@/components/text-input';
import type { Character, CharacterFormOptions } from '@/types/character';
import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

type ProfessionRow = { name: string; skill_level: string };

type CharacterFormData = {
    name: string;
    faction: string;
    class: string;
    spec: string;
    race: string;
    level: string;
    professions: ProfessionRow[];
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
            spec: character?.spec ?? '',
            race: character?.race ?? '',
            level: character?.level?.toString() ?? '',
            professions: (character?.professions ?? []).map((profession) => ({
                name: profession.name,
                skill_level: profession.skill_level?.toString() ?? '',
            })),
        });

    const primaryCount = data.professions.filter(
        (row) => options.professions.find((p) => p.value === row.name)?.isPrimary,
    ).length;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        transform((formData) => ({
            ...formData,
            race: formData.race === '' ? null : formData.race,
            level: formData.level === '' ? null : Number(formData.level),
            professions: formData.professions
                .filter((row) => row.name !== '')
                .map((row) => ({
                    name: row.name,
                    skill_level: row.skill_level === '' ? null : Number(row.skill_level),
                })),
        }));

        if (method === 'put') {
            put(action);
        } else {
            post(action);
        }
    };

    const racesForFaction = options.races.filter((race) => race.faction === data.faction);

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

    function addProfession() {
        setData('professions', [...data.professions, { name: '', skill_level: '' }]);
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
            data.professions.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );
    }

    return (
        <form onSubmit={submit} className="space-y-6">
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
                    <select
                        id="faction"
                        value={data.faction}
                        onChange={(e) => updateFaction(e.target.value)}
                        className={selectClassName}
                    >
                        {options.factions.map((faction) => (
                            <option key={faction.value} value={faction.value}>
                                {faction.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.faction} />
                </div>

                <div>
                    <InputLabel htmlFor="class">Classe</InputLabel>
                    <select
                        id="class"
                        value={data.class}
                        onChange={(e) => setData('class', e.target.value)}
                        className={selectClassName}
                    >
                        {options.classes.map((classOption) => (
                            <option key={classOption.value} value={classOption.value}>
                                {classOption.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.class} />
                </div>

                <div>
                    <InputLabel htmlFor="spec">Especialização</InputLabel>
                    <TextInput
                        id="spec"
                        placeholder="Ex.: Arcano, Furtividade, Sombra..."
                        value={data.spec}
                        onChange={(e) => setData('spec', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.spec} />
                </div>

                <div>
                    <InputLabel htmlFor="race">Raça</InputLabel>
                    <select
                        id="race"
                        value={data.race}
                        onChange={(e) => setData('race', e.target.value)}
                        className={selectClassName}
                    >
                        <option value="">Não informar</option>
                        {racesForFaction.map((race) => (
                            <option key={race.value} value={race.value}>
                                {race.label}
                            </option>
                        ))}
                    </select>
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

            <div>
                <div className="mb-2 flex items-center justify-between">
                    <InputLabel>Profissões</InputLabel>
                    <span className="text-xs text-parchment-300">{primaryCount}/2 primárias</span>
                </div>

                <div className="space-y-2">
                    {data.professions.map((row, index) => (
                        <div key={index} className="flex gap-2">
                            <select
                                value={row.name}
                                onChange={(e) => updateProfession(index, { name: e.target.value })}
                                className="flex-1 rounded-md border border-tavern-700 bg-tavern-950 px-3 py-2 text-sm text-parchment-100 focus:border-parchment-300 focus:outline-none"
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
                                        {profession.isPrimary ? '' : ' (secundária)'}
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
                                    updateProfession(index, { skill_level: e.target.value })
                                }
                                className="w-24"
                            />

                            <button
                                type="button"
                                onClick={() => removeProfession(index)}
                                className="rounded-md border border-tavern-700 px-3 text-sm text-parchment-300 hover:text-horde"
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
                        className="mt-2 text-sm font-medium text-parchment-100 underline"
                    >
                        + Adicionar profissão
                    </button>
                )}

                <InputError message={errors.professions} />
            </div>

            <button
                type="submit"
                disabled={processing}
                className="rounded-md bg-alliance px-5 py-2 font-medium text-white transition hover:bg-alliance-dim disabled:opacity-50"
            >
                {submitLabel}
            </button>
        </form>
    );
}

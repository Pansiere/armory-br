import EmblemIcon from '@/components/emblem-icon';
import FactionColumn from '@/components/faction-column';
import AppLayout from '@/layouts/app-layout';
import type { Character, EquipmentSlotOption } from '@/types/character';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Sortable from 'sortablejs';

type Lists = { alliance: Character[]; horde: Character[] };

function idsOf(container: HTMLElement | null): number[] {
    if (!container) {
        return [];
    }

    return Array.from(container.children)
        .map((child) => Number((child as HTMLElement).dataset.id))
        .filter((id) => !Number.isNaN(id));
}

function reorder(
    current: Lists,
    allianceIds: number[],
    hordeIds: number[],
): Lists {
    const byId = new Map(
        [...current.alliance, ...current.horde].map((character) => [
            character.id,
            character,
        ]),
    );

    const resolve = (ids: number[]) =>
        ids
            .map((id) => byId.get(id))
            .filter((character): character is Character => !!character);

    return { alliance: resolve(allianceIds), horde: resolve(hordeIds) };
}

export default function Dashboard({
    alliance,
    horde,
    equipmentSlots,
}: {
    alliance: Character[];
    horde: Character[];
    equipmentSlots: EquipmentSlotOption[];
}) {
    const [lists, setLists] = useState<Lists>({ alliance, horde });
    const allianceRef = useRef<HTMLDivElement>(null);
    const hordeRef = useRef<HTMLDivElement>(null);

    // Novas props do servidor (após navegação ou after o autosave) são a
    // fonte da verdade.
    useEffect(() => {
        setLists({ alliance, horde });
    }, [alliance, horde]);

    useEffect(() => {
        const containers = [allianceRef.current, hordeRef.current].filter(
            (el): el is HTMLDivElement => el !== null,
        );

        const onEnd = (event: Sortable.SortableEvent) => {
            const newAlliance = idsOf(allianceRef.current);
            const newHorde = idsOf(hordeRef.current);

            // O SortableJS já moveu o nó real no DOM, por fora do React.
            // Desfaz esse movimento aqui — a partir daqui só o React mexe no
            // DOM (via setLists), senão a próxima reconciliação tenta remover
            // um nó que já não está mais no container original e quebra.
            const { item, from, oldIndex } = event;
            item.parentElement?.removeChild(item);
            if (oldIndex !== undefined && oldIndex < from.children.length) {
                from.insertBefore(item, from.children[oldIndex]);
            } else {
                from.appendChild(item);
            }

            setLists((current) => reorder(current, newAlliance, newHorde));

            router.patch(
                '/characters/reorder',
                { alliance: newAlliance, horde: newHorde },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onError: () =>
                        router.reload({ only: ['alliance', 'horde'] }),
                },
            );
        };

        const instances = containers.map(
            (container) =>
                new Sortable(container, {
                    group: 'characters',
                    animation: 150,
                    delay: 150,
                    delayOnTouchOnly: true,
                    filter: '.sortable-ignore',
                    preventOnFilter: false,
                    ghostClass: 'opacity-40',
                    onEnd,
                }),
        );

        return () => instances.forEach((instance) => instance.destroy());
    }, [lists]);

    const total = lists.alliance.length + lists.horde.length;
    const itemLevels = [...lists.alliance, ...lists.horde]
        .map((character) => character.average_item_level)
        .filter((level): level is number => level != null);
    const averageItemLevel =
        itemLevels.length > 0
            ? Math.round(
                  itemLevels.reduce((sum, level) => sum + level, 0) /
                      itemLevels.length,
              )
            : null;

    const newCharacterButton = (
        <Link
            href="/characters/create"
            className="group border-parchment-300/30 bg-tavern-900 font-heading text-parchment-100 hover:border-parchment-300/60 hover:bg-tavern-800 inline-flex shrink-0 items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold tracking-wide shadow transition"
        >
            <span className="text-parchment-300 group-hover:text-parchment-100 text-lg leading-none transition">
                +
            </span>
            Novo personagem
        </Link>
    );

    return (
        <AppLayout>
            <Head title="Meus personagens" />

            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="font-heading text-parchment-100 text-2xl font-bold">
                        Meus personagens
                    </h1>
                    <p className="text-parchment-300 mt-1 text-sm">
                        {total === 0
                            ? 'Nenhum personagem cadastrado ainda.'
                            : `${total} personagem${total === 1 ? '' : 's'}${
                                  averageItemLevel != null
                                      ? ` · ilvl médio ${averageItemLevel}`
                                      : ''
                              }`}
                    </p>
                </div>

                {total > 0 && newCharacterButton}
            </div>

            {total === 0 ? (
                <div className="border-tavern-700 flex flex-col items-center gap-4 rounded-lg border border-dashed px-6 py-16 text-center">
                    <EmblemIcon className="text-tavern-700 h-16 w-16" />
                    <div>
                        <p className="font-heading text-parchment-100 text-lg font-semibold">
                            Sua vitrine está vazia
                        </p>
                        <p className="text-parchment-300 mt-1 max-w-sm text-sm">
                            Cadastre seu primeiro personagem pra começar a
                            montar o boneco de equipamento e organizar sua conta
                            por facção.
                        </p>
                    </div>
                    {newCharacterButton}
                </div>
            ) : (
                <div className="flex flex-col gap-6 md:flex-row">
                    <FactionColumn
                        faction="alliance"
                        characters={lists.alliance}
                        listRef={allianceRef}
                        equipmentSlots={equipmentSlots}
                    />
                    <div className="bg-tavern-700 h-px md:hidden" />
                    <div className="bg-tavern-700 hidden w-px md:block" />
                    <FactionColumn
                        faction="horde"
                        characters={lists.horde}
                        listRef={hordeRef}
                        equipmentSlots={equipmentSlots}
                    />
                </div>
            )}
        </AppLayout>
    );
}

import FactionColumn from '@/components/faction-column';
import AppLayout from '@/layouts/app-layout';
import type { Character } from '@/types/character';
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

function reorder(current: Lists, allianceIds: number[], hordeIds: number[]): Lists {
    const byId = new Map(
        [...current.alliance, ...current.horde].map((character) => [character.id, character]),
    );

    const resolve = (ids: number[]) =>
        ids.map((id) => byId.get(id)).filter((character): character is Character => !!character);

    return { alliance: resolve(allianceIds), horde: resolve(hordeIds) };
}

export default function Dashboard({
    alliance,
    horde,
}: {
    alliance: Character[];
    horde: Character[];
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
                    onError: () => router.reload({ only: ['alliance', 'horde'] }),
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

    return (
        <AppLayout>
            <Head title="Meus personagens" />

            <div className="flex flex-col gap-6 md:flex-row">
                <FactionColumn faction="alliance" characters={lists.alliance} listRef={allianceRef} />
                <div className="h-px bg-tavern-700 md:hidden" />
                <div className="hidden w-px bg-tavern-700 md:block" />
                <FactionColumn faction="horde" characters={lists.horde} listRef={hordeRef} />
            </div>

            <div className="mt-6 text-center">
                <Link
                    href="/characters/create"
                    className="inline-block rounded-md bg-alliance px-4 py-2 font-medium text-white transition hover:bg-alliance-dim"
                >
                    + Novo personagem
                </Link>
            </div>
        </AppLayout>
    );
}

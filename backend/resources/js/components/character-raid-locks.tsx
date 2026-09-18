import { cn } from '@/lib/utils';
import type { RaidLock, RaidOption } from '@/types/character';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState, type MouseEvent } from 'react';

const SIZES = [10, 25] as const;

function formatResetDate(iso: string): string {
    return new Date(iso).toLocaleString('pt-BR', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Badges de CD de raid no card do dashboard — um por raid (ICC/RS/ToC/VoA),
 * sempre visíveis (mesmo sem CD marcado) pra que o toggle seja descobrível.
 * Cada badge abre um popover com 10 e 25-man, e cada tamanho cicla
 * Livre → Normal → Heroico → Livre num só clique, já que 10 e 25 são
 * lockouts independentes mas Normal/Heroico do mesmo tamanho compartilham
 * um só (não dá pra ter os dois ao mesmo tempo no mesmo tamanho).
 */
export default function CharacterRaidLocks({
    characterId,
    raids,
    raidLocks,
}: {
    characterId: number;
    raids: RaidOption[];
    raidLocks: RaidLock[];
}) {
    const [openRaid, setOpenRaid] = useState<string | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!openRaid) {
            return;
        }

        function onMouseDown(event: globalThis.MouseEvent) {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpenRaid(null);
            }
        }

        document.addEventListener('mousedown', onMouseDown);
        return () => document.removeEventListener('mousedown', onMouseDown);
    }, [openRaid]);

    function stop(event: MouseEvent) {
        event.preventDefault();
        event.stopPropagation();
    }

    function locksFor(raidValue: string) {
        return raidLocks
            .filter((lock) => lock.raid === raidValue)
            .sort((a, b) => a.size - b.size);
    }

    function cycle(
        raidValue: string,
        size: 10 | 25,
        current: RaidLock | undefined,
    ) {
        setOpenRaid(null);

        if (!current) {
            router.put(
                `/characters/${characterId}/raid-locks/${raidValue}/${size}`,
                { heroic: false },
                { preserveScroll: true, preserveState: true },
            );
            return;
        }

        if (!current.heroic) {
            router.put(
                `/characters/${characterId}/raid-locks/${raidValue}/${size}`,
                { heroic: true },
                { preserveScroll: true, preserveState: true },
            );
            return;
        }

        router.delete(
            `/characters/${characterId}/raid-locks/${raidValue}/${size}`,
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    return (
        <div
            ref={containerRef}
            className="sortable-ignore relative flex flex-wrap items-center gap-1"
        >
            {raids.map((raid) => {
                const locks = locksFor(raid.value);
                const isOpen = openRaid === raid.value;

                return (
                    <div key={raid.value} className="relative">
                        <button
                            type="button"
                            onClick={(event) => {
                                stop(event);
                                setOpenRaid(isOpen ? null : raid.value);
                            }}
                            title={
                                locks.length > 0
                                    ? `CD até ${formatResetDate(locks[0].locked_until)}`
                                    : `Sem CD em ${raid.label}`
                            }
                            className={cn(
                                'rounded px-1.5 py-0.5 text-[10px] font-semibold',
                                locks.length > 0
                                    ? 'bg-tavern-900 text-parchment-100'
                                    : 'bg-tavern-900/30 text-parchment-300/50',
                            )}
                        >
                            {raid.shortLabel}
                            {locks.length > 0 && (
                                <span className="ml-1 font-normal">
                                    {locks
                                        .map(
                                            (lock) =>
                                                `${lock.size}${lock.heroic ? 'H' : ''}`,
                                        )
                                        .join('/')}
                                </span>
                            )}
                        </button>

                        {isOpen && (
                            <div
                                onClick={stop}
                                className="border-tavern-700 bg-tavern-950 absolute top-full left-0 z-10 mt-1 w-36 rounded border p-2 shadow-xl"
                            >
                                <p className="text-parchment-100 mb-1.5 text-xs font-semibold">
                                    {raid.label}
                                </p>
                                {SIZES.map((size) => {
                                    const lock = locks.find(
                                        (candidate) => candidate.size === size,
                                    );

                                    return (
                                        <button
                                            key={size}
                                            type="button"
                                            onClick={(event) => {
                                                stop(event);
                                                cycle(raid.value, size, lock);
                                            }}
                                            className="hover:bg-tavern-800 flex w-full items-center justify-between rounded px-1.5 py-1 text-xs"
                                        >
                                            <span className="text-parchment-300">
                                                {size}
                                            </span>
                                            <span
                                                className={cn(
                                                    'font-semibold',
                                                    !lock &&
                                                        'text-parchment-300/40',
                                                    lock &&
                                                        !lock.heroic &&
                                                        'text-parchment-100',
                                                    lock?.heroic &&
                                                        'text-amber-400',
                                                )}
                                            >
                                                {lock
                                                    ? lock.heroic
                                                        ? 'Heroico'
                                                        : 'Normal'
                                                    : 'Livre'}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

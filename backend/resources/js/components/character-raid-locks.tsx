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
 * Cada badge abre um popover com 10 e 25-man, cada um com dois botões
 * explícitos N/H (em vez de um clique só ciclando os estados, que não deixa
 * claro que dá pra escolher heroico direto) — clicar no que já está ativo
 * desmarca. 10 e 25 são lockouts independentes, mas Normal/Heroico do mesmo
 * tamanho compartilham um só (não dá pra ter os dois ao mesmo tempo).
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

    function setMode(
        raidValue: string,
        size: 10 | 25,
        current: RaidLock | undefined,
        heroic: boolean,
    ) {
        // Clicar no modo que já está ativo desmarca (volta a "Livre") —
        // senão não teria como limpar um CD marcado por engano sem esperar
        // o reset.
        if (current && current.heroic === heroic) {
            router.delete(
                `/characters/${characterId}/raid-locks/${raidValue}/${size}`,
                { preserveScroll: true, preserveState: true },
            );
            return;
        }

        router.put(
            `/characters/${characterId}/raid-locks/${raidValue}/${size}`,
            { heroic },
            { preserveScroll: true, preserveState: true },
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
                                                `${lock.size}${lock.heroic ? 'H' : 'N'}`,
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
                                        <div
                                            key={size}
                                            className="flex items-center justify-between gap-2 py-0.5"
                                        >
                                            <span className="text-parchment-300 text-xs">
                                                {size}
                                            </span>
                                            <div className="flex gap-1">
                                                <button
                                                    type="button"
                                                    title="Normal"
                                                    onClick={(event) => {
                                                        stop(event);
                                                        setMode(
                                                            raid.value,
                                                            size,
                                                            lock,
                                                            false,
                                                        );
                                                    }}
                                                    className={cn(
                                                        'rounded px-1.5 py-0.5 text-[10px] font-bold',
                                                        lock && !lock.heroic
                                                            ? 'bg-parchment-100 text-tavern-950'
                                                            : 'bg-tavern-800 text-parchment-300/50 hover:text-parchment-100',
                                                    )}
                                                >
                                                    N
                                                </button>
                                                <button
                                                    type="button"
                                                    title="Heroico"
                                                    onClick={(event) => {
                                                        stop(event);
                                                        setMode(
                                                            raid.value,
                                                            size,
                                                            lock,
                                                            true,
                                                        );
                                                    }}
                                                    className={cn(
                                                        'rounded px-1.5 py-0.5 text-[10px] font-bold',
                                                        lock?.heroic
                                                            ? 'text-tavern-950 bg-amber-400'
                                                            : 'bg-tavern-800 text-parchment-300/50 hover:text-parchment-100',
                                                    )}
                                                >
                                                    H
                                                </button>
                                            </div>
                                        </div>
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

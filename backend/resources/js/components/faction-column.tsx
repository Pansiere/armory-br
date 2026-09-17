import CharacterCard from '@/components/character-card';
import { cn } from '@/lib/utils';
import type { Character, Faction } from '@/types/character';
import { Link } from '@inertiajs/react';
import type { RefObject } from 'react';

const FACTION_META: Record<
    Faction,
    { label: string; border: string; wash: string; text: string; badge: string }
> = {
    alliance: {
        label: 'Aliança',
        border: 'border-alliance',
        wash: 'bg-alliance/15',
        text: 'text-alliance',
        badge: 'bg-alliance',
    },
    horde: {
        label: 'Horda',
        border: 'border-horde',
        wash: 'bg-horde/15',
        text: 'text-horde',
        badge: 'bg-horde',
    },
};

export default function FactionColumn({
    faction,
    characters,
    listRef,
}: {
    faction: Faction;
    characters: Character[];
    listRef: RefObject<HTMLDivElement | null>;
}) {
    const meta = FACTION_META[faction];

    return (
        <section className={cn('flex-1 rounded-lg border-t-4', meta.border, meta.wash)}>
            <header className="flex items-center justify-between px-4 py-3">
                <h2 className={cn('flex items-center gap-2 font-heading text-lg font-bold', meta.text)}>
                    <svg viewBox="0 0 24 24" aria-hidden="true" className="h-4 w-4 fill-current">
                        <path d="M12 2 C8 2 5 3.4 5 5.6 L5 11 C5 16 8 19.6 12 21.5 C16 19.6 19 16 19 11 L19 5.6 C19 3.4 16 2 12 2 Z" />
                    </svg>
                    {meta.label}
                </h2>
                <span
                    className={cn(
                        'flex h-7 min-w-7 items-center justify-center rounded-full px-2 text-sm font-semibold text-white',
                        meta.badge,
                    )}
                >
                    {characters.length}
                </span>
            </header>

            <div ref={listRef} data-faction={faction} className="flex min-h-24 flex-col gap-2 px-3 pb-4">
                {characters.map((character) => (
                    <CharacterCard key={character.id} character={character} />
                ))}

                {characters.length === 0 && (
                    <Link
                        href="/characters/create"
                        className="sortable-ignore rounded-md border border-dashed border-tavern-700 px-4 py-8 text-center text-sm text-parchment-300 transition hover:border-parchment-300 hover:text-parchment-100"
                    >
                        Nenhum personagem {faction === 'alliance' ? 'da Aliança' : 'da Horda'}{' '}
                        ainda.
                        <br />
                        <span className="font-medium underline">Cadastrar personagem</span>
                    </Link>
                )}
            </div>
        </section>
    );
}

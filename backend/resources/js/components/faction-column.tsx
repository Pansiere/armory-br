import CharacterCard from '@/components/character-card';
import FactionIcon from '@/components/faction-icon';
import { cn } from '@/lib/utils';
import type {
    Character,
    EquipmentSlotOption,
    Faction,
} from '@/types/character';
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
    equipmentSlots,
}: {
    faction: Faction;
    characters: Character[];
    listRef: RefObject<HTMLDivElement | null>;
    equipmentSlots: EquipmentSlotOption[];
}) {
    const meta = FACTION_META[faction];

    return (
        <section
            className={cn(
                'flex-1 rounded-lg border-t-4',
                meta.border,
                meta.wash,
            )}
        >
            <header className="flex items-center justify-between px-4 py-3">
                <h2
                    className={cn(
                        'font-heading flex items-center gap-2 text-lg font-bold',
                        meta.text,
                    )}
                >
                    <FactionIcon className="h-4 w-4" />
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

            <div
                ref={listRef}
                data-faction={faction}
                className="flex min-h-24 flex-col gap-2 px-3 pb-4"
            >
                {characters.map((character) => (
                    <CharacterCard
                        key={character.id}
                        character={character}
                        equipmentSlots={equipmentSlots}
                    />
                ))}

                {characters.length === 0 && (
                    <Link
                        href="/characters/create"
                        className="sortable-ignore border-tavern-700 text-parchment-300 hover:border-parchment-300 hover:text-parchment-100 rounded-md border border-dashed px-4 py-8 text-center text-sm transition"
                    >
                        Nenhum personagem{' '}
                        {faction === 'alliance' ? 'da Aliança' : 'da Horda'}{' '}
                        ainda.
                        <br />
                        <span className="font-medium underline">
                            Cadastrar personagem
                        </span>
                    </Link>
                )}
            </div>
        </section>
    );
}

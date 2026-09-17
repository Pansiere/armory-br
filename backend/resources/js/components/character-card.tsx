import { cn } from '@/lib/utils';
import type { Character } from '@/types/character';
import { Link } from '@inertiajs/react';

export default function CharacterCard({ character }: { character: Character }) {
    const initial = character.name.charAt(0).toUpperCase();

    return (
        <Link
            href={`/characters/${character.id}/edit`}
            data-id={character.id}
            className="group flex cursor-grab items-center gap-3 rounded-md border-l-4 bg-parchment-100 p-3 text-ink shadow transition hover:bg-parchment-200 active:cursor-grabbing"
            style={{ borderLeftColor: character.class_color }}
        >
            <span
                className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-heading text-lg font-bold text-ink"
                style={{ backgroundColor: character.class_color }}
            >
                {initial}
            </span>

            <div className="min-w-0">
                <p className="truncate font-medium">
                    {character.name}
                    {character.level ? (
                        <span className="text-ink/60">
                            {' '}
                            ({character.level}
                            {character.average_item_level != null
                                ? ` · ilvl ${character.average_item_level}`
                                : ''}
                            )
                        </span>
                    ) : (
                        ''
                    )}
                </p>
                <p
                    className={cn(
                        'truncate text-sm font-medium',
                        character.class_needs_text_outline &&
                            '[text-shadow:0_0_3px_rgba(0,0,0,0.7)]',
                    )}
                    style={{ color: character.class_color }}
                >
                    {character.class_label}
                    {character.spec ? ` · ${character.spec}` : ''}
                </p>
                {character.professions.length > 0 && (
                    <p className="truncate text-xs text-ink/60">
                        {character.professions.map((profession) => profession.label).join(' · ')}
                    </p>
                )}
            </div>
        </Link>
    );
}

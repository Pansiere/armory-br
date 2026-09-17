import { cn } from '@/lib/utils';
import type { Character } from '@/types/character';
import { Link } from '@inertiajs/react';

export default function CharacterCard({ character }: { character: Character }) {
    const initial = character.name.charAt(0).toUpperCase();

    const weapon = character.equipment.find(
        (equipment) => equipment.slot === 'main_hand' || equipment.slot === 'ranged',
    )?.item;

    return (
        <Link
            href={`/characters/${character.id}/edit`}
            data-id={character.id}
            className="group flex cursor-grab items-center gap-3 rounded-md border-l-4 bg-parchment-100 p-3 text-ink shadow transition hover:-translate-y-0.5 hover:bg-parchment-200 hover:shadow-md active:cursor-grabbing active:translate-y-0"
            style={{ borderLeftColor: character.class_color }}
        >
            <span className="relative shrink-0">
                <span
                    className="flex h-10 w-10 items-center justify-center rounded-full font-heading text-lg font-bold text-ink"
                    style={{
                        backgroundColor: character.class_color,
                        boxShadow: `0 0 10px -2px ${character.class_color}`,
                    }}
                >
                    {initial}
                </span>
                {weapon?.icon_url && (
                    <span
                        className="absolute -right-1.5 -bottom-1.5 flex h-5 w-5 items-center justify-center rounded-sm bg-parchment-100 p-0.5"
                        style={{ boxShadow: `0 0 0 1.5px ${weapon.quality_color}, 0 1px 3px rgba(0,0,0,0.4)` }}
                    >
                        <img src={weapon.icon_url} alt="" className="h-full w-full rounded-[1px]" />
                    </span>
                )}
            </span>

            <div className="min-w-0 flex-1">
                <p className="flex items-center gap-1.5 truncate font-medium">
                    <span className="truncate">{character.name}</span>
                    {character.is_public && (
                        <span
                            title="Perfil público ativo"
                            className="shrink-0 text-ink/40 group-hover:text-alliance"
                        >
                            <svg
                                viewBox="0 0 16 16"
                                aria-hidden="true"
                                className="h-3 w-3 fill-none stroke-current"
                                strokeWidth="1.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M6.5 9.5 9.5 6.5M7 5l.7-.7a2.5 2.5 0 0 1 3.5 3.5L10.5 8.5M9 11l-.7.7a2.5 2.5 0 0 1-3.5-3.5L5.5 7.5" />
                            </svg>
                        </span>
                    )}
                    {character.level != null && (
                        <span className="shrink-0 text-xs font-normal text-ink/60">
                            Nv. {character.level}
                        </span>
                    )}
                    {character.average_item_level != null && (
                        <span className="shrink-0 rounded-full bg-tavern-900 px-1.5 py-0.5 text-[10px] font-semibold text-parchment-200">
                            ilvl {character.average_item_level}
                        </span>
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

import CharacterEquipmentModal from '@/components/character-equipment-modal';
import EquipmentMiniature from '@/components/equipment-miniature';
import { cn } from '@/lib/utils';
import type { Character, EquipmentSlotOption } from '@/types/character';
import { Link } from '@inertiajs/react';
import { useState, type MouseEvent } from 'react';

export default function CharacterCard({
    character,
    equipmentSlots,
}: {
    character: Character;
    equipmentSlots: EquipmentSlotOption[];
}) {
    const [expanded, setExpanded] = useState(false);

    const primarySpec = character.specs[0];
    const secondarySpec = character.specs[1];

    const weapon = primarySpec?.equipment.find(
        (equipment) =>
            equipment.slot === 'main_hand' || equipment.slot === 'ranged',
    )?.item;

    function openExpanded(event: MouseEvent) {
        event.preventDefault();
        event.stopPropagation();
        setExpanded(true);
    }

    return (
        <Link
            href={`/characters/${character.id}/edit`}
            data-id={character.id}
            className="group bg-parchment-100 text-ink hover:bg-parchment-200 flex cursor-grab items-center gap-3 rounded-md border-l-4 p-3 shadow transition hover:-translate-y-0.5 hover:shadow-md active:translate-y-0 active:cursor-grabbing"
            style={{ borderLeftColor: character.class_color }}
        >
            <span className="relative shrink-0">
                <span
                    className="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full"
                    style={{
                        backgroundColor: character.class_color,
                        boxShadow: `0 0 10px -2px ${character.class_color}`,
                    }}
                >
                    <img
                        src={character.class_icon_url}
                        alt=""
                        title={character.class_label}
                        className="h-full w-full object-cover"
                    />
                </span>
                {weapon?.icon_url && (
                    <span
                        className="bg-parchment-100 absolute -right-1.5 -bottom-1.5 flex h-5 w-5 items-center justify-center rounded-sm p-0.5"
                        style={{
                            boxShadow: `0 0 0 1.5px ${weapon.quality_color}, 0 1px 3px rgba(0,0,0,0.4)`,
                        }}
                    >
                        <img
                            src={weapon.icon_url}
                            alt=""
                            className="h-full w-full rounded-[1px]"
                        />
                    </span>
                )}
            </span>

            <div className="min-w-0 flex-1">
                <p className="flex items-center gap-1.5 truncate font-medium">
                    <span className="truncate">{character.name}</span>
                    {character.is_public && (
                        <span
                            title="Perfil público ativo"
                            className="text-ink/40 group-hover:text-alliance shrink-0"
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
                        <span className="text-ink/60 shrink-0 text-xs font-normal">
                            Nv. {character.level}
                        </span>
                    )}
                    {character.average_item_level != null && (
                        <span className="bg-tavern-900 text-parchment-200 shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold">
                            ilvl {character.average_item_level}
                        </span>
                    )}
                </p>
                <p
                    className={cn(
                        'flex items-center gap-1 truncate text-sm font-medium',
                        character.class_needs_text_outline &&
                            '[text-shadow:0_0_3px_rgba(0,0,0,0.7)]',
                    )}
                    style={{ color: character.class_color }}
                >
                    {character.race_icon_url && (
                        <span className="flex shrink-0 items-center gap-1">
                            <img
                                src={character.race_icon_url}
                                alt=""
                                className="h-3.5 w-3.5 rounded-sm"
                            />
                            <span>{character.race_label}</span>
                            <span aria-hidden="true">·</span>
                        </span>
                    )}
                    <span className="truncate">{character.class_label}</span>
                    {primarySpec && (
                        <span className="flex shrink-0 items-center gap-1">
                            <span aria-hidden="true">·</span>
                            <img
                                src={primarySpec.icon_url}
                                alt=""
                                className="h-3.5 w-3.5 rounded-sm"
                            />
                            <span className="truncate">
                                {primarySpec.label}
                            </span>
                        </span>
                    )}
                    {secondarySpec && (
                        <span className="flex shrink-0 items-center gap-1">
                            <span aria-hidden="true">/</span>
                            <img
                                src={secondarySpec.icon_url}
                                alt=""
                                className="h-3.5 w-3.5 rounded-sm"
                            />
                            <span className="truncate">
                                {secondarySpec.label}
                            </span>
                        </span>
                    )}
                </p>
                {character.professions.length > 0 && (
                    <p className="text-ink/60 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs">
                        {character.professions.map((profession) => (
                            <span
                                key={profession.id}
                                className="flex shrink-0 items-center gap-1"
                            >
                                <img
                                    src={profession.icon_url}
                                    alt=""
                                    className="h-3.5 w-3.5 rounded-sm"
                                />
                                {profession.label}
                            </span>
                        ))}
                    </p>
                )}
                {primarySpec && (
                    <EquipmentMiniature equipment={primarySpec.equipment} />
                )}
            </div>

            <button
                type="button"
                onClick={openExpanded}
                title="Ver equipamento completo"
                className="sortable-ignore text-ink/40 hover:text-ink shrink-0 self-start rounded p-1"
            >
                <svg
                    viewBox="0 0 16 16"
                    aria-hidden="true"
                    className="h-4 w-4 fill-none stroke-current"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <path d="M6 2H2v4M10 2h4v4M6 14H2v-4M10 14h4v-4" />
                </svg>
            </button>

            {expanded && (
                <CharacterEquipmentModal
                    character={character}
                    slots={equipmentSlots}
                    onClose={() => setExpanded(false)}
                />
            )}
        </Link>
    );
}

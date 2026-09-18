import ItemSearchInput from '@/components/item-search-input';
import ItemTooltip from '@/components/item-tooltip';
import { cn } from '@/lib/utils';
import type {
    Character,
    CharacterEquipment,
    EquipmentSlotOption,
    ItemSummary,
    SocketedGem,
} from '@/types/character';
import { router } from '@inertiajs/react';
import { useState } from 'react';

// Mesma paleta de App\Enums\GemColor::hex() — bitmask 1=meta, 2=vermelho,
// 4=amarelo, 8=azul.
const GEM_COLOR_HEX: Record<number, string> = {
    1: '#8a8a8a',
    2: '#c0392b',
    4: '#e1c542',
    8: '#2f7bc0',
};

export default function EquipmentDoll({
    character,
    slots,
    activeSpecIndex,
    onSpecChange,
    readOnly = false,
}: {
    character: Character;
    slots: EquipmentSlotOption[];
    activeSpecIndex: number;
    onSpecChange: (index: number) => void;
    /**
     * Modo de exibição (ex.: modal do card na dashboard, issue #20) — sem
     * clique pra trocar item/gema, sem botão de desequipar. A tooltip
     * (hover/toque longo) continua funcionando normalmente.
     */
    readOnly?: boolean;
}) {
    const [activeSlot, setActiveSlot] = useState<string | null>(null);
    const [activeGemSocket, setActiveGemSocket] = useState<{
        slot: string;
        position: number;
    } | null>(null);

    const spec = character.specs[activeSpecIndex] ?? character.specs[0];

    const equipped = new Map<string, CharacterEquipment>(
        (spec?.equipment ?? []).map((equipment) => [equipment.slot, equipment]),
    );

    function switchSpec(index: number) {
        onSpecChange(index);
        setActiveSlot(null);
        setActiveGemSocket(null);
    }

    function equip(slot: string, item: ItemSummary) {
        setActiveSlot(null);
        router.put(
            `/characters/${character.id}/equipment/${spec.value}/${slot}`,
            { item_id: item.id },
            { preserveScroll: true, preserveState: true },
        );
    }

    function unequip(slot: string) {
        router.delete(
            `/characters/${character.id}/equipment/${spec.value}/${slot}`,
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }

    function equipGem(slot: string, position: number, gem: ItemSummary) {
        setActiveGemSocket(null);
        router.put(
            `/characters/${character.id}/equipment/${spec.value}/${slot}/gems/${position}`,
            { item_id: gem.id },
            { preserveScroll: true, preserveState: true },
        );
    }

    function unequipGem(slot: string, position: number) {
        router.delete(
            `/characters/${character.id}/equipment/${spec.value}/${slot}/gems/${position}`,
            { preserveScroll: true, preserveState: true },
        );
    }

    function renderItemLabel(item: ItemSummary) {
        return (
            <ItemTooltip itemId={item.id} qualityColor={item.quality_color}>
                {item.icon_url && (
                    <img
                        src={item.icon_url}
                        alt=""
                        className="h-7 w-7 shrink-0 rounded-sm border"
                        style={{ borderColor: item.quality_color }}
                    />
                )}
                <span
                    className="truncate"
                    style={{ color: item.quality_color }}
                >
                    {item.name}
                </span>
            </ItemTooltip>
        );
    }

    function renderGemBadge(item: SocketedGem['item']) {
        return (
            item.icon_url && (
                <ItemTooltip itemId={item.id} qualityColor={item.quality_color}>
                    <img
                        src={item.icon_url}
                        alt=""
                        className="h-full w-full rounded-[1px]"
                    />
                </ItemTooltip>
            )
        );
    }

    function renderSlot(slotOption: EquipmentSlotOption) {
        const equipment = equipped.get(slotOption.value);
        const item = equipment?.item;

        if (readOnly) {
            return (
                <div key={slotOption.value} className="relative">
                    <div
                        className={cn(
                            'bg-tavern-900 flex w-full items-center gap-2 rounded-md border-l-4 py-2 pr-3 pl-3 text-left text-sm',
                            !item && 'border-tavern-700 border-dashed',
                        )}
                        style={
                            item
                                ? { borderLeftColor: item.quality_color }
                                : undefined
                        }
                    >
                        <span className="text-parchment-300 w-28 shrink-0 text-xs">
                            {slotOption.label}
                        </span>
                        {item ? (
                            renderItemLabel(item)
                        ) : (
                            <span className="text-parchment-300/50">Vazio</span>
                        )}
                    </div>

                    {item && item.socket_colors.length > 0 && (
                        <div className="mt-1 ml-3 flex gap-1">
                            {item.socket_colors.map((socketColor, index) => {
                                const position = index + 1;
                                const gem = equipment?.gems.find(
                                    (g) => g.socket_position === position,
                                );

                                return (
                                    <div
                                        key={position}
                                        title={gem?.item.name}
                                        className="flex h-5 w-5 items-center justify-center rounded-sm"
                                        style={{
                                            boxShadow: `0 0 0 1.5px ${GEM_COLOR_HEX[socketColor] ?? '#574632'}`,
                                            backgroundColor: gem
                                                ? undefined
                                                : 'rgba(0,0,0,0.3)',
                                        }}
                                    >
                                        {gem && renderGemBadge(gem.item)}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            );
        }

        return (
            <div key={slotOption.value} className="relative">
                <button
                    type="button"
                    onClick={() =>
                        setActiveSlot(
                            activeSlot === slotOption.value
                                ? null
                                : slotOption.value,
                        )
                    }
                    className={cn(
                        'bg-tavern-900 hover:bg-tavern-800 flex w-full items-center gap-2 rounded-md border-l-4 py-2 pr-7 pl-3 text-left text-sm transition',
                        !item && 'border-tavern-700 border-dashed',
                    )}
                    style={
                        item
                            ? { borderLeftColor: item.quality_color }
                            : undefined
                    }
                >
                    <span className="text-parchment-300 w-28 shrink-0 text-xs">
                        {slotOption.label}
                    </span>
                    {item ? (
                        renderItemLabel(item)
                    ) : (
                        <span className="text-parchment-300/50">Vazio</span>
                    )}
                </button>

                {item && activeSlot !== slotOption.value && (
                    <button
                        type="button"
                        onClick={() => unequip(slotOption.value)}
                        title="Desequipar"
                        className="text-parchment-300 hover:text-horde absolute top-1/2 right-2 -translate-y-1/2"
                    >
                        ×
                    </button>
                )}

                {activeSlot === slotOption.value && (
                    <ItemSearchInput
                        target={{ slot: slotOption.value }}
                        onSelect={(selected) =>
                            equip(slotOption.value, selected)
                        }
                        onCancel={() => setActiveSlot(null)}
                    />
                )}

                {item && item.socket_colors.length > 0 && (
                    <div className="mt-1 ml-3 flex gap-1">
                        {item.socket_colors.map((socketColor, index) => {
                            const position = index + 1;
                            const gem = equipment?.gems.find(
                                (g) => g.socket_position === position,
                            );
                            const isActive =
                                activeGemSocket?.slot === slotOption.value &&
                                activeGemSocket.position === position;

                            return (
                                <div key={position} className="relative">
                                    <button
                                        type="button"
                                        title={gem ? undefined : 'Socket vazio'}
                                        onClick={() =>
                                            setActiveGemSocket(
                                                isActive
                                                    ? null
                                                    : {
                                                          slot: slotOption.value,
                                                          position,
                                                      },
                                            )
                                        }
                                        className="flex h-5 w-5 items-center justify-center rounded-sm"
                                        style={{
                                            boxShadow: `0 0 0 1.5px ${GEM_COLOR_HEX[socketColor] ?? '#574632'}`,
                                            backgroundColor: gem
                                                ? undefined
                                                : 'rgba(0,0,0,0.3)',
                                        }}
                                    >
                                        {gem && renderGemBadge(gem.item)}
                                    </button>

                                    {gem && !isActive && (
                                        <button
                                            type="button"
                                            title="Remover gema"
                                            onClick={() =>
                                                unequipGem(
                                                    slotOption.value,
                                                    position,
                                                )
                                            }
                                            className="bg-tavern-950 text-parchment-300 hover:text-horde absolute -top-1.5 -right-1.5 flex h-3 w-3 items-center justify-center rounded-full text-[8px]"
                                        >
                                            ×
                                        </button>
                                    )}

                                    {isActive && (
                                        <ItemSearchInput
                                            target={{ gemColor: socketColor }}
                                            onSelect={(selected) =>
                                                equipGem(
                                                    slotOption.value,
                                                    position,
                                                    selected,
                                                )
                                            }
                                            onCancel={() =>
                                                setActiveGemSocket(null)
                                            }
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        );
    }

    const left = slots.filter((slot) => slot.column === 'left');
    const right = slots.filter((slot) => slot.column === 'right');
    const bottom = slots.filter((slot) => slot.column === 'bottom');

    const portrait = (
        <div className="flex flex-col items-center gap-2 py-2 lg:w-40">
            <div
                className="font-heading text-ink flex h-28 w-28 shrink-0 items-center justify-center rounded-full text-4xl font-bold"
                style={{
                    backgroundColor: character.class_color,
                    boxShadow: `0 0 28px -4px ${character.class_color}`,
                }}
            >
                {character.name.charAt(0).toUpperCase()}
            </div>
            <p
                className={cn(
                    'font-heading text-center text-lg font-bold',
                    character.class_needs_text_outline &&
                        '[text-shadow:0_0_3px_rgba(0,0,0,0.7)]',
                )}
                style={{ color: character.class_color }}
            >
                {character.name}
            </p>
            <p className="text-parchment-300 flex items-center justify-center gap-1 text-center text-xs">
                {[character.race_label, character.class_label]
                    .filter(Boolean)
                    .join(' · ')}
                {spec && (
                    <>
                        <span aria-hidden="true">·</span>
                        <img
                            src={spec.icon_url}
                            alt=""
                            className="h-3.5 w-3.5 rounded-sm"
                        />
                        {spec.label}
                    </>
                )}
            </p>
        </div>
    );

    return (
        <div>
            <div className="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="font-heading text-parchment-100 flex items-baseline gap-2 text-lg font-semibold">
                    Equipamento
                    {spec?.average_item_level != null && (
                        <span className="text-parchment-300 font-sans text-sm font-normal">
                            item level médio: {spec.average_item_level}
                        </span>
                    )}
                </h2>

                {character.specs.length > 1 && (
                    <div className="border-tavern-700 bg-tavern-900 flex gap-1 rounded-md border p-1">
                        {character.specs.map((option, index) => (
                            <button
                                key={option.id}
                                type="button"
                                onClick={() => switchSpec(index)}
                                className={cn(
                                    'flex items-center gap-1.5 rounded px-2 py-1 text-sm font-medium transition',
                                    index === activeSpecIndex
                                        ? 'bg-tavern-700 text-parchment-100'
                                        : 'text-parchment-300 hover:text-parchment-100',
                                )}
                            >
                                <img
                                    src={option.icon_url}
                                    alt=""
                                    className="h-4 w-4 rounded-sm"
                                />
                                {option.label}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <div className="mb-4 flex justify-center lg:hidden">{portrait}</div>

            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-[1fr_auto_1fr] lg:items-start lg:gap-4">
                <div className="space-y-2">{left.map(renderSlot)}</div>
                <div className="hidden lg:block">{portrait}</div>
                <div className="space-y-2">{right.map(renderSlot)}</div>
            </div>

            <div className="mt-2 grid gap-2 sm:grid-cols-3">
                {bottom.map(renderSlot)}
            </div>
        </div>
    );
}

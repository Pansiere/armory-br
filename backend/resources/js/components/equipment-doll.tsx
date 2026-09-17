import ItemSearchInput from '@/components/item-search-input';
import { cn } from '@/lib/utils';
import type { Character, EquipmentSlotOption, ItemSummary } from '@/types/character';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function EquipmentDoll({
    character,
    slots,
    activeSpecIndex,
    onSpecChange,
}: {
    character: Character;
    slots: EquipmentSlotOption[];
    activeSpecIndex: number;
    onSpecChange: (index: number) => void;
}) {
    const [activeSlot, setActiveSlot] = useState<string | null>(null);

    const spec = character.specs[activeSpecIndex] ?? character.specs[0];

    const equipped = new Map(
        (spec?.equipment ?? []).map((equipment) => [equipment.slot, equipment.item]),
    );

    function switchSpec(index: number) {
        onSpecChange(index);
        setActiveSlot(null);
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
        router.delete(`/characters/${character.id}/equipment/${spec.value}/${slot}`, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function renderSlot(slotOption: EquipmentSlotOption) {
        const item = equipped.get(slotOption.value);

        return (
            <div key={slotOption.value} className="relative">
                <button
                    type="button"
                    onClick={() =>
                        setActiveSlot(activeSlot === slotOption.value ? null : slotOption.value)
                    }
                    className={cn(
                        'flex w-full items-center gap-2 rounded-md border-l-4 bg-tavern-900 py-2 pr-7 pl-3 text-left text-sm transition hover:bg-tavern-800',
                        !item && 'border-dashed border-tavern-700',
                    )}
                    style={item ? { borderLeftColor: item.quality_color } : undefined}
                >
                    <span className="w-28 shrink-0 text-xs text-parchment-300">
                        {slotOption.label}
                    </span>
                    {item ? (
                        <>
                            {item.icon_url && (
                                <img
                                    src={item.icon_url}
                                    alt=""
                                    className="h-7 w-7 shrink-0 rounded-sm border"
                                    style={{ borderColor: item.quality_color }}
                                />
                            )}
                            <span className="truncate" style={{ color: item.quality_color }}>
                                {item.name}
                            </span>
                        </>
                    ) : (
                        <span className="text-parchment-300/50">Vazio</span>
                    )}
                </button>

                {item && activeSlot !== slotOption.value && (
                    <button
                        type="button"
                        onClick={() => unequip(slotOption.value)}
                        title="Desequipar"
                        className="absolute top-1/2 right-2 -translate-y-1/2 text-parchment-300 hover:text-horde"
                    >
                        ×
                    </button>
                )}

                {activeSlot === slotOption.value && (
                    <ItemSearchInput
                        slot={slotOption.value}
                        onSelect={(selected) => equip(slotOption.value, selected)}
                        onCancel={() => setActiveSlot(null)}
                    />
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
                className="flex h-28 w-28 shrink-0 items-center justify-center rounded-full font-heading text-4xl font-bold text-ink"
                style={{
                    backgroundColor: character.class_color,
                    boxShadow: `0 0 28px -4px ${character.class_color}`,
                }}
            >
                {character.name.charAt(0).toUpperCase()}
            </div>
            <p
                className={cn(
                    'text-center font-heading text-lg font-bold',
                    character.class_needs_text_outline && '[text-shadow:0_0_3px_rgba(0,0,0,0.7)]',
                )}
                style={{ color: character.class_color }}
            >
                {character.name}
            </p>
            <p className="flex items-center justify-center gap-1 text-center text-xs text-parchment-300">
                {[character.race_label, character.class_label].filter(Boolean).join(' · ')}
                {spec && (
                    <>
                        <span aria-hidden="true">·</span>
                        <img src={spec.icon_url} alt="" className="h-3.5 w-3.5 rounded-sm" />
                        {spec.label}
                    </>
                )}
            </p>
        </div>
    );

    return (
        <div>
            <div className="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                <h2 className="flex items-baseline gap-2 font-heading text-lg font-semibold text-parchment-100">
                    Equipamento
                    {spec?.average_item_level != null && (
                        <span className="font-sans text-sm font-normal text-parchment-300">
                            item level médio: {spec.average_item_level}
                        </span>
                    )}
                </h2>

                {character.specs.length > 1 && (
                    <div className="flex gap-1 rounded-md border border-tavern-700 bg-tavern-900 p-1">
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
                                <img src={option.icon_url} alt="" className="h-4 w-4 rounded-sm" />
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

            <div className="mt-2 grid gap-2 sm:grid-cols-3">{bottom.map(renderSlot)}</div>
        </div>
    );
}

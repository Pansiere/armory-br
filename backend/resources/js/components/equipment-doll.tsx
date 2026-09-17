import ItemSearchInput from '@/components/item-search-input';
import { cn } from '@/lib/utils';
import type { Character, EquipmentSlotOption, ItemSummary } from '@/types/character';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function EquipmentDoll({
    character,
    slots,
}: {
    character: Character;
    slots: EquipmentSlotOption[];
}) {
    const [activeSlot, setActiveSlot] = useState<string | null>(null);

    const equipped = new Map(
        (character.equipment ?? []).map((equipment) => [equipment.slot, equipment.item]),
    );

    function equip(slot: string, item: ItemSummary) {
        setActiveSlot(null);
        router.put(
            `/characters/${character.id}/equipment/${slot}`,
            { item_id: item.id },
            { preserveScroll: true, preserveState: true },
        );
    }

    function unequip(slot: string) {
        router.delete(`/characters/${character.id}/equipment/${slot}`, {
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
                                    className="h-6 w-6 shrink-0 rounded-sm"
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

    return (
        <div>
            <h2 className="mb-3 flex items-baseline gap-2 font-heading text-lg font-semibold text-parchment-100">
                Equipamento
                {character.average_item_level && (
                    <span className="font-sans text-sm font-normal text-parchment-300">
                        item level médio: {character.average_item_level}
                    </span>
                )}
            </h2>

            <div className="grid gap-2 sm:grid-cols-2">
                <div className="space-y-2">{left.map(renderSlot)}</div>
                <div className="space-y-2">{right.map(renderSlot)}</div>
            </div>

            <div className="mt-2 grid gap-2 sm:grid-cols-3">{bottom.map(renderSlot)}</div>
        </div>
    );
}

import EquipmentDoll from '@/components/equipment-doll';
import type { Character, EquipmentSlotOption } from '@/types/character';
import { useEffect, useState, type MouseEvent } from 'react';
import { createPortal } from 'react-dom';

/**
 * Boneco completo em modo leitura, aberto a partir do botão de expandir do
 * card na dashboard (issue #20) — pra ver o equipamento inteiro sem sair
 * da dashboard nem cair na tela de edição. Renderizado via portal pra fora
 * da lista arrastável (SortableJS às vezes aplica transform nos itens da
 * lista durante o drag, o que quebraria um `position: fixed` que dependa
 * do viewport se o modal nascesse dentro dela).
 */
export default function CharacterEquipmentModal({
    character,
    slots,
    onClose,
}: {
    character: Character;
    slots: EquipmentSlotOption[];
    onClose: () => void;
}) {
    const [activeSpecIndex, setActiveSpecIndex] = useState(0);

    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onClose]);

    function closeFromBackdrop(event: MouseEvent) {
        event.stopPropagation();
        onClose();
    }

    return createPortal(
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            onClick={closeFromBackdrop}
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-label={`Equipamento de ${character.name}`}
                onClick={(event) => event.stopPropagation()}
                className="border-tavern-700 bg-tavern-950 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg border p-4 shadow-2xl sm:p-6"
            >
                <div className="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 className="font-heading text-parchment-100 text-xl font-bold">
                            {character.name}
                        </h2>
                        <p className="text-parchment-300 text-sm">
                            {[character.race_label, character.class_label]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        title="Fechar"
                        className="text-parchment-300 hover:text-parchment-100 shrink-0 text-2xl leading-none"
                    >
                        ×
                    </button>
                </div>

                <EquipmentDoll
                    character={character}
                    slots={slots}
                    activeSpecIndex={activeSpecIndex}
                    onSpecChange={setActiveSpecIndex}
                    readOnly
                />
            </div>
        </div>,
        document.body,
    );
}

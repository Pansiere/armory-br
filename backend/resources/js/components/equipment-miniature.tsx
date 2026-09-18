import ItemTooltip from '@/components/item-tooltip';
import type { CharacterEquipment } from '@/types/character';

/**
 * Prévia compacta do equipamento no card da dashboard (issue #20) — só os
 * itens equipados (sem slot vazio, sem gema solta), do tamanho de um ícone
 * pequeno. Pra ver o boneco inteiro, o botão de expandir do card abre o
 * modal com o <EquipmentDoll readOnly />.
 */
export default function EquipmentMiniature({
    equipment,
}: {
    equipment: CharacterEquipment[];
}) {
    const equipped = equipment.filter((entry) => entry.item.icon_url);

    if (equipped.length === 0) {
        return null;
    }

    return (
        <div className="mt-1.5 flex flex-wrap gap-1">
            {equipped.map((entry) => (
                <ItemTooltip
                    key={entry.slot}
                    itemId={entry.item.id}
                    qualityColor={entry.item.quality_color}
                >
                    <img
                        src={entry.item.icon_url ?? undefined}
                        alt={entry.item.name}
                        className="h-5 w-5 rounded-sm border"
                        style={{ borderColor: entry.item.quality_color }}
                    />
                </ItemTooltip>
            ))}
        </div>
    );
}

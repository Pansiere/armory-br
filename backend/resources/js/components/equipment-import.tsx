import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function EquipmentImport({ characterId }: { characterId: number }) {
    const [text, setText] = useState('');
    const [processing, setProcessing] = useState(false);
    const { equipmentImport } = usePage().props;

    function submit() {
        if (text.trim() === '') {
            return;
        }

        setProcessing(true);
        router.post(
            `/characters/${characterId}/equipment/import`,
            { text },
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setText('');
                },
            },
        );
    }

    return (
        <div className="mb-6 rounded-lg border border-tavern-700 bg-tavern-900 p-4">
            <h3 className="font-heading text-sm font-semibold text-parchment-100">
                Colar equipamento
            </h3>
            <p className="mt-1 text-xs text-parchment-300">
                Cole aqui o texto que seu addon exporta com os itens equipados — a
                gente reconhece os links de item no texto e monta o boneco de uma vez,
                em vez de buscar um por um.
            </p>

            <textarea
                value={text}
                onChange={(e) => setText(e.target.value)}
                rows={3}
                placeholder="Cole aqui..."
                className="mt-2 w-full rounded-md border border-tavern-700 bg-tavern-950 px-3 py-2 text-sm text-parchment-100 focus:border-parchment-300 focus:outline-none"
            />

            <button
                type="button"
                onClick={submit}
                disabled={processing || text.trim() === ''}
                className="mt-2 rounded-md bg-alliance px-4 py-1.5 text-sm font-medium text-white transition hover:bg-alliance-dim disabled:opacity-50"
            >
                Montar boneco
            </button>

            {equipmentImport && (
                <p className="mt-2 text-xs text-parchment-300">
                    {equipmentImport.equipped}{' '}
                    {equipmentImport.equipped === 1 ? 'item equipado' : 'itens equipados'}
                    {equipmentImport.ignored > 0
                        ? `, ${equipmentImport.ignored} ${equipmentImport.ignored === 1 ? 'ignorado' : 'ignorados'} (não reconhecido ou slot já ocupado)`
                        : ''}
                    .
                </p>
            )}
        </div>
    );
}

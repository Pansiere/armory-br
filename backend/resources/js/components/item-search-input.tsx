import ItemTooltip from '@/components/item-tooltip';
import type { ItemSummary } from '@/types/character';
import { useEffect, useRef, useState } from 'react';

type SearchTarget = { slot: string } | { gemColor: number };

export default function ItemSearchInput({
    target,
    onSelect,
    onCancel,
}: {
    target: SearchTarget;
    onSelect: (item: ItemSummary) => void;
    onCancel: () => void;
}) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<ItemSummary[]>([]);
    const [loading, setLoading] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    const targetParam =
        'slot' in target
            ? `slot=${encodeURIComponent(target.slot)}`
            : `gem_color=${target.gemColor}`;

    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    useEffect(() => {
        if (query.trim().length < 2) {
            setResults([]);
            return;
        }

        setLoading(true);
        const controller = new AbortController();

        const timeout = setTimeout(() => {
            fetch(
                `/items/search?q=${encodeURIComponent(query)}&${targetParam}`,
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                },
            )
                .then((res) => res.json())
                .then((body: ItemSummary[]) => setResults(body))
                .catch((error: unknown) => {
                    if (
                        !(
                            error instanceof DOMException &&
                            error.name === 'AbortError'
                        )
                    ) {
                        setResults([]);
                    }
                })
                .finally(() => setLoading(false));
        }, 250);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [query, targetParam]);

    return (
        <div className="border-tavern-700 bg-tavern-900 absolute z-10 mt-1 w-64 rounded-md border p-2 shadow-xl">
            <div className="flex gap-1">
                <input
                    ref={inputRef}
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder="Buscar item..."
                    className="border-tavern-700 bg-tavern-950 text-parchment-100 focus:border-parchment-300 w-full rounded border px-2 py-1 text-sm focus:outline-none"
                />
                <button
                    type="button"
                    onClick={onCancel}
                    className="border-tavern-700 text-parchment-300 hover:text-horde rounded border px-2 text-xs"
                >
                    ×
                </button>
            </div>

            {loading && (
                <p className="text-parchment-300 mt-1 text-xs">Buscando...</p>
            )}

            {!loading && query.trim().length >= 2 && results.length === 0 && (
                <p className="text-parchment-300 mt-1 text-xs">
                    Nenhum item encontrado.
                </p>
            )}

            {results.length > 0 && (
                <ul className="mt-1 max-h-48 overflow-y-auto">
                    {results.map((item) => (
                        <li key={item.id}>
                            <button
                                type="button"
                                onClick={() => onSelect(item)}
                                className="hover:bg-tavern-800 flex w-full items-center gap-2 truncate rounded px-2 py-1 text-left text-sm"
                            >
                                <ItemTooltip
                                    itemId={item.id}
                                    qualityColor={item.quality_color}
                                >
                                    {item.icon_url ? (
                                        <img
                                            src={item.icon_url}
                                            alt=""
                                            className="h-5 w-5 shrink-0 rounded-sm border"
                                            style={{
                                                borderColor: item.quality_color,
                                            }}
                                        />
                                    ) : (
                                        <span
                                            className="h-5 w-5 shrink-0 rounded-sm border border-dashed"
                                            style={{
                                                borderColor: item.quality_color,
                                            }}
                                        />
                                    )}
                                    <span
                                        className="truncate"
                                        style={{ color: item.quality_color }}
                                    >
                                        {item.name}
                                    </span>
                                </ItemTooltip>
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

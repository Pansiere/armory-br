import {
    useEffect,
    useRef,
    useState,
    type MouseEvent,
    type ReactNode,
} from 'react';

// Guardado no módulo (não em estado do componente) — a tooltip de um item
// é a mesma pra qualquer instância na página (boneco, busca, gema), então
// não faz sentido buscar de novo só porque o mesmo item apareceu em dois
// lugares. `null` cacheado também (item sem tooltip disponível não deve
// ficar re-tentando a cada hover).
const cache = new Map<number, string | null>();
const inflight = new Map<number, Promise<string | null>>();

function fetchTooltip(itemId: number): Promise<string | null> {
    if (cache.has(itemId)) {
        return Promise.resolve(cache.get(itemId) ?? null);
    }

    const existing = inflight.get(itemId);
    if (existing) {
        return existing;
    }

    const promise = fetch(`/items/${itemId}/tooltip`, {
        headers: { Accept: 'application/json' },
    })
        .then((res) => (res.ok ? res.json() : { tooltip_html: null }))
        .then((body: { tooltip_html: string | null }) => body.tooltip_html)
        .catch(() => null)
        .then((html) => {
            cache.set(itemId, html);
            inflight.delete(itemId);
            return html;
        });

    inflight.set(itemId, promise);
    return promise;
}

const LONG_PRESS_MS = 500;
const TOOLTIP_WIDTH = 320;

/**
 * Envolve um item (ícone/nome já renderizado pelo `children`) com a
 * tooltip estilo Wowhead — mostra ao passar o mouse (desktop) ou segurar o
 * toque (mobile, pra não brigar com o tap normal que já troca/seleciona o
 * item nesses mesmos elementos). O HTML vem de `/items/{id}/tooltip`
 * (proxy do próprio endpoint que o Wowhead usa nas páginas dele — ver
 * `App\Support\WowheadTooltipResolver`), e some silenciosamente se não tiver
 * (script/endpoint indisponível) — o ícone/nome por trás já contam o
 * essencial.
 */
export default function ItemTooltip({
    itemId,
    qualityColor,
    children,
}: {
    itemId: number;
    qualityColor: string;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [html, setHtml] = useState<string | null>(null);
    const [position, setPosition] = useState({
        top: 0,
        left: 0,
        flipped: false,
    });
    const anchorRef = useRef<HTMLSpanElement>(null);
    const longPressTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const suppressClick = useRef(false);

    function show() {
        const anchor = anchorRef.current;

        if (anchor) {
            const rect = anchor.getBoundingClientRect();
            const flipped = rect.top > window.innerHeight / 2;
            const left = Math.min(
                Math.max(rect.left, 12),
                window.innerWidth - TOOLTIP_WIDTH - 12,
            );

            setPosition({
                top: flipped ? rect.top - 8 : rect.bottom + 8,
                left,
                flipped,
            });
        }

        setOpen(true);
        void fetchTooltip(itemId).then(setHtml);
    }

    function hide() {
        setOpen(false);
    }

    function onTouchStart() {
        longPressTimer.current = setTimeout(() => {
            suppressClick.current = true;
            show();
        }, LONG_PRESS_MS);
    }

    function cancelLongPress() {
        if (longPressTimer.current) {
            clearTimeout(longPressTimer.current);
            longPressTimer.current = null;
        }
    }

    function onClickCapture(event: MouseEvent) {
        if (suppressClick.current) {
            suppressClick.current = false;
            event.preventDefault();
            event.stopPropagation();
        }
    }

    useEffect(() => cancelLongPress, []);

    // Fecha ao tocar fora — o long-press no mobile não tem um "mouseleave"
    // equivalente pra fechar sozinho.
    useEffect(() => {
        if (!open) {
            return;
        }

        function onOutsideTouch(event: TouchEvent) {
            if (
                anchorRef.current &&
                !anchorRef.current.contains(event.target as Node)
            ) {
                hide();
            }
        }

        document.addEventListener('touchstart', onOutsideTouch);
        return () => document.removeEventListener('touchstart', onOutsideTouch);
    }, [open]);

    return (
        <span
            ref={anchorRef}
            className="contents"
            onMouseEnter={show}
            onMouseLeave={hide}
            onTouchStart={onTouchStart}
            onTouchEnd={cancelLongPress}
            onTouchMove={cancelLongPress}
            onTouchCancel={cancelLongPress}
            onClickCapture={onClickCapture}
        >
            {children}

            {open && html && (
                <span
                    role="tooltip"
                    className="wowhead-tooltip fixed z-50"
                    style={{
                        top: position.top,
                        left: position.left,
                        width: TOOLTIP_WIDTH,
                        transform: position.flipped
                            ? 'translateY(-100%)'
                            : undefined,
                        boxShadow: `0 0 0 1px ${qualityColor}, 0 0 16px -2px ${qualityColor}, 0 8px 24px rgba(0,0,0,0.6)`,
                    }}
                    dangerouslySetInnerHTML={{ __html: html }}
                />
            )}
        </span>
    );
}

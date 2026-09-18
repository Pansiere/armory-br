/**
 * Brasão genérico de facção — desenhado à mão (não é asset da Blizzard)
 * porque não existe um ícone oficial único de "Aliança"/"Horda" do jeito
 * que existe um por classe/raça/profissão (o mais próximo são ícones de
 * PVP/conquista específicos, sem essa função genérica). Recolorido via
 * `currentColor`, então quem usa controla a cor com `className="text-..."`.
 * Extraído de `faction-column.tsx` (issue #22) pra reusar no seletor de
 * facção do formulário.
 */
export default function FactionIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" aria-hidden="true" className={className}>
            <path
                className="fill-current"
                d="M12 2 C8 2 5 3.4 5 5.6 L5 11 C5 16 8 19.6 12 21.5 C16 19.6 19 16 19 11 L19 5.6 C19 3.4 16 2 12 2 Z"
            />
        </svg>
    );
}

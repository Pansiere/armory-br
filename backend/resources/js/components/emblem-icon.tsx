export default function EmblemIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 64 64" aria-hidden="true" className={className}>
            <path
                d="M32,16 C25,16 19,18 19,22 L19,33 C19,43 24,49 32,52 C40,49 45,43 45,33 L45,22 C45,18 39,16 32,16 Z"
                fill="currentColor"
            />
            <path
                d="M32,19 L34.5,25 L34.5,30 L29.5,30 L29.5,25 Z"
                fill="var(--color-tavern-950)"
            />
            <rect x="24" y="30" width="16" height="3.6" rx="1" fill="var(--color-tavern-950)" />
            <rect x="29" y="33.6" width="6" height="13" rx="3" fill="var(--color-tavern-950)" />
        </svg>
    );
}

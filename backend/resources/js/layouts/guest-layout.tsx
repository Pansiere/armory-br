import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

export default function GuestLayout({
    title,
    children,
}: PropsWithChildren<{ title: string }>) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-12">
            <svg
                viewBox="0 0 64 64"
                aria-hidden="true"
                className="pointer-events-none absolute top-1/2 left-1/2 h-[140vh] w-[140vh] -translate-x-1/2 -translate-y-1/2 opacity-[0.05]"
            >
                <path
                    d="M32,16 C25,16 19,18 19,22 L19,33 C19,43 24,49 32,52 C40,49 45,43 45,33 L45,22 C45,18 39,16 32,16 Z"
                    fill="none"
                    stroke="#f8f1de"
                    strokeWidth="0.6"
                />
                <path d="M32,19 L34.5,25 L34.5,30 L29.5,30 L29.5,25 Z" fill="#f8f1de" />
                <rect x="24" y="30" width="16" height="3.6" rx="1" fill="#f8f1de" />
                <rect x="29" y="33.6" width="6" height="13" rx="3" fill="#f8f1de" />
            </svg>

            <div className="relative mb-8 text-center">
                <Link
                    href="/"
                    className="font-heading text-3xl font-bold tracking-wide text-parchment-100"
                >
                    Armory BR
                </Link>
                <p className="mt-1 text-sm text-parchment-300">
                    Vitrine de personagens WotLK 3.3.5
                </p>
            </div>

            <div className="w-full max-w-sm rounded-lg border border-tavern-700 bg-tavern-900 p-6 shadow-xl sm:p-8">
                <h1 className="mb-6 font-heading text-xl font-semibold text-parchment-100">
                    {title}
                </h1>
                {children}
            </div>
        </div>
    );
}

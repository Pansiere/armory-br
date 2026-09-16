import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

export default function GuestLayout({
    title,
    children,
}: PropsWithChildren<{ title: string }>) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center px-4 py-12">
            <div className="mb-8 text-center">
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

import EmblemIcon from '@/components/emblem-icon';
import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler, PropsWithChildren } from 'react';

export default function AppLayout({ children }: PropsWithChildren) {
    const { post, processing } = useForm({});

    const logout: FormEventHandler = (e) => {
        e.preventDefault();
        post('/logout');
    };

    return (
        <div className="min-h-screen">
            <header className="border-b border-tavern-800 bg-tavern-900 bg-gradient-to-b from-tavern-900 to-tavern-900/80 shadow-[0_1px_0_rgba(217,200,160,0.06)]">
                <div className="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
                    <Link
                        href="/dashboard"
                        className="flex shrink-0 items-center gap-2 font-heading text-lg font-bold tracking-wide whitespace-nowrap text-parchment-100 transition hover:text-parchment-200 sm:text-xl"
                    >
                        <EmblemIcon className="h-6 w-6 shrink-0 text-parchment-100 sm:h-7 sm:w-7" />
                        Armory BR
                    </Link>

                    <nav className="flex items-center gap-3 text-sm whitespace-nowrap text-parchment-300 sm:gap-4">
                        <Link href="/dashboard" className="hidden hover:text-parchment-100 sm:inline">
                            Meus personagens
                        </Link>
                        <Link href="/settings/account" className="hover:text-parchment-100">
                            Configurações
                        </Link>
                        <form onSubmit={logout}>
                            <button
                                type="submit"
                                disabled={processing}
                                className="hover:text-parchment-100 disabled:opacity-50"
                            >
                                Sair
                            </button>
                        </form>
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">{children}</main>
        </div>
    );
}

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
            <header className="border-b border-tavern-800 bg-tavern-900">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                    <Link
                        href="/dashboard"
                        className="font-heading text-xl font-bold tracking-wide text-parchment-100"
                    >
                        Armory BR
                    </Link>

                    <nav className="flex items-center gap-4 text-sm text-parchment-300">
                        <Link href="/dashboard" className="hover:text-parchment-100">
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

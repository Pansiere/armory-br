import InputError from '@/components/input-error';
import InputLabel from '@/components/input-label';
import TextInput from '@/components/text-input';
import GuestLayout from '@/layouts/guest-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        username: '',
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout title="Entrar">
            <Head title="Entrar" />

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="username">Usuário</InputLabel>
                    <TextInput
                        id="username"
                        autoFocus
                        autoComplete="username"
                        value={data.username}
                        onChange={(e) => setData('username', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.username} />
                </div>

                <div>
                    <InputLabel htmlFor="password">Senha</InputLabel>
                    <TextInput
                        id="password"
                        type="password"
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.password} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-alliance px-4 py-2 font-medium text-white transition hover:bg-alliance-dim disabled:opacity-50"
                >
                    Entrar
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-parchment-300">
                Não tem conta?{' '}
                <Link href="/register" className="font-medium text-parchment-100 underline">
                    Cadastre-se
                </Link>
            </p>
        </GuestLayout>
    );
}

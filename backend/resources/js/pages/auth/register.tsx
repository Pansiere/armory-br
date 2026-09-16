import InputError from '@/components/input-error';
import InputLabel from '@/components/input-label';
import TextInput from '@/components/text-input';
import GuestLayout from '@/layouts/guest-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout title="Criar conta">
            <Head title="Criar conta" />

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
                    <InputLabel htmlFor="email">E-mail</InputLabel>
                    <TextInput
                        id="email"
                        type="email"
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="mt-1"
                    />
                    <p className="mt-1 text-xs text-parchment-300">
                        Usado só pra recuperar sua conta caso você esqueça a senha.
                    </p>
                    <InputError message={errors.email} />
                </div>

                <div>
                    <InputLabel htmlFor="password">Senha</InputLabel>
                    <TextInput
                        id="password"
                        type="password"
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.password} />
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation">Confirmar senha</InputLabel>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-md bg-alliance px-4 py-2 font-medium text-white transition hover:bg-alliance-dim disabled:opacity-50"
                >
                    Criar conta
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-parchment-300">
                Já tem conta?{' '}
                <Link href="/login" className="font-medium text-parchment-100 underline">
                    Entrar
                </Link>
            </p>
        </GuestLayout>
    );
}

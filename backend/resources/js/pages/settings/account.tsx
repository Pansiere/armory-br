import InputError from '@/components/input-error';
import InputLabel from '@/components/input-label';
import TextInput from '@/components/text-input';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

export default function Account() {
    const { auth } = usePage().props;

    const {
        data,
        setData,
        delete: destroy,
        processing,
        errors,
        reset,
    } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        destroy('/account', {
            onError: () => reset('password'),
        });
    };

    return (
        <AppLayout>
            <Head title="Configurações da conta" />

            <h1 className="mb-6 font-heading text-2xl font-bold text-parchment-100">
                Configurações da conta
            </h1>

            <div className="mb-8 rounded-lg border border-tavern-700 bg-tavern-900 p-6">
                <h2 className="font-heading text-lg font-semibold text-parchment-100">Usuário</h2>
                <p className="mt-2 text-parchment-200">{auth.user.username}</p>
            </div>

            <div className="rounded-lg border border-horde p-6">
                <h2 className="font-heading text-lg font-semibold text-parchment-100">
                    Excluir conta
                </h2>
                <p className="mt-2 text-sm text-parchment-200">
                    Isso apaga sua conta, todos os seus personagens e profissões, de verdade e sem
                    volta. Não existe recuperação — nem por senha, nem por suporte.
                </p>

                <form onSubmit={submit} className="mt-4 max-w-xs space-y-3">
                    <div>
                        <InputLabel htmlFor="password">Confirme sua senha</InputLabel>
                        <TextInput
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-horde px-4 py-2 font-medium text-white transition hover:bg-horde-dim disabled:opacity-50"
                    >
                        Apagar minha conta
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}

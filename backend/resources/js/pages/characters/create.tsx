import CharacterForm from '@/components/character-form';
import AppLayout from '@/layouts/app-layout';
import type { CharacterFormOptions } from '@/types/character';
import { Head } from '@inertiajs/react';

export default function Create(options: CharacterFormOptions) {
    return (
        <AppLayout>
            <Head title="Novo personagem" />

            <h1 className="mb-6 font-heading text-2xl font-bold text-parchment-100">
                Novo personagem
            </h1>

            <CharacterForm
                options={options}
                action="/characters"
                method="post"
                submitLabel="Criar personagem"
            />
        </AppLayout>
    );
}

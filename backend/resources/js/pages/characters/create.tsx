import CharacterForm from '@/components/character-form';
import CharacterJsonImport from '@/components/character-json-import';
import AppLayout from '@/layouts/app-layout';
import type { CharacterFormOptions } from '@/types/character';
import { Head } from '@inertiajs/react';

export default function Create(options: CharacterFormOptions) {
    return (
        <AppLayout>
            <Head title="Novo personagem" />

            <h1 className="font-heading text-parchment-100 mb-6 text-2xl font-bold">
                Novo personagem
            </h1>

            <CharacterJsonImport />

            <CharacterForm
                options={options}
                action="/characters"
                method="post"
                submitLabel="Criar personagem"
            />
        </AppLayout>
    );
}

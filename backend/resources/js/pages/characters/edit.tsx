import CharacterForm from '@/components/character-form';
import AppLayout from '@/layouts/app-layout';
import type { Character, CharacterFormOptions } from '@/types/character';
import { Head, router } from '@inertiajs/react';

export default function Edit({
    character,
    ...options
}: CharacterFormOptions & { character: Character }) {
    function destroy() {
        if (confirm(`Apagar ${character.name}? Essa ação não pode ser desfeita.`)) {
            router.delete(`/characters/${character.id}`);
        }
    }

    return (
        <AppLayout>
            <Head title={`Editar ${character.name}`} />

            <div className="mb-6 flex items-center justify-between">
                <h1 className="font-heading text-2xl font-bold text-parchment-100">
                    Editar {character.name}
                </h1>
                <button
                    type="button"
                    onClick={destroy}
                    className="text-sm font-medium text-horde hover:underline"
                >
                    Apagar personagem
                </button>
            </div>

            <CharacterForm
                options={options}
                character={character}
                action={`/characters/${character.id}`}
                method="put"
                submitLabel="Salvar alterações"
            />
        </AppLayout>
    );
}

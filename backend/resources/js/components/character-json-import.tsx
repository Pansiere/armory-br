import InputError from '@/components/input-error';
import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

/**
 * Importa um personagem a partir do JSON que a própria Armory BR exporta
 * (issue #18) — diferente da caixa de colar texto de addon (issue #23,
 * componente separado nesta mesma tela): o JSON já traz classe e
 * especialização, que o texto de addon nunca inclui, então essa opção
 * recria o personagem inteiro sem precisar escolher nada manualmente.
 */
export default function CharacterJsonImport() {
    const { data, setData, post, processing, errors } = useForm<{
        file: File | null;
    }>({
        file: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!data.file) {
            return;
        }

        post('/characters/import-json');
    };

    return (
        <div className="border-tavern-700 bg-tavern-900 mb-6 rounded-lg border p-4">
            <h3 className="font-heading text-parchment-100 text-sm font-semibold">
                Importar backup (.json)
            </h3>
            <p className="text-parchment-300 mt-1 text-xs">
                Tem um arquivo exportado da própria Armory BR (botão "Exportar"
                na tela de edição de um personagem)? Selecione aqui pra recriar
                o personagem inteiro — inclusive classe e especialização, que o
                texto colado acima não traz.
            </p>

            <form
                onSubmit={submit}
                className="mt-2 flex flex-wrap items-center gap-2"
            >
                <input
                    type="file"
                    accept="application/json"
                    onChange={(e) =>
                        setData('file', e.target.files?.[0] ?? null)
                    }
                    className="text-parchment-300 text-sm"
                />
                <button
                    type="submit"
                    disabled={processing || !data.file}
                    className="bg-alliance hover:bg-alliance-dim rounded-md px-4 py-1.5 text-sm font-medium text-white transition disabled:opacity-50"
                >
                    Importar
                </button>
            </form>

            {/* Os erros do backend vêm com a forma do JSON exportado (name,
                faction, specs.0.spec...), não da forma do form client-side
                (só `file`) — mostra o primeiro que vier, seja qual for a
                chave. */}
            <InputError
                message={Object.values(errors as Record<string, string>)[0]}
            />
        </div>
    );
}

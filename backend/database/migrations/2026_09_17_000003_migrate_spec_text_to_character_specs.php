<?php

use App\Enums\CharacterClass;
use App\Enums\Spec;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migra o texto livre de `characters.spec` (formato antigo) pra uma
     * linha em `character_specs` (posição 1) e reaponta o equipamento já
     * cadastrado do personagem pra essa spec. Casa por label normalizado
     * (sem acento/caixa) contra as specs da classe do personagem.
     *
     * Personagem sem texto de spec, ou cujo texto não casa com nada: se ele
     * já tem equipamento cadastrado, a próxima migration deixaria esse
     * `character_items.character_spec_id` NULL — e a coluna vira NOT NULL
     * logo em seguida. Pra não perder o equipamento nesse caso raro, cai
     * pra primeira spec da classe (dá pra trocar depois editando o
     * personagem; o que importa aqui é não apagar o boneco que a pessoa já
     * montou).
     */
    public function up(): void
    {
        $characters = DB::table('characters')->get();

        foreach ($characters as $character) {
            $class = CharacterClass::tryFrom($character->class);

            if ($class === null) {
                continue;
            }

            $hasItems = DB::table('character_items')->where('character_id', $character->id)->exists();

            $matched = $character->spec
                ? collect(Spec::forClass($class))->first(
                    fn (Spec $spec) => $this->normalize($spec->label()) === $this->normalize($character->spec),
                )
                : null;

            if ($matched === null && ! $hasItems) {
                continue;
            }

            $matched ??= Spec::forClass($class)[0];

            $specId = DB::table('character_specs')->insertGetId([
                'character_id' => $character->id,
                'spec' => $matched->value,
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('character_items')
                ->where('character_id', $character->id)
                ->update(['character_spec_id' => $specId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dados derivados do texto livre — não há como reverter com
        // segurança sem já ter perdido a coluna original (a migration
        // seguinte que a dropa). Sem-op de propósito.
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return str_replace(
            ['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
            $value,
        );
    }
};

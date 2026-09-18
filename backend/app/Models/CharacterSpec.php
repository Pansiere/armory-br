<?php

namespace App\Models;

use App\Enums\EquipmentSlot;
use App\Enums\Spec;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $character_id
 * @property Spec $spec
 * @property int $position
 */
#[Fillable(['spec', 'position'])]
class CharacterSpec extends Model
{
    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * O conjunto de equipamento dessa spec — cada spec tem o seu próprio
     * (seção do plano sobre dual spec).
     *
     * @return HasMany<CharacterItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
    }

    /**
     * Item level médio do equipamento dessa spec. Null sem nenhum item
     * equipado ainda.
     */
    public function averageItemLevel(): ?int
    {
        if ($this->items->isEmpty()) {
            return null;
        }

        return (int) round($this->items->avg(fn (CharacterItem $characterItem) => $characterItem->item->item_level));
    }

    /**
     * Equipa o item no slot, cuidando pra não deixar gema "grudada" errada:
     * como a linha de character_items é reaproveitada (updateOrCreate por
     * slot, não delete+recreate — senão a reordenação via drag-and-drop e
     * afins ficariam mais complicadas), trocar de item no mesmo slot exige
     * limpar as gemas antigas manualmente, já que o item novo pode nem ter
     * os mesmos sockets do antigo. Usado tanto pelo equipar manual via busca
     * (CharacterEquipmentController) quanto pelo import de texto
     * (App\Support\CharacterImportParser) — por isso mora no model, não em
     * nenhum dos dois controllers.
     */
    public function equipItem(EquipmentSlot $slot, Item $item): CharacterItem
    {
        $existing = $this->items()->where('slot', $slot->value)->first();

        if ($existing && $existing->item_id !== $item->id) {
            $existing->gems()->delete();
        }

        return $this->items()->updateOrCreate(
            ['slot' => $slot->value],
            ['item_id' => $item->id],
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'spec' => Spec::class,
            'position' => 'integer',
        ];
    }
}

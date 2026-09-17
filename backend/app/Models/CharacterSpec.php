<?php

namespace App\Models;

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

<?php

namespace App\Models;

use App\Enums\EquipmentSlot;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Um item equipado num slot do boneco visual (seção 7.1), pertencente ao
 * conjunto de equipamento de UMA spec do personagem (dual spec — seção 7.5).
 *
 * @property int $id
 * @property int $character_spec_id
 * @property EquipmentSlot $slot
 * @property int $item_id
 */
#[Fillable(['slot', 'item_id'])]
class CharacterItem extends Model
{
    /**
     * @return BelongsTo<CharacterSpec, $this>
     */
    public function characterSpec(): BelongsTo
    {
        return $this->belongsTo(CharacterSpec::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return HasMany<CharacterItemGem, $this>
     */
    public function gems(): HasMany
    {
        return $this->hasMany(CharacterItemGem::class)->orderBy('socket_position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot' => EquipmentSlot::class,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma gema encaixada num dos até 3 sockets de um item equipado.
 *
 * @property int $id
 * @property int $character_item_id
 * @property int $socket_position
 * @property int $item_id
 */
#[Fillable(['socket_position', 'item_id'])]
class CharacterItemGem extends Model
{
    /**
     * @return BelongsTo<CharacterItem, $this>
     */
    public function characterItem(): BelongsTo
    {
        return $this->belongsTo(CharacterItem::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'socket_position' => 'integer',
        ];
    }
}

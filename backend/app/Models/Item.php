<?php

namespace App\Models;

use App\Enums\InventorySlot;
use App\Enums\ItemQuality;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Itens do jogo, importados do AzerothCore-wotlk (GPL-2.0) via
 * `php artisan items:import`. Não editável pelo usuário.
 *
 * @property int $id
 * @property int $item_id
 * @property string $name
 * @property string|null $icon
 * @property InventorySlot $slot
 * @property ItemQuality $quality
 * @property int $item_level
 */
#[Fillable(['item_id', 'name', 'icon', 'slot', 'quality', 'item_level'])]
class Item extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot' => InventorySlot::class,
            'quality' => ItemQuality::class,
            'item_level' => 'integer',
        ];
    }
}

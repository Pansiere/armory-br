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
 * @property int $socket_color_1
 * @property int $socket_color_2
 * @property int $socket_color_3
 * @property int|null $gem_color
 * @property string|null $tooltip_html
 */
#[Fillable([
    'item_id', 'name', 'icon', 'slot', 'quality', 'item_level',
    'socket_color_1', 'socket_color_2', 'socket_color_3', 'gem_color', 'tooltip_html',
])]
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
            'socket_color_1' => 'integer',
            'socket_color_2' => 'integer',
            'socket_color_3' => 'integer',
            'gem_color' => 'integer',
        ];
    }

    public function isGem(): bool
    {
        return $this->gem_color !== null;
    }

    /**
     * Cores dos sockets do item (na ordem), só as ocupadas (0 = sem socket
     * ali é descartado).
     *
     * @return array<int, int>
     */
    public function socketColors(): array
    {
        return array_values(array_filter([
            $this->socket_color_1,
            $this->socket_color_2,
            $this->socket_color_3,
        ]));
    }

    public function hasSockets(): bool
    {
        return $this->socketColors() !== [];
    }

    /**
     * O CDN de ícones do Wowhead (wow.zamimg.com) é o hotlink padrão usado
     * por praticamente todo addon/site de WoW — não temos os ícones em si
     * pra redistribuir (ver ImportItems::resolveIcons()), só o nome.
     */
    public function iconUrl(): ?string
    {
        return $this->icon ? "https://wow.zamimg.com/images/wow/icons/medium/{$this->icon}.jpg" : null;
    }
}

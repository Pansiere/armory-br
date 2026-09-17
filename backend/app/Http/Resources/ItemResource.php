<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'icon_url' => $this->iconUrl(),
            'quality' => $this->quality->value,
            'quality_label' => $this->quality->label(),
            'quality_color' => $this->quality->color(),
            'item_level' => $this->item_level,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Character
 */
class CharacterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'faction' => $this->faction->value,
            'class' => $this->class->value,
            'class_label' => $this->class->label(),
            'class_color' => $this->class->color(),
            'class_needs_text_outline' => $this->class->needsTextOutline(),
            'spec' => $this->spec,
            'race' => $this->race?->value,
            'race_label' => $this->race?->label(),
            'level' => $this->level,
            'position' => $this->position,
            'is_public' => $this->is_public,
            'public_url' => $this->is_public ? route('characters.public', $this->public_token) : null,
            'average_item_level' => $this->whenLoaded('items', fn () => $this->averageItemLevel()),
            'professions' => $this->whenLoaded(
                'professions',
                fn () => $this->professions->map(fn ($profession) => [
                    'id' => $profession->id,
                    'name' => $profession->name->value,
                    'label' => $profession->name->label(),
                    'skill_level' => $profession->skill_level,
                ]),
            ),
            'equipment' => $this->whenLoaded(
                'items',
                fn () => $this->items->map(fn ($characterItem) => [
                    'slot' => $characterItem->slot->value,
                    'item' => [
                        'id' => $characterItem->item->id,
                        'name' => $characterItem->item->name,
                        'icon' => $characterItem->item->icon,
                        'quality' => $characterItem->item->quality->value,
                        'quality_label' => $characterItem->item->quality->label(),
                        'quality_color' => $characterItem->item->quality->color(),
                        'item_level' => $characterItem->item->item_level,
                    ],
                ]),
            ),
        ];
    }
}

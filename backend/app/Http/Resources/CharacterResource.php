<?php

namespace App\Http\Resources;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterItemGem;
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
            'race' => $this->race?->value,
            'race_label' => $this->race?->label(),
            'level' => $this->level,
            'position' => $this->position,
            'is_public' => $this->is_public,
            'public_url' => $this->is_public ? route('characters.public', $this->public_token) : null,
            'average_item_level' => $this->whenLoaded('specs', fn () => $this->averageItemLevel()),
            'professions' => $this->whenLoaded(
                'professions',
                fn () => $this->professions->map(fn ($profession) => [
                    'id' => $profession->id,
                    'name' => $profession->name->value,
                    'label' => $profession->name->label(),
                    'skill_level' => $profession->skill_level,
                ]),
            ),
            'specs' => $this->whenLoaded(
                'specs',
                fn () => $this->specs->map(fn ($characterSpec) => [
                    'id' => $characterSpec->id,
                    'value' => $characterSpec->spec->value,
                    'label' => $characterSpec->spec->label(),
                    'icon_url' => $characterSpec->spec->iconUrl(),
                    'position' => $characterSpec->position,
                    'average_item_level' => $characterSpec->relationLoaded('items')
                        ? $characterSpec->averageItemLevel()
                        : null,
                    'equipment' => $characterSpec->relationLoaded('items')
                        ? $characterSpec->items->map(fn (CharacterItem $characterItem) => [
                            'slot' => $characterItem->slot->value,
                            'item' => [
                                'id' => $characterItem->item->id,
                                'name' => $characterItem->item->name,
                                'icon' => $characterItem->item->icon,
                                'icon_url' => $characterItem->item->iconUrl(),
                                'quality' => $characterItem->item->quality->value,
                                'quality_label' => $characterItem->item->quality->label(),
                                'quality_color' => $characterItem->item->quality->color(),
                                'item_level' => $characterItem->item->item_level,
                                'socket_colors' => $characterItem->item->socketColors(),
                            ],
                            'gems' => $characterItem->relationLoaded('gems')
                                ? $characterItem->gems->map(fn (CharacterItemGem $gem) => [
                                    'socket_position' => $gem->socket_position,
                                    'item' => [
                                        'id' => $gem->item->id,
                                        'name' => $gem->item->name,
                                        'icon_url' => $gem->item->iconUrl(),
                                        'quality_color' => $gem->item->quality->color(),
                                        'gem_color' => $gem->item->gem_color,
                                    ],
                                ])
                                : [],
                        ])
                        : [],
                ]),
            ),
        ];
    }
}

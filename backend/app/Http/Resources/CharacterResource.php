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
            'position' => $this->position,
            'professions' => $this->whenLoaded(
                'professions',
                fn () => $this->professions->map(fn ($profession) => [
                    'id' => $profession->id,
                    'name' => $profession->name->value,
                    'label' => $profession->name->label(),
                    'skill_level' => $profession->skill_level,
                ]),
            ),
        ];
    }
}

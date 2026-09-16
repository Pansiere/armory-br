<?php

namespace App\Models;

use App\Enums\Profession;
use Database\Factories\CharacterProfessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $character_id
 * @property Profession $name
 * @property int|null $skill_level
 */
#[Fillable(['name', 'skill_level'])]
class CharacterProfession extends Model
{
    /** @use HasFactory<CharacterProfessionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => Profession::class,
            'skill_level' => 'integer',
        ];
    }
}

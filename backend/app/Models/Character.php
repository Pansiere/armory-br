<?php

namespace App\Models;

use App\Enums\CharacterClass;
use App\Enums\Faction;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Faction $faction
 * @property CharacterClass $class
 * @property string|null $spec
 * @property string|null $race
 * @property int|null $level
 * @property int $position
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'faction', 'class', 'spec', 'position', 'is_public'])]
class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CharacterProfession, $this>
     */
    public function professions(): HasMany
    {
        return $this->hasMany(CharacterProfession::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'faction' => Faction::class,
            'class' => CharacterClass::class,
            'level' => 'integer',
            'position' => 'integer',
            'is_public' => 'boolean',
        ];
    }
}

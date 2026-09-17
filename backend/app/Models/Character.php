<?php

namespace App\Models;

use App\Enums\CharacterClass;
use App\Enums\Faction;
use App\Enums\Race;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
 * @property string $public_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'faction', 'class', 'spec', 'race', 'level', 'position', 'is_public'])]
class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Character $character) {
            $character->public_token ??= Str::random(20);
        });
    }

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
     * @return HasMany<CharacterItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CharacterItem::class);
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
            'race' => Race::class,
            'level' => 'integer',
            'position' => 'integer',
            'is_public' => 'boolean',
        ];
    }
}

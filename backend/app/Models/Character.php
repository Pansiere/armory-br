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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Faction $faction
 * @property CharacterClass $class
 * @property string|null $race
 * @property int|null $level
 * @property int $position
 * @property bool $is_public
 * @property string $public_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'faction', 'class', 'race', 'level', 'position', 'is_public'])]
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
     * As 1-2 specs do personagem (dual spec), ordenadas — posição 1 é a
     * spec primária.
     *
     * @return HasMany<CharacterSpec, $this>
     */
    public function specs(): HasMany
    {
        return $this->hasMany(CharacterSpec::class)->orderBy('position');
    }

    /**
     * Todos os itens equipados do personagem, somando as duas specs (quando
     * há dual spec) — passa por character_specs porque character_items não
     * tem mais character_id direto (redundante com character_spec_id desde
     * o dual spec). Pra pegar o equipamento de UMA spec específica, use
     * `CharacterSpec::items()`.
     *
     * @return HasManyThrough<CharacterItem, CharacterSpec, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(CharacterItem::class, CharacterSpec::class);
    }

    public function primarySpec(): ?CharacterSpec
    {
        return $this->specs->firstWhere('position', 1);
    }

    /**
     * Item level médio da spec primária (seção 7.4) — é o número mostrado na
     * vitrine. Cada spec tem o seu próprio (ver `CharacterSpec::averageItemLevel()`),
     * já que dual spec pode ter dois conjuntos de equipamento bem diferentes.
     */
    public function averageItemLevel(): ?int
    {
        return $this->primarySpec()?->averageItemLevel();
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

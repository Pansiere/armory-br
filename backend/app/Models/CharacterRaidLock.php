<?php

namespace App\Models;

use App\Enums\Raid;
use App\Support\RaidResetSchedule;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $character_id
 * @property Raid $raid
 * @property int $size
 * @property bool $heroic
 * @property Carbon $locked_at
 */
#[Fillable(['raid', 'size', 'heroic', 'locked_at'])]
class CharacterRaidLock extends Model
{
    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Se esse lock ainda vale ou já passou do último reset semanal — ver
     * App\Support\RaidResetSchedule pro racional de calcular isso on-the-fly
     * em vez de apagar a linha via job na hora do reset.
     */
    public function isActive(): bool
    {
        return $this->locked_at->greaterThanOrEqualTo(RaidResetSchedule::lastReset());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raid' => Raid::class,
            'size' => 'integer',
            'heroic' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }
}

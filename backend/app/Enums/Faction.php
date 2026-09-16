<?php

namespace App\Enums;

enum Faction: string
{
    case Alliance = 'alliance';
    case Horde = 'horde';

    public function label(): string
    {
        return match ($this) {
            self::Alliance => 'Aliança',
            self::Horde => 'Horda',
        };
    }

    /**
     * Cor de identidade da facção (cabeçalho e divisor da coluna).
     */
    public function color(): string
    {
        return match ($this) {
            self::Alliance => '#2B5797',
            self::Horde => '#8C1F28',
        };
    }
}

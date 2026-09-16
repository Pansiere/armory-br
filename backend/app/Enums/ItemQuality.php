<?php

namespace App\Enums;

enum ItemQuality: int
{
    case Poor = 0;
    case Common = 1;
    case Uncommon = 2;
    case Rare = 3;
    case Epic = 4;
    case Legendary = 5;
    case Artifact = 6;

    public function label(): string
    {
        return match ($this) {
            self::Poor => 'Pobre',
            self::Common => 'Comum',
            self::Uncommon => 'Incomum',
            self::Rare => 'Raro',
            self::Epic => 'Épico',
            self::Legendary => 'Lendário',
            self::Artifact => 'Artefato',
        };
    }

    /**
     * Cor da borda por qualidade (seção 7.1 do spec).
     */
    public function color(): string
    {
        return match ($this) {
            self::Poor => '#9D9D9D',
            self::Common => '#FFFFFF',
            self::Uncommon => '#1EFF00',
            self::Rare => '#0070DD',
            self::Epic => '#A335EE',
            self::Legendary => '#FF8000',
            self::Artifact => '#E6CC80',
        };
    }
}

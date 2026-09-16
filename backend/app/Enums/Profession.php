<?php

namespace App\Enums;

enum Profession: string
{
    // Primárias — máximo 2 por personagem, nível máximo 450.
    case Alchemy = 'alchemy';
    case Blacksmithing = 'blacksmithing';
    case Enchanting = 'enchanting';
    case Engineering = 'engineering';
    case Herbalism = 'herbalism';
    case Inscription = 'inscription';
    case Jewelcrafting = 'jewelcrafting';
    case Leatherworking = 'leatherworking';
    case Mining = 'mining';
    case Skinning = 'skinning';
    case Tailoring = 'tailoring';

    // Secundárias — acumuláveis, sem limite de quantidade.
    case Cooking = 'cooking';
    case FirstAid = 'first_aid';
    case Fishing = 'fishing';

    public function label(): string
    {
        return match ($this) {
            self::Alchemy => 'Alquimia',
            self::Blacksmithing => 'Ferraria',
            self::Enchanting => 'Encantamento',
            self::Engineering => 'Engenharia',
            self::Herbalism => 'Herborismo',
            self::Inscription => 'Escrivania',
            self::Jewelcrafting => 'Joalheria',
            self::Leatherworking => 'Couraria',
            self::Mining => 'Mineração',
            self::Skinning => 'Esfolamento',
            self::Tailoring => 'Alfaiataria',
            self::Cooking => 'Culinária',
            self::FirstAid => 'Primeiros Socorros',
            self::Fishing => 'Pesca',
        };
    }

    public function isPrimary(): bool
    {
        return match ($this) {
            self::Cooking, self::FirstAid, self::Fishing => false,
            default => true,
        };
    }

    public const MAX_SKILL_LEVEL = 450;

    public const MAX_PRIMARY_PER_CHARACTER = 2;

    /**
     * @return array<int, self>
     */
    public static function primaries(): array
    {
        return array_values(array_filter(self::cases(), fn (self $profession) => $profession->isPrimary()));
    }

    /**
     * @return array<int, self>
     */
    public static function secondaries(): array
    {
        return array_values(array_filter(self::cases(), fn (self $profession) => ! $profession->isPrimary()));
    }
}

<?php

namespace App\Enums;

enum CharacterClass: string
{
    case Warrior = 'warrior';
    case Paladin = 'paladin';
    case Hunter = 'hunter';
    case Rogue = 'rogue';
    case Priest = 'priest';
    case DeathKnight = 'death_knight';
    case Shaman = 'shaman';
    case Mage = 'mage';
    case Warlock = 'warlock';
    case Druid = 'druid';

    public function label(): string
    {
        return match ($this) {
            self::Warrior => 'Guerreiro',
            self::Paladin => 'Paladino',
            self::Hunter => 'Caçador',
            self::Rogue => 'Ladino',
            self::Priest => 'Sacerdote',
            self::DeathKnight => 'Cavaleiro da Morte',
            self::Shaman => 'Xamã',
            self::Mage => 'Mago',
            self::Warlock => 'Bruxo',
            self::Druid => 'Druida',
        };
    }

    /**
     * Cor oficial da classe (WotLK 3.3.5). Fiel ao jogo — não alterar o hex.
     */
    public function color(): string
    {
        return match ($this) {
            self::Warrior => '#C79C6E',
            self::Paladin => '#F58CBA',
            self::Hunter => '#ABD473',
            self::Rogue => '#FFF569',
            self::Priest => '#FFFFFF',
            self::DeathKnight => '#C41F3B',
            self::Shaman => '#0070DE',
            self::Mage => '#69CCF0',
            self::Warlock => '#9482C9',
            self::Druid => '#FF7D0A',
        };
    }

    /**
     * Ladino e Sacerdote somem sobre fundo claro. Em vez de trair a cor
     * oficial, o front aplica um contorno escuro no texto quando true.
     */
    public function needsTextOutline(): bool
    {
        return match ($this) {
            self::Rogue, self::Priest => true,
            default => false,
        };
    }
}

<?php

namespace App\Enums;

enum Race: string
{
    case Human = 'human';
    case Dwarf = 'dwarf';
    case NightElf = 'night_elf';
    case Gnome = 'gnome';
    case Draenei = 'draenei';
    case Orc = 'orc';
    case Undead = 'undead';
    case Tauren = 'tauren';
    case Troll = 'troll';
    case BloodElf = 'blood_elf';

    public function label(): string
    {
        return match ($this) {
            self::Human => 'Humano',
            self::Dwarf => 'Anão',
            self::NightElf => 'Elfo Noturno',
            self::Gnome => 'Gnomo',
            self::Draenei => 'Draenei',
            self::Orc => 'Orc',
            self::Undead => 'Morto-vivo',
            self::Tauren => 'Tauren',
            self::Troll => 'Troll',
            self::BloodElf => 'Elfo Sangrento',
        };
    }

    public function faction(): Faction
    {
        return match ($this) {
            self::Human, self::Dwarf, self::NightElf, self::Gnome, self::Draenei => Faction::Alliance,
            self::Orc, self::Undead, self::Tauren, self::Troll, self::BloodElf => Faction::Horde,
        };
    }
}

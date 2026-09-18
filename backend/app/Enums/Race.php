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

    /**
     * No jogo o ícone de raça é por gênero (macho/fêmea), mas `Character`
     * não guarda gênero (só nome/facção/classe/raça/nível — seção sobre
     * ícones, issue #22) — adicionar uma coluna só pra isso é fora do
     * escopo pedido ali. Convenção adotada: sempre a variante macho,
     * mesmo ícone pra qualquer personagem dessa raça independente do sexo
     * de verdade dele no jogo. Cada URL confirmada batendo direto no CDN
     * (wow.zamimg.com) antes de codar — não é chute.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Human => 'achievement_character_human_male',
            self::Dwarf => 'achievement_character_dwarf_male',
            self::NightElf => 'achievement_character_nightelf_male',
            self::Gnome => 'achievement_character_gnome_male',
            self::Draenei => 'achievement_character_draenei_male',
            self::Orc => 'achievement_character_orc_male',
            self::Undead => 'achievement_character_undead_male',
            self::Tauren => 'achievement_character_tauren_male',
            self::Troll => 'achievement_character_troll_male',
            self::BloodElf => 'achievement_character_bloodelf_male',
        };
    }

    public function iconUrl(): string
    {
        return "https://wow.zamimg.com/images/wow/icons/medium/{$this->icon()}.jpg";
    }
}

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

    /**
     * Ícone da profissão (o mesmo mostrado no livro de receitas, no jogo).
     * Conferido um por um contra o JSON de tooltip do Wowhead pra WotLK
     * (endpoint usado pela própria página deles, mesmo mecanismo do
     * `App\Support\WowheadTooltipResolver`) — não é chute: vários aqui não
     * seguem o padrão óbvio `trade_<nome>` (ex.: Herborismo usa um ícone de
     * feitiço da natureza, Couraria usa um ícone genérico de kit de
     * armadura, Encantamento é "engraving", não "enchanting").
     */
    public function icon(): string
    {
        return match ($this) {
            self::Alchemy => 'trade_alchemy',
            self::Blacksmithing => 'trade_blacksmithing',
            self::Enchanting => 'trade_engraving',
            self::Engineering => 'trade_engineering',
            self::Herbalism => 'spell_nature_naturetouchgrow',
            self::Inscription => 'inv_inscription_tradeskill01',
            self::Jewelcrafting => 'inv_misc_gem_01',
            self::Leatherworking => 'inv_misc_armorkit_17',
            self::Mining => 'trade_mining',
            self::Skinning => 'inv_misc_pelt_wolf_01',
            self::Tailoring => 'trade_tailoring',
            self::Cooking => 'inv_misc_food_15',
            self::FirstAid => 'spell_holy_sealofsacrifice',
            self::Fishing => 'trade_fishing',
        };
    }

    public function iconUrl(): string
    {
        return "https://wow.zamimg.com/images/wow/icons/medium/{$this->icon()}.jpg";
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

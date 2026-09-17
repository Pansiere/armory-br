<?php

namespace App\Enums;

/**
 * As 3 árvores de talento de cada classe no 3.3.5 (dual spec — patch 3.1).
 * Nome do caso é composto (classe+árvore) porque o nome da árvore sozinho
 * colide entre classes (Proteção existe em Guerreiro e Paladino, Restauração
 * em Xamã e Druida, Gelo em Cavaleiro da Morte e Mago...).
 */
enum Spec: string
{
    case WarriorArms = 'warrior_arms';
    case WarriorFury = 'warrior_fury';
    case WarriorProtection = 'warrior_protection';

    case PaladinHoly = 'paladin_holy';
    case PaladinProtection = 'paladin_protection';
    case PaladinRetribution = 'paladin_retribution';

    case HunterBeastMastery = 'hunter_beast_mastery';
    case HunterMarksmanship = 'hunter_marksmanship';
    case HunterSurvival = 'hunter_survival';

    case RogueAssassination = 'rogue_assassination';
    case RogueCombat = 'rogue_combat';
    case RogueSubtlety = 'rogue_subtlety';

    case PriestDiscipline = 'priest_discipline';
    case PriestHoly = 'priest_holy';
    case PriestShadow = 'priest_shadow';

    case DeathKnightBlood = 'death_knight_blood';
    case DeathKnightFrost = 'death_knight_frost';
    case DeathKnightUnholy = 'death_knight_unholy';

    case ShamanElemental = 'shaman_elemental';
    case ShamanEnhancement = 'shaman_enhancement';
    case ShamanRestoration = 'shaman_restoration';

    case MageArcane = 'mage_arcane';
    case MageFire = 'mage_fire';
    case MageFrost = 'mage_frost';

    case WarlockAffliction = 'warlock_affliction';
    case WarlockDemonology = 'warlock_demonology';
    case WarlockDestruction = 'warlock_destruction';

    case DruidBalance = 'druid_balance';
    case DruidFeral = 'druid_feral';
    case DruidRestoration = 'druid_restoration';

    public function label(): string
    {
        return match ($this) {
            self::WarriorArms => 'Armas',
            self::WarriorFury => 'Fúria',
            self::WarriorProtection, self::PaladinProtection => 'Proteção',

            self::PaladinHoly, self::PriestHoly => 'Sagrado',
            self::PaladinRetribution => 'Retribuição',

            self::HunterBeastMastery => 'Domínio das Feras',
            self::HunterMarksmanship => 'Pontaria',
            self::HunterSurvival => 'Sobrevivência',

            self::RogueAssassination => 'Assassinato',
            self::RogueCombat => 'Combate',
            self::RogueSubtlety => 'Sutileza',

            self::PriestDiscipline => 'Disciplina',
            self::PriestShadow => 'Sombras',

            self::DeathKnightBlood => 'Sangue',
            self::DeathKnightFrost, self::MageFrost => 'Gelo',
            self::DeathKnightUnholy => 'Profano',

            self::ShamanElemental => 'Elemental',
            self::ShamanEnhancement => 'Aprimoramento',
            self::ShamanRestoration, self::DruidRestoration => 'Restauração',

            self::MageArcane => 'Arcano',
            self::MageFire => 'Fogo',

            self::WarlockAffliction => 'Aflição',
            self::WarlockDemonology => 'Demonologia',
            self::WarlockDestruction => 'Destruição',

            self::DruidBalance => 'Equilíbrio',
            self::DruidFeral => 'Feral',
        };
    }

    public function characterClass(): CharacterClass
    {
        return match ($this) {
            self::WarriorArms, self::WarriorFury, self::WarriorProtection => CharacterClass::Warrior,
            self::PaladinHoly, self::PaladinProtection, self::PaladinRetribution => CharacterClass::Paladin,
            self::HunterBeastMastery, self::HunterMarksmanship, self::HunterSurvival => CharacterClass::Hunter,
            self::RogueAssassination, self::RogueCombat, self::RogueSubtlety => CharacterClass::Rogue,
            self::PriestDiscipline, self::PriestHoly, self::PriestShadow => CharacterClass::Priest,
            self::DeathKnightBlood, self::DeathKnightFrost, self::DeathKnightUnholy => CharacterClass::DeathKnight,
            self::ShamanElemental, self::ShamanEnhancement, self::ShamanRestoration => CharacterClass::Shaman,
            self::MageArcane, self::MageFire, self::MageFrost => CharacterClass::Mage,
            self::WarlockAffliction, self::WarlockDemonology, self::WarlockDestruction => CharacterClass::Warlock,
            self::DruidBalance, self::DruidFeral, self::DruidRestoration => CharacterClass::Druid,
        };
    }

    /**
     * Ícone da árvore de talento (o mesmo mostrado na aba, no jogo). Conferido
     * um por um contra a calculadora de talentos do Wowhead pro 3.3.5 — não é
     * chute (alguns são reaproveitados entre classes/árvores diferentes, ex.:
     * warrior_arms e rogue_assassination usam o mesmo ícone no jogo).
     */
    public function icon(): string
    {
        return match ($this) {
            self::WarriorArms => 'ability_rogue_eviscerate',
            self::WarriorFury => 'ability_warrior_innerrage',
            self::WarriorProtection => 'inv_shield_06',

            self::PaladinHoly => 'spell_holy_holybolt',
            self::PaladinProtection => 'spell_holy_devotionaura',
            self::PaladinRetribution => 'spell_holy_auraoflight',

            self::HunterBeastMastery => 'ability_hunter_beasttaming',
            self::HunterMarksmanship => 'ability_marksmanship',
            self::HunterSurvival => 'ability_hunter_swiftstrike',

            self::RogueAssassination => 'ability_rogue_eviscerate',
            self::RogueCombat => 'ability_backstab',
            self::RogueSubtlety => 'ability_stealth',

            self::PriestDiscipline => 'spell_holy_wordfortitude',
            self::PriestHoly => 'spell_holy_guardianspirit',
            self::PriestShadow => 'spell_shadow_shadowwordpain',

            self::DeathKnightBlood => 'spell_deathknight_bloodpresence',
            self::DeathKnightFrost => 'spell_deathknight_frostpresence',
            self::DeathKnightUnholy => 'spell_deathknight_unholypresence',

            self::ShamanElemental => 'spell_nature_lightning',
            self::ShamanEnhancement => 'spell_nature_lightningshield',
            self::ShamanRestoration => 'spell_nature_magicimmunity',

            self::MageArcane => 'spell_holy_magicalsentry',
            self::MageFire => 'spell_fire_firebolt02',
            self::MageFrost => 'spell_frost_frostbolt02',

            self::WarlockAffliction => 'spell_shadow_deathcoil',
            self::WarlockDemonology => 'spell_shadow_metamorphosis',
            self::WarlockDestruction => 'spell_shadow_rainoffire',

            self::DruidBalance => 'spell_nature_starfall',
            self::DruidFeral => 'ability_racial_bearform',
            self::DruidRestoration => 'spell_nature_healingtouch',
        };
    }

    public function iconUrl(): string
    {
        return "https://wow.zamimg.com/images/wow/icons/medium/{$this->icon()}.jpg";
    }

    /**
     * As 3 specs da classe, na ordem canônica das abas no jogo.
     *
     * @return array<int, self>
     */
    public static function forClass(CharacterClass $class): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $spec) => $spec->characterClass() === $class,
        ));
    }
}

<?php

namespace App\Enums;

enum Raid: string
{
    case IcecrownCitadel = 'icc';
    case RubySanctum = 'rs';
    case TrialOfTheCrusader = 'toc';
    case VaultOfArchavon = 'voa';

    public function label(): string
    {
        return match ($this) {
            self::IcecrownCitadel => 'Cidadela de Icecrown',
            self::RubySanctum => 'Santuário do Rubi',
            self::TrialOfTheCrusader => 'Provação do Cruzado',
            self::VaultOfArchavon => 'Cofre de Archavon',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::IcecrownCitadel => 'ICC',
            self::RubySanctum => 'RS',
            self::TrialOfTheCrusader => 'ToC',
            self::VaultOfArchavon => 'VoA',
        };
    }
}

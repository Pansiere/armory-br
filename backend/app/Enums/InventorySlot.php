<?php

namespace App\Enums;

/**
 * Espelha o campo InventoryType do item_template (TrinityCore/AzerothCore).
 * Os 19 slots equipáveis do boneco (seção 7.1) são um subconjunto deste enum
 * — o restante existe pra classificar itens não-equipáveis (bolsa, munição...).
 */
enum InventorySlot: int
{
    case NonEquip = 0;
    case Head = 1;
    case Neck = 2;
    case Shoulders = 3;
    case Shirt = 4;
    case Chest = 5;
    case Waist = 6;
    case Legs = 7;
    case Feet = 8;
    case Wrists = 9;
    case Hands = 10;
    case Finger = 11;
    case Trinket = 12;
    case OneHand = 13;
    case Shield = 14;
    case Ranged = 15;
    case Back = 16;
    case TwoHand = 17;
    case Bag = 18;
    case Tabard = 19;
    case Robe = 20;
    case MainHand = 21;
    case OffHand = 22;
    case HeldInOffHand = 23;
    case Ammo = 24;
    case Thrown = 25;
    case RangedRight = 26;
    case Quiver = 27;
    case Relic = 28;

    public function label(): string
    {
        return match ($this) {
            self::NonEquip => 'Não equipável',
            self::Head => 'Cabeça',
            self::Neck => 'Pescoço',
            self::Shoulders => 'Ombros',
            self::Shirt => 'Camisa',
            self::Chest, self::Robe => 'Peito',
            self::Waist => 'Cintura',
            self::Legs => 'Pernas',
            self::Feet => 'Pés',
            self::Wrists => 'Pulsos',
            self::Hands => 'Mãos',
            self::Finger => 'Anel',
            self::Trinket => 'Berloque',
            self::OneHand, self::TwoHand, self::MainHand => 'Mão principal',
            self::Shield, self::OffHand, self::HeldInOffHand => 'Mão secundária',
            self::Ranged, self::RangedRight, self::Thrown => 'À distância',
            self::Relic => 'Relíquia',
            self::Back => 'Costas',
            self::Bag => 'Bolsa',
            self::Tabard => 'Tabardo',
            self::Ammo, self::Quiver => 'Munição',
        };
    }

    /**
     * Os 19 slots que aparecem no boneco visual (seção 7.1).
     */
    public function isEquipmentDollSlot(): bool
    {
        return $this !== self::NonEquip
            && $this !== self::Bag
            && $this !== self::Ammo
            && $this !== self::Quiver;
    }
}

<?php

namespace App\Enums;

/**
 * Os 19 slots do boneco visual (seção 7.1). Diferente de InventorySlot (que
 * vem do item_template e descreve o TIPO de slot que um item aceita), este
 * enum é a POSIÇÃO no boneco — por isso anel e berloque têm duas entradas
 * cada, mesmo aceitando o mesmo InventorySlot.
 */
enum EquipmentSlot: string
{
    case Head = 'head';
    case Neck = 'neck';
    case Shoulders = 'shoulders';
    case Back = 'back';
    case Chest = 'chest';
    case Shirt = 'shirt';
    case Tabard = 'tabard';
    case Wrists = 'wrists';
    case Hands = 'hands';
    case Waist = 'waist';
    case Legs = 'legs';
    case Feet = 'feet';
    case Ring1 = 'ring_1';
    case Ring2 = 'ring_2';
    case Trinket1 = 'trinket_1';
    case Trinket2 = 'trinket_2';
    case MainHand = 'main_hand';
    case OffHand = 'off_hand';
    case Ranged = 'ranged';

    public function label(): string
    {
        return match ($this) {
            self::Head => 'Cabeça',
            self::Neck => 'Pescoço',
            self::Shoulders => 'Ombros',
            self::Back => 'Costas',
            self::Chest => 'Peito',
            self::Shirt => 'Camisa',
            self::Tabard => 'Tabardo',
            self::Wrists => 'Pulsos',
            self::Hands => 'Mãos',
            self::Waist => 'Cintura',
            self::Legs => 'Pernas',
            self::Feet => 'Pés',
            self::Ring1 => 'Anel 1',
            self::Ring2 => 'Anel 2',
            self::Trinket1 => 'Berloque 1',
            self::Trinket2 => 'Berloque 2',
            self::MainHand => 'Mão principal',
            self::OffHand => 'Mão secundária',
            self::Ranged => 'À distância / relíquia',
        };
    }

    /**
     * Coluna esquerda, coluna direita ou barra de baixo — layout da seção 7.1.
     *
     * @return 'left'|'right'|'bottom'
     */
    public function column(): string
    {
        return match ($this) {
            self::Head, self::Neck, self::Shoulders, self::Back,
            self::Chest, self::Shirt, self::Tabard, self::Wrists => 'left',
            self::Hands, self::Waist, self::Legs, self::Feet,
            self::Ring1, self::Ring2, self::Trinket1, self::Trinket2 => 'right',
            self::MainHand, self::OffHand, self::Ranged => 'bottom',
        };
    }

    /**
     * Quais InventorySlot (do item_template) um item precisa ter pra caber
     * nesse slot do boneco.
     *
     * @return array<int, InventorySlot>
     */
    public function acceptedInventorySlots(): array
    {
        return match ($this) {
            self::Head => [InventorySlot::Head],
            self::Neck => [InventorySlot::Neck],
            self::Shoulders => [InventorySlot::Shoulders],
            self::Back => [InventorySlot::Back],
            self::Chest => [InventorySlot::Chest, InventorySlot::Robe],
            self::Shirt => [InventorySlot::Shirt],
            self::Tabard => [InventorySlot::Tabard],
            self::Wrists => [InventorySlot::Wrists],
            self::Hands => [InventorySlot::Hands],
            self::Waist => [InventorySlot::Waist],
            self::Legs => [InventorySlot::Legs],
            self::Feet => [InventorySlot::Feet],
            self::Ring1, self::Ring2 => [InventorySlot::Finger],
            self::Trinket1, self::Trinket2 => [InventorySlot::Trinket],
            self::MainHand => [InventorySlot::OneHand, InventorySlot::TwoHand, InventorySlot::MainHand],
            self::OffHand => [InventorySlot::Shield, InventorySlot::OffHand, InventorySlot::HeldInOffHand],
            self::Ranged => [InventorySlot::Ranged, InventorySlot::RangedRight, InventorySlot::Relic, InventorySlot::Thrown],
        };
    }
}

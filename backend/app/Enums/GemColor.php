<?php

namespace App\Enums;

/**
 * Bitmask de cor de socket/gema do 3.3.5 — valores conferidos contra os
 * dados reais do `item_template` do AzerothCore (`socketColor_N`), não
 * chutados. Um socket ou gema pode combinar mais de uma cor ao mesmo tempo
 * (ex.: 14 = vermelho|amarelo|azul, "prismático"), por isso é bitmask e não
 * um enum comum — os métodos estáticos operam sobre o int guardado em
 * `items.socket_color_N` / `items.gem_color`.
 */
enum GemColor: int
{
    case Meta = 1;
    case Red = 2;
    case Yellow = 4;
    case Blue = 8;

    public function label(): string
    {
        return match ($this) {
            self::Meta => 'Meta',
            self::Red => 'Vermelho',
            self::Yellow => 'Amarelo',
            self::Blue => 'Azul',
        };
    }

    /**
     * Cor pra desenhar o selo do socket/gema na UI.
     */
    public function hex(): string
    {
        return match ($this) {
            self::Meta => '#8a8a8a',
            self::Red => '#c0392b',
            self::Yellow => '#e1c542',
            self::Blue => '#2f7bc0',
        };
    }

    /**
     * As cores individuais presentes num bitmask (ex.: 14 -> [Red, Yellow, Blue]).
     *
     * @return array<int, self>
     */
    public static function fromMask(int $mask): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $color) => ($mask & $color->value) !== 0,
        ));
    }

    /**
     * Se a gema (pelo bitmask dela) pode ser encaixada num socket dessa cor.
     * Meta só combina com meta — não entra na lógica de "qualquer uma bate".
     */
    public static function gemFitsSocket(int $gemMask, int $socketMask): bool
    {
        if ($socketMask === self::Meta->value || $gemMask === self::Meta->value) {
            return $gemMask === $socketMask;
        }

        return ($gemMask & $socketMask) !== 0;
    }
}

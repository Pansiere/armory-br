<?php

namespace App\Support;

use App\Models\Character;
use GdImage;

/**
 * Imagem de preview (og:image) pra quando o link público de um personagem é
 * colado no Discord/etc — seção 7.3. Gerada com GD (já vem com o PHP, sem
 * dependência pesada tipo navegador headless) usando as fontes Cinzel e
 * Instrument Sans bundladas em resources/fonts (OFL, mesmas do app).
 *
 * Sem ícone de item ainda (seção 8) — o resumo do equipamento é só um
 * quadradinho colorido por qualidade, por enquanto.
 */
class CharacterPreviewImage
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    public function __construct(private readonly Character $character) {}

    public function render(): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $background = $this->color($image, '#15100c');
        imagefilledrectangle($image, 0, 0, self::WIDTH, self::HEIGHT, $background);

        $factionColor = $this->color($image, $this->character->faction->color());
        imagefilledrectangle($image, 0, 0, self::WIDTH, 12, $factionColor);

        $cinzel = resource_path('fonts/Cinzel.ttf');
        $sans = resource_path('fonts/InstrumentSans.ttf');

        $muted = $this->color($image, '#d9c8a0');
        $parchment = $this->color($image, '#f8f1de');
        $classColor = $this->color($image, $this->character->class->color());

        imagettftext($image, 16, 0, 60, 70, $muted, $cinzel, 'ARMORY BR');

        imagettftext($image, 52, 0, 60, 200, $classColor, $cinzel, $this->truncate($this->character->name, 22));

        $info = collect([
            $this->character->faction->label(),
            $this->character->class->label(),
            $this->character->level ? 'Nível '.$this->character->level : null,
            $this->character->race?->label(),
        ])->filter()->implode('   ·   ');

        imagettftext($image, 22, 0, 60, 248, $parchment, $sans, $info);

        if ($this->character->professions->isNotEmpty()) {
            $professions = $this->character->professions
                ->map(fn ($profession) => $profession->name->label())
                ->implode('   ·   ');

            imagettftext($image, 18, 0, 60, 288, $muted, $sans, $this->truncate($professions, 70));
        }

        $this->drawEquipmentSummary($image, $sans, $muted);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function drawEquipmentSummary(GdImage $image, string $sansFont, int $mutedColor): void
    {
        if ($this->character->items->isEmpty()) {
            return;
        }

        imagettftext($image, 16, 0, 60, 360, $mutedColor, $sansFont, 'EQUIPAMENTO');

        $x = 60;
        $y = 380;
        $size = 36;
        $gap = 10;

        foreach ($this->character->items as $characterItem) {
            $color = $this->color($image, $characterItem->item->quality->color());
            imagefilledrectangle($image, $x, $y, $x + $size, $y + $size, $color);

            $x += $size + $gap;
            if ($x > self::WIDTH - 100) {
                $x = 60;
                $y += $size + $gap;
            }
        }
    }

    private function truncate(string $text, int $maxLength): string
    {
        return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength - 1).'…' : $text;
    }

    private function color(GdImage $image, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($image, $r, $g, $b);
    }
}

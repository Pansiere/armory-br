<?php

namespace App\Support;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\Item;
use GdImage;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Imagem de preview (og:image) pra quando o link público de um personagem é
 * colado no Discord/etc — seção 7.3. Gerada com GD (já vem com o PHP, sem
 * dependência pesada tipo navegador headless) usando as fontes Cinzel e
 * Instrument Sans bundladas em resources/fonts (OFL, mesmas do app).
 *
 * Os ícones dos itens equipados são buscados do CDN do Wowhead em paralelo
 * (Http::pool) e cacheados por 30 dias — o nome do ícone nunca muda pra um
 * item existente. Se um ícone não resolver (rede fora, item sem ícone
 * conhecido), cai de volta pro quadradinho colorido por qualidade.
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

        $primarySpec = $this->character->primarySpec();
        $averageItemLevel = $primarySpec?->averageItemLevel();

        $info = collect([
            $this->character->faction->label(),
            $this->character->class->label(),
            $primarySpec?->spec->label(),
            $this->character->level ? 'Nível '.$this->character->level : null,
            $this->character->race?->label(),
            $averageItemLevel !== null ? 'ilvl '.$averageItemLevel : null,
        ])->filter()->implode('   ·   ');

        imagettftext($image, 22, 0, 60, 248, $parchment, $sans, $info);

        if ($this->character->professions->isNotEmpty()) {
            $professions = $this->character->professions
                ->map(fn ($profession) => $profession->name->label())
                ->implode('   ·   ');

            imagettftext($image, 18, 0, 60, 288, $muted, $sans, $this->truncate($professions, 70));
        }

        $icons = $this->drawEquipmentSummary($image, $sans, $muted);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        foreach ($icons as $icon) {
            imagedestroy($icon);
        }

        return $png;
    }

    /**
     * @return array<string, GdImage>
     */
    private function drawEquipmentSummary(GdImage $image, string $sansFont, int $mutedColor): array
    {
        $primarySpec = $this->character->primarySpec();
        $items = $primarySpec ? $primarySpec->items : collect();

        if ($items->isEmpty()) {
            return [];
        }

        imagettftext($image, 16, 0, 60, 360, $mutedColor, $sansFont, 'EQUIPAMENTO');

        $icons = $this->fetchIcons($items);

        $x = 60;
        $y = 380;
        $size = 36;
        $inset = 3;
        $gap = 10;

        foreach ($items as $characterItem) {
            $borderColor = $this->color($image, $characterItem->item->quality->color());
            imagefilledrectangle($image, $x, $y, $x + $size, $y + $size, $borderColor);

            $icon = $icons[$characterItem->item->icon] ?? null;
            if ($icon !== null) {
                $innerSize = $size - ($inset * 2);
                imagecopyresampled(
                    $image, $icon,
                    $x + $inset, $y + $inset, 0, 0,
                    $innerSize, $innerSize, imagesx($icon), imagesy($icon),
                );
            }

            $x += $size + $gap;
            if ($x > self::WIDTH - 100) {
                $x = 60;
                $y += $size + $gap;
            }
        }

        return $icons;
    }

    /**
     * Busca (com cache) os ícones dos itens equipados, decodificados como
     * GdImage. Falhas individuais (rede, ícone corrompido) são ignoradas —
     * o item correspondente simplesmente não entra no array de retorno.
     *
     * @param  Collection<int, CharacterItem>  $characterItems
     * @return array<string, GdImage>
     */
    private function fetchIcons($characterItems): array
    {
        $items = $characterItems
            ->map(fn ($characterItem) => $characterItem->item)
            ->filter(fn (?Item $item) => $item !== null && $item->icon !== null)
            ->unique('icon')
            ->values();

        if ($items->isEmpty()) {
            return [];
        }

        $bytesByName = [];
        $toFetch = [];

        foreach ($items as $item) {
            $cached = Cache::get("item-icon-bytes:{$item->icon}");

            if ($cached !== null) {
                $bytesByName[$item->icon] = base64_decode($cached);
            } else {
                $toFetch[] = $item;
            }
        }

        if ($toFetch !== []) {
            $responses = Http::pool(fn (Pool $pool) => collect($toFetch)
                ->map(fn (Item $item) => $pool->as($item->icon)->timeout(3)->get($item->iconUrl()))
                ->all());

            foreach ($toFetch as $item) {
                $response = $responses[$item->icon] ?? null;

                if ($response instanceof Response && $response->successful()) {
                    $bytesByName[$item->icon] = $response->body();
                    // O driver de cache "database" exige texto válido em UTF-8;
                    // bytes crus de um JPEG não são — por isso base64 aqui.
                    Cache::put("item-icon-bytes:{$item->icon}", base64_encode($response->body()), now()->addDays(30));
                }
            }
        }

        $images = [];

        foreach ($bytesByName as $name => $bytes) {
            $decoded = @imagecreatefromstring($bytes);

            if ($decoded !== false) {
                $images[$name] = $decoded;
            }
        }

        return $images;
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

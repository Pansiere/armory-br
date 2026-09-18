<?php

namespace App\Support;

use App\Models\Item;
use Illuminate\Support\Facades\Http;

/**
 * Busca o HTML de tooltip que o próprio Wowhead usa nas páginas dele —
 * endpoint interno que alimenta o script "Wowhead Tooltips" deles
 * (`nether.wowhead.com/wotlk/tooltip/item/...`, sem autenticação).
 * Confirmado contra o que o wowsims/wotlk usa (mesmo endpoint, mesmo
 * `lvl=80` fixo — só importa pra itens que escalam com o nível do
 * personagem, como heirlooms, caso raro o bastante pra não valer a
 * complexidade de guardar uma versão por nível).
 *
 * O HTML é guardado direto na linha do item (`items.tooltip_html`) na
 * primeira vez que alguém passa o mouse nele — item de jogo é dado
 * estático, não faz sentido buscar nunca mais depois disso. Diferente do
 * ícone/gema (resolvidos em lote no `items:import`), isso é sob demanda
 * porque o Wowhead só devolve um item por request, e a tabela tem dezenas
 * de milhares de linhas — buscar tudo de uma vez no import demoraria
 * horas e arriscaria bloqueio por excesso de requisições.
 */
class WowheadTooltipResolver
{
    private const ENDPOINT = 'https://nether.wowhead.com/wotlk/tooltip/item/%d?lvl=80';

    public function resolve(Item $item): ?string
    {
        if ($item->tooltip_html !== null) {
            return $item->tooltip_html;
        }

        $response = Http::timeout(5)->get(sprintf(self::ENDPOINT, $item->item_id));

        if (! $response->successful()) {
            return null;
        }

        $html = $response->json('tooltip');

        if (! is_string($html) || $html === '') {
            return null;
        }

        $item->update(['tooltip_html' => $html]);

        return $html;
    }
}

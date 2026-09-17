<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Support\CharacterPreviewImage;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicCharacterController extends Controller
{
    /**
     * Página pública do personagem — Blade puro, sem Inertia/React, de
     * propósito: bots de preview (Discord, WhatsApp, etc.) não executam
     * JavaScript, então as meta tags og:* precisam estar no HTML que o
     * servidor manda de cara. Configurar SSR só pra essa página seria peso
     * desnecessário pro projeto (seção 2 do spec: "leve por princípio").
     */
    public function show(string $token): View
    {
        $character = $this->findPublicCharacter($token);

        return view('public.character', ['character' => $character]);
    }

    public function image(string $token): Response
    {
        $character = $this->findPublicCharacter($token);

        $png = (new CharacterPreviewImage($character))->render();

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function findPublicCharacter(string $token): Character
    {
        return Character::query()
            ->where('public_token', $token)
            ->where('is_public', true)
            ->with(['professions', 'items.item'])
            ->firstOrFail();
    }
}

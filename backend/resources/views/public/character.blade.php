<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $summary = $character->class->label().' '.$character->faction->label();
            $summary .= $character->level ? ', nível '.$character->level : '';
            $summary .= $character->averageItemLevel() ? ', ilvl '.$character->averageItemLevel() : '';
        @endphp

        <title>{{ $character->name }} — {{ config('app.name') }}</title>
        <meta name="description" content="{{ $summary }} — vitrine de personagem de WoW 3.3.5.">

        <meta property="og:type" content="profile">
        <meta property="og:title" content="{{ $character->name }} — {{ config('app.name') }}">
        <meta property="og:description" content="{{ $summary }}">
        <meta property="og:image" content="{{ route('characters.public.image', $character->public_token) }}">
        <meta property="og:url" content="{{ route('characters.public', $character->public_token) }}">
        <meta name="twitter:card" content="summary_large_image">

        <link rel="icon" href="/favicon.ico" sizes="any">

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-tavern-950 text-parchment-100">
        <div class="mx-auto max-w-2xl px-4 py-12">
            <a href="{{ url('/') }}" class="font-heading text-2xl font-bold text-parchment-100">
                Armory BR
            </a>

            <div class="mt-8 rounded-lg border-l-4 bg-tavern-900 p-6" style="border-left-color: {{ $character->class->color() }}">
                <h1 class="font-heading text-3xl font-bold" style="color: {{ $character->class->color() }}">
                    {{ $character->name }}
                    @if ($character->level)
                        <span class="text-parchment-300">({{ $character->level }})</span>
                    @endif
                </h1>

                <p class="mt-1 text-parchment-200">
                    {{ $character->faction->label() }} · {{ $character->class->label() }}
                    @if ($character->spec)
                        · {{ $character->spec }}
                    @endif
                    @if ($character->race)
                        · {{ $character->race->label() }}
                    @endif
                </p>

                @if ($character->professions->isNotEmpty())
                    <p class="mt-2 text-sm text-parchment-300">
                        {{ $character->professions->map(fn ($profession) => $profession->name->label())->join(' · ') }}
                    </p>
                @endif

                @if ($character->averageItemLevel())
                    <p class="mt-2 text-sm text-parchment-300">
                        Item level médio: {{ $character->averageItemLevel() }}
                    </p>
                @endif
            </div>

            @if ($character->items->isNotEmpty())
                <div class="mt-6">
                    <h2 class="mb-3 font-heading text-lg font-semibold text-parchment-100">
                        Equipamento
                    </h2>

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($character->items as $characterItem)
                            <div
                                class="flex items-center gap-2 rounded-md border-l-4 bg-tavern-900 px-3 py-2 text-sm"
                                style="border-left-color: {{ $characterItem->item->quality->color() }}"
                            >
                                <span class="w-28 shrink-0 text-xs text-parchment-300">
                                    {{ $characterItem->slot->label() }}
                                </span>
                                <span style="color: {{ $characterItem->item->quality->color() }}">
                                    {{ $characterItem->item->name }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="mt-10 text-xs text-parchment-300">
                World of Warcraft, seus nomes, ícones e arte pertencem à Blizzard
                Entertainment. O Armory BR não é afiliado, patrocinado nem endossado
                pela Blizzard.
            </p>

            <a href="{{ url('/') }}" class="mt-4 inline-block text-sm text-parchment-300 underline">
                Cadastre seus personagens no Armory BR
            </a>
        </div>
    </body>
</html>

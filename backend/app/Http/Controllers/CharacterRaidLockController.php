<?php

namespace App\Http\Controllers;

use App\Enums\Raid;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class CharacterRaidLockController extends Controller
{
    public function update(Request $request, Character $character, string $raid, int $size): RedirectResponse
    {
        Gate::authorize('update', $character);

        $raidEnum = Raid::tryFrom($raid);
        abort_if($raidEnum === null, 404);
        abort_unless(in_array($size, [10, 25], true), 404);

        $validated = $request->validate([
            'heroic' => ['nullable', 'boolean'],
        ]);

        $character->raidLocks()->updateOrCreate(
            ['raid' => $raidEnum->value, 'size' => $size],
            ['heroic' => $validated['heroic'] ?? false, 'locked_at' => Carbon::now()],
        );

        return back();
    }

    public function destroy(Character $character, string $raid, int $size): RedirectResponse
    {
        Gate::authorize('update', $character);

        $raidEnum = Raid::tryFrom($raid);
        abort_if($raidEnum === null, 404);
        abort_unless(in_array($size, [10, 25], true), 404);

        $character->raidLocks()->where('raid', $raidEnum->value)->where('size', $size)->delete();

        return back();
    }
}

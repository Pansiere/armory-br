<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    public function view(User $user, Character $character): bool
    {
        return $user->id === $character->user_id;
    }

    public function update(User $user, Character $character): bool
    {
        return $user->id === $character->user_id;
    }

    public function delete(User $user, Character $character): bool
    {
        return $user->id === $character->user_id;
    }
}

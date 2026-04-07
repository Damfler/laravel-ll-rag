<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

class SpacePolicy
{
    // SpaceController: edit / update
    public function update(User $user, Space $space): bool
    {
        return $user->isAdmin() || $space->owner_id === $user->id;
    }

    // SpaceController: destroy
    public function delete(User $user, Space $space): bool
    {
        return $user->isAdmin() || $space->owner_id === $user->id;
    }

    // PageController: create / store / edit / update / destroy
    public function editor(User $user, Space $space): bool
    {
        return $user->isEditor() || $space->owner_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\ActionPack;
use App\Models\User;

class ActionPackPolicy
{
    public function view(User $user, ActionPack $actionPack): bool
    {
        return $user->workspaces()
            ->where('workspaces.id', $actionPack->workspace_id)
            ->exists();
    }
}

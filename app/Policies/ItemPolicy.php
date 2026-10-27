<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function view(User $user, Item $item): bool
    {
        return $user->workspaces()
            ->where('workspaces.id', $item->workspace_id)
            ->exists();
    }
}

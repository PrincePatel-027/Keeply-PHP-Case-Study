<?php

namespace App\Policies;

use App\Models\ResolutionCase;
use App\Models\User;

class ResolutionCasePolicy
{
    public function view(User $user, ResolutionCase $case): bool
    {
        return $user->workspaces()
            ->where('workspaces.id', $case->workspace_id)
            ->exists();
    }
}

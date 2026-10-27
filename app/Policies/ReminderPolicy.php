<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    public function update(User $user, Reminder $reminder): bool
    {
        return $user->workspaces()
            ->where('workspaces.id', $reminder->workspace_id)
            ->exists();
    }
}

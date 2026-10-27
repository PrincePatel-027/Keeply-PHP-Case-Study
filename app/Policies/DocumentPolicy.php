<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->workspaces()
            ->where('workspaces.id', $document->workspace_id)
            ->exists();
    }
}

<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AccountDeletionService
{
    public const RETENTION_DAYS = 30;

    public function request(User $user): void
    {
        $hasSharedWorkspace = $user->workspaces()
            ->withCount('members')
            ->get()
            ->contains(fn (Workspace $workspace): bool => $workspace->members_count > 1);

        if ($hasSharedWorkspace) {
            throw ValidationException::withMessages([
                'account' => 'Account deletion is blocked while you belong to a shared workspace. Transfer or leave that workspace first.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete();
        });
    }

    public function purge(User $user): void
    {
        $workspaces = $user->workspaces()->withCount('members')->get();

        if ($workspaces->contains(fn (Workspace $workspace): bool => $workspace->members_count > 1)) {
            return;
        }

        $workspaceIds = $workspaces->pluck('id');
        $files = Document::withTrashed()
            ->whereIn('workspace_id', $workspaceIds)
            ->get(['storage_disk', 'storage_path']);

        foreach ($files as $file) {
            if (! Storage::disk($file->storage_disk)->delete($file->storage_path)) {
                throw new RuntimeException('A private account file could not be removed. The purge will be retried.');
            }
        }

        DB::transaction(function () use ($user, $workspaceIds): void {
            Workspace::query()->whereIn('id', $workspaceIds)->delete();
            $user->forceDelete();
        });
    }
}

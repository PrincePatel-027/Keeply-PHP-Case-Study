<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Console\Command;

class PurgeDeletedAccounts extends Command
{
    protected $signature = 'accounts:purge-deleted';

    protected $description = 'Permanently purge accounts after the deletion recovery period';

    public function handle(AccountDeletionService $deletions): int
    {
        $purged = 0;

        User::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(AccountDeletionService::RETENTION_DAYS))
            ->orderBy('id')
            ->eachById(function (User $user) use ($deletions, &$purged): void {
                $deletions->purge($user);

                if (! User::withTrashed()->whereKey($user->id)->exists()) {
                    $purged++;
                }
            });

        $this->info("Purged {$purged} account(s).");

        return self::SUCCESS;
    }
}

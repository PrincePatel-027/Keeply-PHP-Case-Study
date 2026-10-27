<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderEmail;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Throwable;

class DispatchReminderEmails extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Queue email delivery for due follow-up reminders';

    public function handle(): int
    {
        $queued = 0;

        Reminder::query()
            ->pending()
            ->where('due_at', '<=', now())
            ->whereNull('queued_at')
            ->whereNull('sent_at')
            ->orderBy('id')
            ->eachById(function (Reminder $reminder) use (&$queued): void {
                $claimed = Reminder::query()
                    ->whereKey($reminder->id)
                    ->whereNull('queued_at')
                    ->whereNull('sent_at')
                    ->update(['queued_at' => now()]);

                if ($claimed === 0) {
                    return;
                }

                try {
                    SendReminderEmail::dispatch($reminder->id);
                    $queued++;
                } catch (Throwable $exception) {
                    Reminder::query()
                        ->whereKey($reminder->id)
                        ->whereNull('sent_at')
                        ->update(['queued_at' => null]);

                    throw $exception;
                }
            });

        $this->info("Queued {$queued} reminder email(s).");

        return self::SUCCESS;
    }
}

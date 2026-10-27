<?php

namespace App\Jobs;

use App\Mail\FollowUpReminderMail;
use App\Models\Reminder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendReminderEmail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $reminderId)
    {
        $this->onQueue('emails');
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function uniqueId(): string
    {
        return (string) $this->reminderId;
    }

    public function handle(): void
    {
        $reminder = Reminder::query()
            ->with(['user', 'resolutionCase.item', 'caseEvent'])
            ->find($this->reminderId);

        if ($reminder === null
            || $reminder->sent_at !== null
            || $reminder->completed_at !== null
            || $reminder->cancelled_at !== null
            || $reminder->due_at->isFuture()) {
            return;
        }

        Mail::to($reminder->user)->send(new FollowUpReminderMail($reminder));

        $reminder->forceFill(['sent_at' => now()])->save();
    }

    public function failed(?Throwable $exception): void
    {
        Reminder::query()
            ->whereKey($this->reminderId)
            ->whereNull('sent_at')
            ->update(['queued_at' => null]);
    }
}

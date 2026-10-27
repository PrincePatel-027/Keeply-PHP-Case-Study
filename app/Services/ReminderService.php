<?php

namespace App\Services;

use App\Models\CaseEvent;

class ReminderService
{
    public function syncFromEvent(CaseEvent $event, int $userId): void
    {
        if ($event->promised_follow_up_at === null) {
            $event->reminder()
                ->whereNull('completed_at')
                ->update(['cancelled_at' => now()]);

            return;
        }

        $event->loadMissing('resolutionCase');
        $reminder = $event->reminder()->firstOrNew();
        $deliveryMustReset = ! $reminder->exists
            || $reminder->cancelled_at !== null
            || ! $reminder->due_at->equalTo($event->promised_follow_up_at);

        $reminder->fill([
            'case_event_id' => $event->id,
            'workspace_id' => $event->resolutionCase->workspace_id,
            'resolution_case_id' => $event->resolution_case_id,
            'user_id' => $reminder->exists ? $reminder->user_id : $userId,
            'type' => 'promised_follow_up',
            'due_at' => $event->promised_follow_up_at,
            'queued_at' => $deliveryMustReset ? null : $reminder->queued_at,
            'sent_at' => $deliveryMustReset ? null : $reminder->sent_at,
            'cancelled_at' => null,
        ]);
        $reminder->save();
    }
}

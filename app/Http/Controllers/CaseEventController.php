<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCaseEventRequest;
use App\Models\CaseEvent;
use App\Models\ResolutionCase;
use App\Services\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CaseEventController extends Controller
{
    public function store(
        SaveCaseEventRequest $request,
        ResolutionCase $resolutionCase,
        ReminderService $reminders,
    ): RedirectResponse {
        Gate::authorize('view', $resolutionCase);

        DB::transaction(function () use ($request, $resolutionCase, $reminders): void {
            $event = $resolutionCase->events()->create($this->payload($request) + [
                'created_by' => $request->user()->id,
            ]);

            $reminders->syncFromEvent($event, $request->user()->id);
        });

        return redirect()->route('cases.show', $resolutionCase)->with('status', 'Timeline entry added.');
    }

    public function edit(ResolutionCase $resolutionCase, CaseEvent $caseEvent): View
    {
        $this->authorizeEvent($resolutionCase, $caseEvent);

        return view('cases.events.edit', compact('resolutionCase', 'caseEvent'));
    }

    public function update(
        SaveCaseEventRequest $request,
        ResolutionCase $resolutionCase,
        CaseEvent $caseEvent,
        ReminderService $reminders,
    ): RedirectResponse {
        $this->authorizeEvent($resolutionCase, $caseEvent);

        DB::transaction(function () use ($request, $caseEvent, $reminders): void {
            $caseEvent->update($this->payload($request));
            $reminders->syncFromEvent($caseEvent, $request->user()->id);
        });

        return redirect()->route('cases.show', $resolutionCase)->with('status', 'Timeline entry updated.');
    }

    private function payload(SaveCaseEventRequest $request): array
    {
        $validated = $request->validated();

        return [
            'event_type' => $validated['event_type'],
            'occurred_at' => $validated['occurred_at'],
            'channel' => $validated['channel'] ?? null,
            'party_name' => $validated['party_name'] ?? null,
            'summary' => $validated['summary'],
            'reference' => $validated['reference'] ?? null,
            'promised_follow_up_at' => $validated['promised_follow_up_at'] ?? null,
            'expense_minor' => isset($validated['expense'])
                ? (int) round((float) $validated['expense'] * 100)
                : null,
        ];
    }

    private function authorizeEvent(ResolutionCase $case, CaseEvent $event): void
    {
        Gate::authorize('view', $case);
        abort_unless($event->resolution_case_id === $case->id, 404);
    }
}

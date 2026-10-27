<?php

namespace App\Services;

use App\Models\ResolutionCase;
use Illuminate\Validation\ValidationException;

class CaseStatusService
{
    private const TRANSITIONS = [
        'draft' => ['active', 'closed_unresolved'],
        'active' => ['waiting_on_business', 'action_required', 'closed_unresolved'],
        'waiting_on_business' => ['active', 'action_required', 'closed_unresolved'],
        'action_required' => ['active', 'waiting_on_business', 'closed_unresolved'],
        'resolved' => [],
        'closed_unresolved' => [],
    ];

    public function allowedTransitions(ResolutionCase $case): array
    {
        return self::TRANSITIONS[$case->status] ?? [];
    }

    public function transition(ResolutionCase $case, string $nextStatus): void
    {
        if (! in_array($nextStatus, $this->allowedTransitions($case), true)) {
            throw ValidationException::withMessages([
                'status' => "This case cannot move from {$case->statusLabel()} to ".str($nextStatus)->headline().'.',
            ]);
        }

        $case->update([
            'status' => $nextStatus,
            'resolved_at' => $nextStatus === 'closed_unresolved' ? now() : null,
        ]);
    }
}

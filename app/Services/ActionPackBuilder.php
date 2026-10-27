<?php

namespace App\Services;

use App\Models\CaseAssessment;
use App\Models\ResolutionCase;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActionPackBuilder
{
    public function __construct(private readonly EvidenceChecklistService $evidenceChecklistService) {}

    public function defaultLetter(ResolutionCase $case, CaseAssessment $assessment, User $user): string
    {
        $case->loadMissing('item');
        $purchaseDate = $assessment->snapshot['item']['purchase_date']
            ? date('j F Y', strtotime($assessment->snapshot['item']['purchase_date']))
            : 'an unrecorded date';
        $warranty = $assessment->snapshot['coverage']['warranty'];
        $warrantyPosition = Str::headline($warranty['state']);
        $outcome = Str::lower(Str::headline($case->desired_outcome));

        return <<<LETTER
Subject: Request for {$outcome} — {$case->item->name}

Hello,

I am writing about {$case->item->name}, purchased on {$purchaseDate}.

The issue recorded is: {$case->problem_summary}

I am requesting {$outcome}. Keeply assessment v{$assessment->version} records the warranty position as {$warrantyPosition}. This is a calculation from the dates entered in my record and does not interpret warranty terms.

Please confirm the next step and provide a reference number for this request.

Regards,
{$user->name}
{$user->email}
LETTER;
    }

    public function build(
        ResolutionCase $case,
        CaseAssessment $assessment,
        Collection $documents,
        string $letterBody,
        User $user,
    ): array {
        $case->loadMissing(['item.category', 'events']);

        return [
            'generated_at' => now(),
            'owner' => $user,
            'case' => $case,
            'item' => $case->item,
            'assessment' => $assessment,
            'documents' => $documents,
            'events' => $case->events,
            'checklist' => $this->evidenceChecklistService->build($case, $assessment),
            'letter_body' => $letterBody,
        ];
    }
}

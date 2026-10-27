<?php

namespace App\Services;

use App\Models\ResolutionCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolutionOutcomeService
{
    public function record(ResolutionCase $case, array $data, int $userId): ResolutionCase
    {
        return DB::transaction(function () use ($case, $data, $userId): ResolutionCase {
            $lockedCase = ResolutionCase::query()
                ->whereKey($case->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCase->resolution_type !== null || $lockedCase->status === 'resolved') {
                throw ValidationException::withMessages([
                    'outcome_type' => 'A final outcome has already been recorded for this case.',
                ]);
            }

            if (
                $lockedCase->status === 'closed_unresolved'
                && ! in_array($data['outcome_type'], ['rejected', 'abandoned'], true)
            ) {
                throw ValidationException::withMessages([
                    'outcome_type' => 'A closed unresolved case can only record a rejected or abandoned outcome.',
                ]);
            }

            $terminalStatus = in_array($data['outcome_type'], ['rejected', 'abandoned'], true)
                ? 'closed_unresolved'
                : 'resolved';
            $resolutionValue = isset($data['resolution_value'])
                ? (int) round((float) $data['resolution_value'] * 100)
                : null;

            $lockedCase->update([
                'status' => $terminalStatus,
                'resolution_type' => $data['outcome_type'],
                'resolution_value_minor' => $resolutionValue,
                'resolved_at' => $data['occurred_at'],
            ]);

            $lockedCase->events()->create([
                'created_by' => $userId,
                'event_type' => 'outcome',
                'occurred_at' => $data['occurred_at'],
                'summary' => $data['summary'],
            ]);

            $lockedCase->reminders()
                ->pending()
                ->update(['cancelled_at' => now()]);

            return $lockedCase->fresh();
        });
    }
}

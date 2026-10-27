<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ResolutionCase;
use App\ValueObjects\CoveragePosition;
use Carbon\CarbonImmutable;

class CoverageCalculator
{
    public function calculate(
        Item $item,
        ResolutionCase $case,
        ?CarbonImmutable $today = null,
    ): array {
        $today ??= CarbonImmutable::today();
        $purchaseDate = $item->purchase_date
            ? CarbonImmutable::instance($item->purchase_date)->startOfDay()
            : null;
        $evaluatedAt = $case->failure_date
            ? CarbonImmutable::instance($case->failure_date)->startOfDay()
            : $today->startOfDay();
        $evaluationSource = $case->failure_date ? 'failure_date' : 'today';

        return [
            'return' => $this->position(
                kind: 'return',
                purchaseDate: $purchaseDate,
                duration: $item->return_days,
                durationLabel: 'return period',
                evaluatedAt: $evaluatedAt,
                evaluationSource: $evaluationSource,
                deadline: $purchaseDate?->addDays($item->return_days ?? 0),
            ),
            'warranty' => $this->position(
                kind: 'warranty',
                purchaseDate: $purchaseDate,
                duration: $item->warranty_months,
                durationLabel: 'warranty length',
                evaluatedAt: $evaluatedAt,
                evaluationSource: $evaluationSource,
                deadline: $purchaseDate?->addMonthsNoOverflow($item->warranty_months ?? 0),
            ),
        ];
    }

    private function position(
        string $kind,
        ?CarbonImmutable $purchaseDate,
        ?int $duration,
        string $durationLabel,
        CarbonImmutable $evaluatedAt,
        string $evaluationSource,
        ?CarbonImmutable $deadline,
    ): CoveragePosition {
        $missingFacts = [];

        if ($purchaseDate === null) {
            $missingFacts[] = 'the purchase date is missing';
        }

        if ($duration === null) {
            $missingFacts[] = "the recorded {$durationLabel} is missing";
        }

        if ($purchaseDate !== null && $evaluatedAt->isBefore($purchaseDate)) {
            $missingFacts[] = 'the problem date is before the purchase date';
        }

        $sourceFacts = [
            'purchase_date' => $purchaseDate?->format('Y-m-d'),
            'duration' => $duration,
            'duration_unit' => $kind === 'return' ? 'days' : 'months',
            'evaluated_at' => $evaluatedAt->format('Y-m-d'),
            'evaluation_source' => $evaluationSource,
        ];

        if ($missingFacts !== []) {
            return new CoveragePosition(
                kind: $kind,
                state: 'unknown',
                deadline: null,
                evaluatedAt: $evaluatedAt,
                evaluationSource: $evaluationSource,
                sourceFacts: $sourceFacts,
                missingFacts: $missingFacts,
                daysDifference: null,
            );
        }

        $daysDifference = (int) $evaluatedAt->diffInDays($deadline, false);

        return new CoveragePosition(
            kind: $kind,
            state: $daysDifference >= 0 ? 'active' : 'expired',
            deadline: $deadline,
            evaluatedAt: $evaluatedAt,
            evaluationSource: $evaluationSource,
            sourceFacts: $sourceFacts,
            missingFacts: [],
            daysDifference: $daysDifference,
        );
    }
}

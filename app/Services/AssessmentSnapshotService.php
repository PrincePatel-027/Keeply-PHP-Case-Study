<?php

namespace App\Services;

use App\Models\CaseAssessment;
use App\Models\ResolutionCase;
use App\ValueObjects\CoveragePosition;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AssessmentSnapshotService
{
    public function __construct(private readonly CoverageCalculator $coverageCalculator) {}

    public function create(ResolutionCase $case, int $userId): CaseAssessment
    {
        return DB::transaction(function () use ($case, $userId): CaseAssessment {
            $lockedCase = ResolutionCase::query()
                ->whereKey($case->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedCase->load('item');

            $assessedAt = CarbonImmutable::now();
            $coverage = $this->coverageCalculator->calculate(
                $lockedCase->item,
                $lockedCase,
                $assessedAt,
            );
            $version = ((int) $lockedCase->assessments()->max('version')) + 1;

            return $lockedCase->assessments()->create([
                'workspace_id' => $lockedCase->workspace_id,
                'created_by' => $userId,
                'version' => $version,
                'assessed_at' => $assessedAt,
                'snapshot' => [
                    'schema_version' => 1,
                    'item' => [
                        'id' => $lockedCase->item->id,
                        'name' => $lockedCase->item->name,
                        'purchase_date' => $lockedCase->item->purchase_date?->format('Y-m-d'),
                        'return_days' => $lockedCase->item->return_days,
                        'warranty_months' => $lockedCase->item->warranty_months,
                    ],
                    'case' => [
                        'id' => $lockedCase->id,
                        'failure_date' => $lockedCase->failure_date?->format('Y-m-d'),
                    ],
                    'coverage' => collect($coverage)
                        ->map(fn (CoveragePosition $position): array => $this->serialize($position))
                        ->all(),
                ],
            ]);
        });
    }

    private function serialize(CoveragePosition $position): array
    {
        return [
            'kind' => $position->kind,
            'state' => $position->state,
            'deadline' => $position->deadline?->format('Y-m-d'),
            'evaluated_at' => $position->evaluatedAt->format('Y-m-d'),
            'evaluation_source' => $position->evaluationSource,
            'source_facts' => $position->sourceFacts,
            'missing_facts' => $position->missingFacts,
            'days_difference' => $position->daysDifference,
            'explanation' => $position->explanation(),
        ];
    }
}

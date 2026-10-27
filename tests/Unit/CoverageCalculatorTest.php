<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Item;
use App\Models\ResolutionCase;
use App\Services\CoverageCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class CoverageCalculatorTest extends TestCase
{
    private CoverageCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CoverageCalculator;
    }

    public function test_recorded_deadline_day_is_still_active(): void
    {
        $coverage = $this->calculate(
            purchaseDate: '2026-01-01',
            returnDays: 30,
            warrantyMonths: 12,
            failureDate: '2026-01-31',
        );

        $this->assertSame('active', $coverage['return']->state);
        $this->assertSame(0, $coverage['return']->daysDifference);
        $this->assertSame('2026-01-31', $coverage['return']->deadline->format('Y-m-d'));
    }

    public function test_day_after_recorded_deadline_is_expired(): void
    {
        $coverage = $this->calculate(
            purchaseDate: '2026-01-01',
            returnDays: 30,
            warrantyMonths: 12,
            failureDate: '2026-02-01',
        );

        $this->assertSame('expired', $coverage['return']->state);
        $this->assertSame(-1, $coverage['return']->daysDifference);
    }

    public function test_warranty_months_do_not_overflow_at_month_end(): void
    {
        $coverage = $this->calculate(
            purchaseDate: '2026-01-31',
            returnDays: 14,
            warrantyMonths: 1,
            failureDate: '2026-02-28',
        );

        $this->assertSame('2026-02-28', $coverage['warranty']->deadline->format('Y-m-d'));
        $this->assertSame('active', $coverage['warranty']->state);
    }

    public function test_missing_duration_is_unknown_not_expired(): void
    {
        $coverage = $this->calculate(
            purchaseDate: '2026-01-01',
            returnDays: 30,
            warrantyMonths: null,
            failureDate: '2026-10-01',
        );

        $this->assertSame('unknown', $coverage['warranty']->state);
        $this->assertContains('the recorded warranty length is missing', $coverage['warranty']->missingFacts);
        $this->assertNull($coverage['warranty']->deadline);
    }

    public function test_problem_before_purchase_produces_unknown_instead_of_false_active_state(): void
    {
        $coverage = $this->calculate(
            purchaseDate: '2026-05-01',
            returnDays: 30,
            warrantyMonths: 12,
            failureDate: '2026-04-30',
        );

        $this->assertSame('unknown', $coverage['return']->state);
        $this->assertContains('the problem date is before the purchase date', $coverage['return']->missingFacts);
    }

    public function test_today_is_used_when_problem_date_is_unknown(): void
    {
        $coverage = $this->calculate(
            purchaseDate: '2026-01-01',
            returnDays: 30,
            warrantyMonths: 12,
            failureDate: null,
            today: '2026-01-15',
        );

        $this->assertSame('today', $coverage['return']->evaluationSource);
        $this->assertSame('active', $coverage['return']->state);
        $this->assertSame(16, $coverage['return']->daysDifference);
    }

    private function calculate(
        ?string $purchaseDate,
        ?int $returnDays,
        ?int $warrantyMonths,
        ?string $failureDate,
        string $today = '2026-10-10',
    ): array {
        $item = new Item([
            'purchase_date' => $purchaseDate,
            'return_days' => $returnDays,
            'warranty_months' => $warrantyMonths,
        ]);
        $case = new ResolutionCase([
            'failure_date' => $failureDate,
        ]);

        return $this->calculator->calculate($item, $case, CarbonImmutable::parse($today));
    }
}

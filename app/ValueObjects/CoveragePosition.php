<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class CoveragePosition
{
    public function __construct(
        public string $kind,
        public string $state,
        public ?CarbonImmutable $deadline,
        public CarbonImmutable $evaluatedAt,
        public string $evaluationSource,
        public array $sourceFacts,
        public array $missingFacts,
        public ?int $daysDifference,
    ) {}

    public function title(): string
    {
        return Str::headline($this->kind).' position';
    }

    public function stateLabel(): string
    {
        return Str::headline($this->state);
    }

    public function explanation(): string
    {
        if ($this->state === 'unknown') {
            return $this->title().' is unknown because '.implode(' and ', $this->missingFacts).'.';
        }

        $deadline = $this->deadline->format('j M Y');
        $reference = $this->evaluationSource === 'failure_date' ? 'reported problem date' : 'today';

        if ($this->daysDifference === 0) {
            return "The {$reference} falls on the recorded {$this->kind} deadline of {$deadline}.";
        }

        $days = abs($this->daysDifference);
        $unit = Str::plural('day', $days);

        if ($this->state === 'active') {
            return "The {$reference} is {$days} {$unit} before the recorded {$this->kind} deadline of {$deadline}.";
        }

        return "The {$reference} is {$days} {$unit} after the recorded {$this->kind} deadline of {$deadline}.";
    }
}

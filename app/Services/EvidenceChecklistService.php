<?php

namespace App\Services;

use App\Models\CaseAssessment;
use App\Models\ResolutionCase;

class EvidenceChecklistService
{
    public function build(ResolutionCase $case, ?CaseAssessment $assessment = null): array
    {
        $case->loadMissing(['item.documents', 'events', 'assessments']);
        $assessment ??= $case->assessments->first();

        if ($assessment === null) {
            return ['assessment' => null, 'items' => []];
        }

        $documentTypes = $case->item->documents
            ->map(fn ($document): string => $document->pivot->purpose)
            ->unique();
        $hasReceipt = $documentTypes->contains('receipt');
        $hasWarranty = $documentTypes->contains('warranty');
        $hasPhoto = $documentTypes->contains('product_photo');
        $hasIdentifier = filled($case->item->serial_number) || filled($case->item->model);
        $repairCount = $case->events->where('event_type', 'repair')->count();
        $communicationEvents = $case->events->whereIn('event_type', ['contact', 'response', 'escalation']);
        $references = $communicationEvents->pluck('reference')->filter();
        $claimOutcome = in_array($case->desired_outcome, ['repair', 'replacement', 'refund', 'return'], true);
        $identifierRequired = in_array($case->desired_outcome, ['repair', 'replacement', 'diagnosis'], true);
        $warrantyRoute = in_array($case->desired_outcome, ['repair', 'replacement'], true);
        $warrantyState = $assessment->snapshot['coverage']['warranty']['state'];

        return [
            'assessment' => $assessment,
            'items' => [
                $this->item(
                    'proof_of_purchase',
                    'Proof of purchase',
                    $hasReceipt ? 'present' : ($claimOutcome ? 'missing' : 'optional'),
                    $hasReceipt
                        ? 'A receipt or invoice is attached to this product.'
                        : ($claimOutcome
                            ? 'No receipt or invoice is attached for the requested outcome.'
                            : 'A receipt may help, but it is not required for the requested outcome.'),
                    $hasReceipt ? 'Attached document type: receipt' : 'Attached document types do not include receipt',
                ),
                $this->item(
                    'product_identifier',
                    'Product identifier',
                    $hasIdentifier ? 'present' : ($identifierRequired ? 'missing' : 'optional'),
                    $hasIdentifier
                        ? 'A model or serial number is recorded for this product.'
                        : ($identifierRequired
                            ? 'Neither a model nor serial number is recorded for the requested outcome.'
                            : 'A model or serial number may help identify the product.'),
                    $hasIdentifier ? 'Product record contains a model or serial number' : 'Product model and serial number are blank',
                ),
                $this->item(
                    'issue_photo',
                    'Product or issue photo',
                    $hasPhoto ? 'present' : 'optional',
                    $hasPhoto
                        ? 'A product photo is attached; confirm that it shows the reported problem before using it.'
                        : 'A clear photo may help document visible damage or symptoms.',
                    $hasPhoto ? 'Attached document type: product photo' : 'No product photo is attached',
                ),
                $this->item(
                    'warranty_document',
                    'Warranty document',
                    $this->warrantyState($hasWarranty, $warrantyRoute, $warrantyState),
                    $this->warrantyExplanation($hasWarranty, $warrantyRoute, $warrantyState),
                    "Assessment v{$assessment->version} warranty position: {$warrantyState}",
                ),
                $this->item(
                    'repair_history',
                    'Repair history',
                    $repairCount > 0 ? 'present' : 'optional',
                    $repairCount > 0
                        ? "{$repairCount} repair ".($repairCount === 1 ? 'entry is' : 'entries are').' recorded in the timeline.'
                        : 'No prior repair is recorded; add one only if a repair has actually occurred.',
                    "Case timeline repair entries: {$repairCount}",
                ),
                $this->item(
                    'communication_reference',
                    'Communication reference',
                    $references->isNotEmpty() ? 'present' : ($communicationEvents->isNotEmpty() ? 'missing' : 'optional'),
                    $references->isNotEmpty()
                        ? 'At least one recorded contact includes a reference number.'
                        : ($communicationEvents->isNotEmpty()
                            ? 'Contact is recorded, but none of those entries has a reference number.'
                            : 'No contact is recorded yet; add a reference after the business provides one.'),
                    'Communication entries: '.$communicationEvents->count().'; references recorded: '.$references->count(),
                ),
            ],
        ];
    }

    private function warrantyState(bool $hasWarranty, bool $warrantyRoute, string $warrantyState): string
    {
        if ($hasWarranty) {
            return 'present';
        }

        if (! $warrantyRoute || $warrantyState === 'expired') {
            return 'not_applicable';
        }

        return 'missing';
    }

    private function warrantyExplanation(bool $hasWarranty, bool $warrantyRoute, string $warrantyState): string
    {
        if ($hasWarranty) {
            return 'A warranty document is attached to this product.';
        }

        if (! $warrantyRoute) {
            return 'The requested outcome does not currently rely on a warranty route.';
        }

        if ($warrantyState === 'expired') {
            return 'The saved assessment records the warranty position as expired, so warranty terms are not requested here.';
        }

        return "No warranty document is attached while the saved warranty position is {$warrantyState}.";
    }

    private function item(string $key, string $label, string $state, string $explanation, string $source): array
    {
        return compact('key', 'label', 'state', 'explanation', 'source');
    }
}

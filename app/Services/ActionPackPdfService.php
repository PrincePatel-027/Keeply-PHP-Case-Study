<?php

namespace App\Services;

use App\Models\ActionPack;
use App\Models\ResolutionCase;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ActionPackPdfService
{
    public function create(array $pack): ActionPack
    {
        $disk = config('filesystems.default');
        $storedPath = null;

        try {
            return DB::transaction(function () use ($pack, $disk, &$storedPath): ActionPack {
                $case = ResolutionCase::query()
                    ->whereKey($pack['case']->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $version = ((int) $case->actionPacks()->max('version')) + 1;
                $generatedAt = now();
                $snapshot = $this->snapshot($pack, $version, $generatedAt->toIso8601String());
                $pdfBytes = $this->render($snapshot);
                $storedPath = "action-packs/{$case->workspace_id}/".Str::uuid().'.pdf';

                if (! Storage::disk($disk)->put($storedPath, $pdfBytes)) {
                    throw new RuntimeException('The Action Pack PDF could not be stored.');
                }

                $fileName = "keeply-action-pack-{$case->id}-v{$version}.pdf";
                $document = $case->workspace->documents()->create([
                    'uploaded_by' => $pack['owner']->id,
                    'type' => 'action_pack',
                    'original_name' => $fileName,
                    'storage_disk' => $disk,
                    'storage_path' => $storedPath,
                    'mime_type' => 'application/pdf',
                    'size_bytes' => strlen($pdfBytes),
                    'sha256' => hash('sha256', $pdfBytes),
                ]);

                return $case->actionPacks()->create([
                    'workspace_id' => $case->workspace_id,
                    'case_assessment_id' => $pack['assessment']->id,
                    'pdf_document_id' => $document->id,
                    'generated_by' => $pack['owner']->id,
                    'version' => $version,
                    'facts_snapshot' => $snapshot['facts'],
                    'events_snapshot' => $snapshot['events'],
                    'evidence_manifest' => $snapshot['evidence'],
                    'letter_body' => $snapshot['letter_body'],
                    'generated_at' => $generatedAt,
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $exception;
        }
    }

    private function snapshot(array $pack, int $version, string $generatedAt): array
    {
        return [
            'pack_version' => $version,
            'generated_at' => $generatedAt,
            'facts' => [
                'owner' => [
                    'name' => $pack['owner']->name,
                    'email' => $pack['owner']->email,
                ],
                'item' => [
                    'id' => $pack['item']->id,
                    'name' => $pack['item']->name,
                    'brand' => $pack['item']->brand,
                    'model' => $pack['item']->model,
                    'serial_number' => $pack['item']->serial_number,
                    'seller_name' => $pack['item']->seller_name,
                    'currency' => $pack['item']->currency,
                    'purchase_date' => $pack['assessment']->snapshot['item']['purchase_date'],
                ],
                'case' => [
                    'id' => $pack['case']->id,
                    'title' => $pack['case']->title,
                    'problem_summary' => $pack['case']->problem_summary,
                    'failure_date' => $pack['case']->failure_date?->format('Y-m-d'),
                    'desired_outcome' => $pack['case']->desired_outcome,
                    'status' => $pack['case']->status,
                ],
                'assessment' => [
                    'id' => $pack['assessment']->id,
                    'version' => $pack['assessment']->version,
                    'assessed_at' => $pack['assessment']->assessed_at->toIso8601String(),
                    'snapshot' => $pack['assessment']->snapshot,
                ],
                'checklist' => $pack['checklist']['items'],
            ],
            'events' => $pack['events']->map(fn ($event): array => [
                'id' => $event->id,
                'type' => $event->event_type,
                'occurred_at' => $event->occurred_at->toIso8601String(),
                'summary' => $event->summary,
                'channel' => $event->channel,
                'party_name' => $event->party_name,
                'reference' => $event->reference,
                'expense_minor' => $event->expense_minor,
            ])->values()->all(),
            'evidence' => $pack['documents']->map(fn ($document): array => [
                'document_id' => $document->id,
                'purpose' => $document->pivot->purpose,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'sha256' => $document->sha256,
            ])->values()->all(),
            'letter_body' => $pack['letter_body'],
        ];
    }

    private function render(array $snapshot): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('action-packs.pdf', compact('snapshot'))->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}

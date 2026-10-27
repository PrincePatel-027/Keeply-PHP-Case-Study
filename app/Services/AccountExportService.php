<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Item;
use App\Models\ResolutionCase;
use App\Models\User;
use App\Models\Workspace;

class AccountExportService
{
    public function build(User $user): array
    {
        $workspaces = $user->workspaces()->orderBy('workspaces.id')->get();

        return [
            'export_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'workspaces' => $workspaces
                ->map(fn (Workspace $workspace): array => $this->workspace($workspace))
                ->values()
                ->all(),
            'retention' => [
                'account_deletion_recovery_days' => AccountDeletionService::RETENTION_DAYS,
                'binary_files_included' => false,
                'binary_file_note' => 'This JSON contains document metadata and integrity hashes. Download private files separately before requesting deletion.',
            ],
        ];
    }

    private function workspace(Workspace $workspace): array
    {
        $items = Item::withTrashed()
            ->where('workspace_id', $workspace->id)
            ->with(['category', 'values.categoryField'])
            ->orderBy('id')
            ->get();
        $documents = Document::withTrashed()
            ->where('workspace_id', $workspace->id)
            ->orderBy('id')
            ->get();
        $cases = ResolutionCase::query()
            ->where('workspace_id', $workspace->id)
            ->with(['events', 'reminders', 'assessments', 'actionPacks'])
            ->orderBy('id')
            ->get();

        return [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'currency' => $workspace->currency,
                'country_code' => $workspace->country_code,
                'created_at' => $workspace->created_at?->toIso8601String(),
            ],
            'products' => $items->map(fn (Item $item): array => [
                ...$item->only([
                    'id', 'name', 'brand', 'model', 'serial_number', 'purchase_date',
                    'purchase_price_minor', 'currency', 'seller_name', 'return_days',
                    'warranty_months', 'status', 'created_at', 'updated_at', 'deleted_at',
                ]),
                'category' => $item->category?->only(['key', 'name']),
                'dynamic_values' => $item->values->map(fn ($value): array => [
                    'field_key' => $value->categoryField->key,
                    'field_label' => $value->categoryField->label,
                    ...$value->only([
                        'value_text', 'value_integer', 'value_decimal', 'value_boolean', 'value_date',
                    ]),
                ])->values()->all(),
            ])->values()->all(),
            'documents' => $documents->map(fn (Document $document): array => $document->only([
                'id', 'type', 'original_name', 'mime_type', 'size_bytes', 'sha256',
                'created_at', 'updated_at', 'deleted_at',
            ]))->values()->all(),
            'cases' => $cases->map(fn (ResolutionCase $case): array => [
                ...$case->only([
                    'id', 'item_id', 'title', 'problem_summary', 'failure_date',
                    'desired_outcome', 'status', 'priority', 'opened_at', 'resolved_at',
                    'resolution_type', 'resolution_value_minor', 'created_at', 'updated_at',
                ]),
                'events' => $case->events->toArray(),
                'reminders' => $case->reminders->toArray(),
                'assessments' => $case->assessments->toArray(),
                'action_packs' => $case->actionPacks->toArray(),
            ])->values()->all(),
        ];
    }
}

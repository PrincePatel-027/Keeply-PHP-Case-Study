<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ActionPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'case_assessment_id',
        'pdf_document_id',
        'generated_by',
        'version',
        'facts_snapshot',
        'events_snapshot',
        'evidence_manifest',
        'letter_body',
        'generated_at',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Generated Action Packs cannot be changed.');
        });

        static::deleting(function (): never {
            throw new LogicException('Generated Action Packs cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'facts_snapshot' => 'array',
            'events_snapshot' => 'array',
            'evidence_manifest' => 'array',
            'generated_at' => 'immutable_datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function resolutionCase(): BelongsTo
    {
        return $this->belongsTo(ResolutionCase::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(CaseAssessment::class, 'case_assessment_id');
    }

    public function pdfDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'pdf_document_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

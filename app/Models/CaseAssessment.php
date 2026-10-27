<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CaseAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'version',
        'assessed_at',
        'snapshot',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Assessment snapshots cannot be changed.');
        });

        static::deleting(function (): never {
            throw new LogicException('Assessment snapshots cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'assessed_at' => 'immutable_datetime',
            'snapshot' => 'array',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

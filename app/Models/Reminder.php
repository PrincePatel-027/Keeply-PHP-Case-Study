<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'resolution_case_id',
        'case_event_id',
        'user_id',
        'type',
        'due_at',
        'queued_at',
        'sent_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->whereNull('cancelled_at');
    }

    public function isOverdue(): bool
    {
        return $this->due_at->isPast() && ! $this->completed_at && ! $this->cancelled_at;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function resolutionCase(): BelongsTo
    {
        return $this->belongsTo(ResolutionCase::class);
    }

    public function caseEvent(): BelongsTo
    {
        return $this->belongsTo(CaseEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

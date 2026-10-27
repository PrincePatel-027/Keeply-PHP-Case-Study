<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ResolutionCase extends Model
{
    use HasFactory;

    public const RESOLUTION_TYPES = [
        'repaired',
        'replaced',
        'refunded',
        'rejected',
        'abandoned',
        'other',
    ];

    public const STATUSES = [
        'draft',
        'active',
        'waiting_on_business',
        'action_required',
        'resolved',
        'closed_unresolved',
    ];

    protected $fillable = [
        'item_id',
        'opened_by',
        'title',
        'problem_summary',
        'failure_date',
        'desired_outcome',
        'status',
        'priority',
        'opened_at',
        'resolved_at',
        'resolution_type',
        'resolution_value_minor',
    ];

    protected function casts(): array
    {
        return [
            'failure_date' => 'date',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
            'resolution_value_minor' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CaseAssessment::class)
            ->orderByDesc('version');
    }

    public function actionPacks(): HasMany
    {
        return $this->hasMany(ActionPack::class)
            ->orderByDesc('version');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function statusLabel(): string
    {
        return Str::headline($this->status);
    }
}

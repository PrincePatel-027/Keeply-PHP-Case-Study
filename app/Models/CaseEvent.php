<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CaseEvent extends Model
{
    use HasFactory;

    public const TYPES = [
        'issue_observed',
        'contact',
        'response',
        'visit',
        'shipment',
        'repair',
        'expense',
        'promise',
        'escalation',
        'note',
        'outcome',
    ];

    public const CHANNELS = [
        'phone',
        'email',
        'chat',
        'in_person',
        'web',
        'post',
        'other',
    ];

    protected $fillable = [
        'created_by',
        'event_type',
        'occurred_at',
        'channel',
        'party_name',
        'summary',
        'reference',
        'promised_follow_up_at',
        'expense_minor',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'promised_follow_up_at' => 'datetime',
            'expense_minor' => 'integer',
        ];
    }

    public function resolutionCase(): BelongsTo
    {
        return $this->belongsTo(ResolutionCase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reminder(): HasOne
    {
        return $this->hasOne(Reminder::class);
    }

    public function typeLabel(): string
    {
        return Str::headline($this->event_type);
    }
}

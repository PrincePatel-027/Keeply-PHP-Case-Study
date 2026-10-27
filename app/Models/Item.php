<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price_minor',
        'currency',
        'seller_name',
        'return_days',
        'warranty_months',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'serial_number' => 'encrypted',
            'purchase_date' => 'date',
            'purchase_price_minor' => 'integer',
            'return_days' => 'integer',
            'warranty_months' => 'integer',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ItemValue::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'item_documents')
            ->withPivot('purpose')
            ->withTimestamps()
            ->orderByDesc('documents.created_at');
    }

    public function resolutionCases(): HasMany
    {
        return $this->hasMany(ResolutionCase::class)->latest('opened_at');
    }
}

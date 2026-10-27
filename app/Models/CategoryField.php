<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryField extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'data_type',
        'is_required',
        'validation_rules',
        'options',
        'help_text',
        'sort_order',
        'is_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'validation_rules' => 'array',
            'options' => 'array',
            'sort_order' => 'integer',
            'is_sensitive' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

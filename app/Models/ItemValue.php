<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_field_id',
        'value_text',
        'value_integer',
        'value_decimal',
        'value_boolean',
        'value_date',
    ];

    protected function casts(): array
    {
        return [
            'value_integer' => 'integer',
            'value_decimal' => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function categoryField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class);
    }

    public function displayValue(): string
    {
        $field = $this->categoryField;

        return match ($field->data_type) {
            'boolean' => $this->value_boolean ? 'Yes' : 'No',
            'date' => $this->value_date?->format('j M Y') ?? '—',
            'integer' => (string) $this->value_integer,
            'decimal' => rtrim(rtrim((string) $this->value_decimal, '0'), '.'),
            'select' => $field->options[$this->value_text] ?? (string) $this->value_text,
            default => (string) $this->value_text,
        };
    }
}

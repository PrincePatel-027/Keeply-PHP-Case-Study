<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Item;
use Illuminate\Validation\Rule;
use LogicException;

class DynamicFieldService
{
    public function validationRules(Category $category): array
    {
        return $category->fields->mapWithKeys(function (CategoryField $field): array {
            $rules = [$field->is_required ? 'required' : 'nullable'];
            $constraints = $field->validation_rules ?? [];

            $rules = array_merge($rules, match ($field->data_type) {
                'text' => ['string'],
                'select' => ['string', Rule::in(array_keys($field->options ?? []))],
                'integer' => ['integer'],
                'decimal' => ['numeric'],
                'boolean' => ['boolean'],
                'date' => ['date_format:Y-m-d'],
                default => throw new LogicException("Unsupported category field type: {$field->data_type}"),
            });

            if (isset($constraints['max_length'])) {
                $rules[] = 'max:'.$constraints['max_length'];
            }

            if (isset($constraints['minimum'])) {
                $rules[] = 'min:'.$constraints['minimum'];
            }

            if (isset($constraints['maximum'])) {
                $rules[] = 'max:'.$constraints['maximum'];
            }

            if ($field->data_type === 'decimal' && isset($constraints['decimal_places'])) {
                $rules[] = 'decimal:0,'.$constraints['decimal_places'];
            }

            return ['dynamic.'.$field->key => $rules];
        })->all();
    }

    public function persist(Item $item, Category $category, array $values): void
    {
        foreach ($category->fields as $field) {
            $rawValue = $values[$field->key] ?? null;

            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $column = match ($field->data_type) {
                'integer' => 'value_integer',
                'decimal' => 'value_decimal',
                'boolean' => 'value_boolean',
                'date' => 'value_date',
                'text', 'select' => 'value_text',
                default => throw new LogicException("Unsupported category field type: {$field->data_type}"),
            };

            $value = match ($field->data_type) {
                'integer' => (int) $rawValue,
                'decimal' => (float) $rawValue,
                'boolean' => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN),
                default => $rawValue,
            };

            $item->values()->create([
                'category_field_id' => $field->id,
                $column => $value,
            ]);
        }
    }
}

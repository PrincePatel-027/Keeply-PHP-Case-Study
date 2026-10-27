<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\DynamicFieldService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(DynamicFieldService $dynamicFields): array
    {
        $category = Category::query()
            ->whereKey($this->input('category_id'))
            ->where('is_active', true)
            ->with('fields')
            ->first();

        $rules = [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'decimal:0,2'],
            'seller_name' => ['nullable', 'string', 'max:160'],
            'return_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'dynamic' => ['nullable', 'array'],
        ];

        return $category
            ? array_merge($rules, $dynamicFields->validationRules($category))
            : $rules;
    }

    public function attributes(): array
    {
        $category = Category::query()
            ->whereKey($this->input('category_id'))
            ->with('fields')
            ->first();

        return $category?->fields
            ->mapWithKeys(fn ($field): array => ['dynamic.'.$field->key => $field->label])
            ->all() ?? [];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResolutionCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && $this->user()?->can('view', $item);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'problem_summary' => ['required', 'string', 'max:5000'],
            'failure_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'desired_outcome' => [
                'required',
                Rule::in(['repair', 'replacement', 'refund', 'return', 'diagnosis', 'other']),
            ],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\ResolutionCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordResolutionOutcomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('resolutionCase');

        return $case instanceof ResolutionCase && $this->user()?->can('view', $case);
    }

    public function rules(): array
    {
        return [
            'outcome_type' => ['required', Rule::in(ResolutionCase::RESOLUTION_TYPES)],
            'occurred_at' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'summary' => ['required', 'string', 'max:5000'],
            'resolution_value' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                'decimal:0,2',
            ],
        ];
    }
}

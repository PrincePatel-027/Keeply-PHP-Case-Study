<?php

namespace App\Http\Requests;

use App\Models\ResolutionCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('resolutionCase');

        return $case instanceof ResolutionCase && $this->user()?->can('view', $case);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ResolutionCase::STATUSES)],
        ];
    }
}

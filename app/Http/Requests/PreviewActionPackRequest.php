<?php

namespace App\Http\Requests;

use App\Models\ResolutionCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PreviewActionPackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('resolutionCase');

        return $case instanceof ResolutionCase && $this->user()?->can('view', $case);
    }

    public function rules(): array
    {
        $case = $this->route('resolutionCase');

        return [
            'assessment_id' => [
                'required',
                'integer',
                Rule::exists('case_assessments', 'id')
                    ->where('resolution_case_id', $case->id),
            ],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['integer', 'distinct'],
            'letter_body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $case = $this->route('resolutionCase');
                $documentIds = collect($this->input('document_ids', []))
                    ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
                    ->map(fn (mixed $id): int => (int) $id)
                    ->unique();

                if ($documentIds->isEmpty()) {
                    return;
                }

                $allowedCount = $case->item->documents()
                    ->whereIn('documents.id', $documentIds)
                    ->count();

                if ($allowedCount !== $documentIds->count()) {
                    $validator->errors()->add(
                        'document_ids',
                        'Every selected document must belong to this product.',
                    );
                }
            },
        ];
    }
}

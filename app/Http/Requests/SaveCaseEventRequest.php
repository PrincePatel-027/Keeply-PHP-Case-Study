<?php

namespace App\Http\Requests;

use App\Models\CaseEvent;
use App\Models\ResolutionCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCaseEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('resolutionCase');
        $event = $this->route('caseEvent');

        if (! $case instanceof ResolutionCase || ! $this->user()?->can('view', $case)) {
            return false;
        }

        return ! $event instanceof CaseEvent || $event->resolution_case_id === $case->id;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in(CaseEvent::TYPES)],
            'occurred_at' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:now'],
            'channel' => ['nullable', Rule::in(CaseEvent::CHANNELS)],
            'party_name' => ['nullable', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:5000'],
            'reference' => ['nullable', 'string', 'max:255'],
            'promised_follow_up_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:occurred_at'],
            'expense' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'decimal:0,2'],
        ];
    }
}

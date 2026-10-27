<div class="event-form-grid">
    <div class="field">
        <label for="event_type">Entry type</label>
        <select id="event_type" name="event_type" required @error('event_type') aria-invalid="true" aria-describedby="event-type-error" @enderror>
            @foreach (['issue_observed' => 'Issue observed', 'contact' => 'Contact made', 'response' => 'Response received', 'visit' => 'Visit', 'shipment' => 'Shipment', 'repair' => 'Repair', 'expense' => 'Expense', 'promise' => 'Promise or commitment', 'escalation' => 'Escalation', 'note' => 'Note'] as $value => $label)
                <option value="{{ $value }}" @selected(old('event_type', $event?->event_type ?? 'contact') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('event_type') <p id="event-type-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="occurred_at">When it happened</label>
        <input id="occurred_at" name="occurred_at" type="datetime-local" value="{{ old('occurred_at', $event?->occurred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required @error('occurred_at') aria-invalid="true" aria-describedby="occurred-at-error" @enderror>
        @error('occurred_at') <p id="occurred-at-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field field-wide">
        <label for="summary">What happened?</label>
        <textarea id="summary" name="summary" rows="4" required @error('summary') aria-invalid="true" aria-describedby="summary-error" @enderror>{{ old('summary', $event?->summary) }}</textarea>
        @error('summary') <p id="summary-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="channel">Channel</label>
        <select id="channel" name="channel" @error('channel') aria-invalid="true" aria-describedby="channel-error" @enderror>
            <option value="">Not applicable</option>
            @foreach (['phone' => 'Phone', 'email' => 'Email', 'chat' => 'Chat', 'in_person' => 'In person', 'web' => 'Website or portal', 'post' => 'Post', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" @selected(old('channel', $event?->channel) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('channel') <p id="channel-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="party_name">Person or business</label>
        <input id="party_name" name="party_name" type="text" value="{{ old('party_name', $event?->party_name) }}" @error('party_name') aria-invalid="true" aria-describedby="party-name-error" @enderror>
        @error('party_name') <p id="party-name-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="reference">Reference number</label>
        <input id="reference" name="reference" type="text" value="{{ old('reference', $event?->reference) }}" @error('reference') aria-invalid="true" aria-describedby="reference-error" @enderror>
        @error('reference') <p id="reference-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field">
        <label for="expense">Expense ({{ $resolutionCase->item->currency }})</label>
        <input id="expense" name="expense" type="number" min="0" step="0.01" value="{{ old('expense', $event?->expense_minor !== null ? number_format($event->expense_minor / 100, 2, '.', '') : '') }}" @error('expense') aria-invalid="true" aria-describedby="expense-error" @enderror>
        @error('expense') <p id="expense-error" class="field-error">{{ $message }}</p> @enderror
    </div>

    <div class="field field-wide">
        <label for="promised_follow_up_at">Promised follow-up</label>
        <input id="promised_follow_up_at" name="promised_follow_up_at" type="datetime-local" value="{{ old('promised_follow_up_at', $event?->promised_follow_up_at?->format('Y-m-d\TH:i')) }}" @error('promised_follow_up_at') aria-invalid="true" aria-describedby="promised-follow-up-error" @enderror>
        <p class="field-hint">Use this only when someone committed to a future response or action.</p>
        @error('promised_follow_up_at') <p id="promised-follow-up-error" class="field-error">{{ $message }}</p> @enderror
    </div>
</div>

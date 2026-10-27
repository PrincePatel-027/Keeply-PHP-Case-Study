<x-layout title="Open a case">
    <div class="record-toolbar">
        <a href="{{ route('items.show', $item) }}">← {{ $item->name }}</a>
        <span>New resolution file</span>
    </div>

    <section class="case-form-shell" data-tab="Open case">
        <header class="case-form-heading">
            <p class="section-code">{{ $item->category->name }} / {{ $item->name }}</p>
            <h1>What went wrong?</h1>
            <p>Record the facts as they are now. Contacts, promises, and evidence will be added to the timeline next.</p>
        </header>

        <form method="POST" action="{{ route('items.cases.store', $item) }}" class="product-form">
            @csrf

            <div class="field">
                <label for="title">Short case title</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Battery no longer holds charge" required autofocus @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
                @error('title') <p id="title-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="problem_summary">What happened?</label>
                <textarea id="problem_summary" name="problem_summary" rows="6" required @error('problem_summary') aria-invalid="true" aria-describedby="problem-summary-error" @enderror>{{ old('problem_summary') }}</textarea>
                <p class="field-hint">Include symptoms and what you have already tried. Do not guess the cause.</p>
                @error('problem_summary') <p id="problem-summary-error" class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field-grid">
                <div class="field">
                    <label for="failure_date">When did you notice it?</label>
                    <input id="failure_date" name="failure_date" type="date" value="{{ old('failure_date') }}" max="{{ now()->format('Y-m-d') }}" @error('failure_date') aria-invalid="true" aria-describedby="failure-date-error" @enderror>
                    @error('failure_date') <p id="failure-date-error" class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="desired_outcome">What would resolve it?</label>
                    <select id="desired_outcome" name="desired_outcome" required @error('desired_outcome') aria-invalid="true" aria-describedby="desired-outcome-error" @enderror>
                        <option value="">Choose an outcome</option>
                        @foreach (['repair' => 'Repair', 'replacement' => 'Replacement', 'refund' => 'Refund', 'return' => 'Return', 'diagnosis' => 'Diagnosis or advice', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('desired_outcome') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('desired_outcome') <p id="desired-outcome-error" class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <fieldset class="priority-field">
                <legend>Priority</legend>
                @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $value => $label)
                    <label>
                        <input type="radio" name="priority" value="{{ $value }}" @checked(old('priority', 'normal') === $value)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
                @error('priority') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            <div class="form-actions">
                <a href="{{ route('items.show', $item) }}">Cancel</a>
                <button class="button" type="submit">Open resolution case</button>
            </div>
        </form>
    </section>
</x-layout>

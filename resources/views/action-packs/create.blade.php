<x-layout title="Build Action Pack">
    <div class="record-toolbar">
        <a href="{{ route('cases.show', $resolutionCase) }}">← Back to case</a>
        <span>Preview builder · Nothing saved yet</span>
    </div>

    <section class="pack-builder-file" data-tab="Action pack">
        <header class="record-header">
            <div>
                <p class="section-code">Resolution file #{{ str_pad((string) $resolutionCase->id, 4, '0', STR_PAD_LEFT) }}</p>
                <h1>Build a factual Action Pack</h1>
                <p>Choose the fixed assessment and private evidence to include, then edit the request letter before previewing it.</p>
            </div>
            <span class="status-stamp">Draft</span>
        </header>

        @if ($errors->any())
            <div class="error-notice" role="alert">
                <strong>Please check the draft.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('cases.action-packs.preview', $resolutionCase) }}" class="pack-builder-form">
            @csrf

            <section class="pack-builder-section">
                <div>
                    <span class="section-code">01 / Fixed position</span>
                    <h2>Assessment version</h2>
                    <p>The preview uses the saved facts and calculations from this version.</p>
                </div>
                <div class="field">
                    <label for="assessment_id">Saved assessment</label>
                    <select id="assessment_id" name="assessment_id" required>
                        @foreach ($assessments as $assessment)
                            <option value="{{ $assessment->id }}" @selected((int) old('assessment_id', $assessments->first()->id) === $assessment->id)>
                                Version {{ $assessment->version }} · {{ $assessment->assessed_at->format('j M Y, g:i A') }} · Warranty {{ Str::headline($assessment->snapshot['coverage']['warranty']['state']) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section class="pack-builder-section">
                <div>
                    <span class="section-code">02 / Private evidence</span>
                    <h2>Documents to include</h2>
                    <p>Only checked files appear in this preview. The files remain private and are not copied yet.</p>
                </div>
                @if ($documents->isEmpty())
                    <p class="assessment-empty">No documents are attached to this product. You can still preview the factual summary and letter.</p>
                @else
                    <div class="pack-document-options">
                        @foreach ($documents as $document)
                            <label>
                                <input
                                    type="checkbox"
                                    name="document_ids[]"
                                    value="{{ $document->id }}"
                                    @checked(in_array($document->id, old('document_ids', $documents->pluck('id')->all())))
                                >
                                <span>
                                    <strong>{{ $document->original_name }}</strong>
                                    <small>{{ Str::headline($document->pivot->purpose) }} · {{ number_format($document->size_bytes / 1024, 1) }} KB</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="pack-builder-section pack-letter-editor">
                <div>
                    <span class="section-code">03 / Your words</span>
                    <h2>Factual request letter</h2>
                    <p>Edit names, context, or the request. Keeply does not send this letter or promise an outcome.</p>
                </div>
                <div class="field">
                    <label for="letter_body">Letter body</label>
                    <textarea id="letter_body" name="letter_body" rows="18" maxlength="10000" required>{{ old('letter_body', $defaultLetter) }}</textarea>
                    <p class="field-hint">Plain text only. Maximum 10,000 characters.</p>
                </div>
            </section>

            <div class="form-actions pack-builder-actions">
                <a href="{{ route('cases.show', $resolutionCase) }}">Cancel</a>
                <button class="button" type="submit">Preview Action Pack</button>
            </div>
        </form>
    </section>
</x-layout>

<x-layout title="{{ $resolutionCase->title }}">
    <div class="record-toolbar">
        <a href="{{ route('cases.index') }}">← All cases</a>
        <span>Resolution file #{{ str_pad((string) $resolutionCase->id, 4, '0', STR_PAD_LEFT) }}</span>
    </div>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    @error('status')
        <p class="error-notice" role="alert">{{ $message }}</p>
    @enderror

    <section class="case-file" data-tab="{{ $resolutionCase->statusLabel() }}">
        <header class="case-header">
            <div>
                <p class="section-code">{{ $resolutionCase->item->name }} / {{ Str::headline($resolutionCase->desired_outcome) }} requested</p>
                <h1>{{ $resolutionCase->title }}</h1>
                <p>{{ $resolutionCase->problem_summary }}</p>
            </div>
            <span class="case-priority case-priority-{{ $resolutionCase->priority }}">{{ $resolutionCase->priority }} priority</span>
        </header>

        <section class="case-position">
            <div class="section-title-row">
                <div>
                    <h2>Current position</h2>
                    <p>Use a status that describes who needs to act next. Invalid jumps are rejected.</p>
                </div>
                <strong>{{ $resolutionCase->statusLabel() }}</strong>
            </div>

            @if ($allowedTransitions)
                <div class="status-actions">
                    @foreach ($allowedTransitions as $nextStatus)
                        <form method="POST" action="{{ route('cases.status.update', $resolutionCase) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus }}">
                            <button type="submit">{{ match ($nextStatus) {
                                'active' => 'Mark active',
                                'waiting_on_business' => 'Waiting on business',
                                'action_required' => 'My action is required',
                                'closed_unresolved' => 'Close unresolved',
                                default => Str::headline($nextStatus),
                            } }}</button>
                        </form>
                    @endforeach
                </div>
            @else
                <p class="terminal-state">This case is closed. Its recorded state cannot be changed.</p>
            @endif
        </section>

        <section class="case-outcome" aria-labelledby="case-outcome-title">
            <div class="section-title-row">
                <div>
                    <h2 id="case-outcome-title">Final outcome</h2>
                    <p>Record what actually happened. This closes the case, preserves its history, and cancels unfinished follow-up reminders.</p>
                </div>
                @if ($resolutionCase->resolution_type)
                    <strong>Outcome recorded</strong>
                @endif
            </div>

            @if ($resolutionCase->resolution_type)
                @php($outcomeEvent = $resolutionCase->events->firstWhere('event_type', 'outcome'))
                <div class="outcome-record">
                    <div>
                        <span>Result</span>
                        <strong>{{ Str::headline($resolutionCase->resolution_type) }}</strong>
                    </div>
                    <div>
                        <span>Resolved</span>
                        <strong>{{ $resolutionCase->resolved_at->format('j M Y, g:i A') }}</strong>
                    </div>
                    <div>
                        <span>Value recovered</span>
                        <strong>{{ $resolutionCase->resolution_value_minor !== null ? $resolutionCase->item->currency.' '.number_format($resolutionCase->resolution_value_minor / 100, 2) : 'Not recorded' }}</strong>
                    </div>
                    <p>{{ $outcomeEvent?->summary }}</p>
                </div>
            @else
                <details class="outcome-composer" @if ($errors->hasAny(['outcome_type', 'occurred_at', 'summary', 'resolution_value'])) open @endif>
                    <summary>Record final outcome</summary>
                    <form method="POST" action="{{ route('cases.outcome.store', $resolutionCase) }}" class="timeline-form">
                        @csrf
                        <div class="field-grid field-grid-two">
                            <div class="field">
                                <label for="outcome_type">Outcome</label>
                                <select id="outcome_type" name="outcome_type" required @error('outcome_type') aria-invalid="true" aria-describedby="outcome-type-error" @enderror>
                                    <option value="">Choose what happened</option>
                                    @if ($resolutionCase->status !== 'closed_unresolved')
                                        <option value="repaired" @selected(old('outcome_type') === 'repaired')>Repaired</option>
                                        <option value="replaced" @selected(old('outcome_type') === 'replaced')>Replaced</option>
                                        <option value="refunded" @selected(old('outcome_type') === 'refunded')>Refunded</option>
                                        <option value="other" @selected(old('outcome_type') === 'other')>Other resolved outcome</option>
                                    @endif
                                    <option value="rejected" @selected(old('outcome_type') === 'rejected')>Request rejected</option>
                                    <option value="abandoned" @selected(old('outcome_type') === 'abandoned')>Abandoned</option>
                                </select>
                                @error('outcome_type')<p id="outcome-type-error" class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="outcome_occurred_at">Outcome date and time</label>
                                <input id="outcome_occurred_at" name="occurred_at" type="datetime-local" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required @error('occurred_at') aria-invalid="true" aria-describedby="outcome-occurred-at-error" @enderror>
                                @error('occurred_at')<p id="outcome-occurred-at-error" class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="field">
                            <label for="resolution_value">Value recovered ({{ $resolutionCase->item->currency }}, optional)</label>
                            <input id="resolution_value" name="resolution_value" type="number" min="0" max="999999999.99" step="0.01" value="{{ old('resolution_value') }}" @error('resolution_value') aria-invalid="true" aria-describedby="resolution-value-error" @enderror>
                            @error('resolution_value')<p id="resolution-value-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="outcome_summary">What happened?</label>
                            <textarea id="outcome_summary" name="summary" rows="4" maxlength="5000" required aria-describedby="outcome-summary-hint @error('summary') outcome-summary-error @enderror" @error('summary') aria-invalid="true" @enderror>{{ old('summary') }}</textarea>
                            <p id="outcome-summary-hint" class="field-hint">Record facts such as who acted, what was provided, and any final reference.</p>
                            @error('summary')<p id="outcome-summary-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <button class="button" type="submit">Record and close case</button>
                    </form>
                </details>
            @endif
        </section>

        <section class="coverage-section" aria-labelledby="coverage-title">
            <div class="section-title-row">
                <div>
                    <h2 id="coverage-title">Recorded coverage position</h2>
                    <p>Calculated only from the dates and periods stored in Keeply. This does not interpret policy terms or guarantee eligibility.</p>
                </div>
                <form method="POST" action="{{ route('cases.assessments.store', $resolutionCase) }}">
                    @csrf
                    <button class="button" type="submit">Save assessment snapshot</button>
                </form>
            </div>

            <div class="coverage-grid">
                @foreach ($coverage as $position)
                    <article class="coverage-card coverage-{{ $position->state }}">
                        <header>
                            <span>{{ $position->title() }}</span>
                            <strong>{{ $position->stateLabel() }}</strong>
                        </header>
                        <p>{{ $position->explanation() }}</p>
                        <dl>
                            <div>
                                <dt>Purchase date</dt>
                                <dd>{{ $position->sourceFacts['purchase_date'] ? Carbon\CarbonImmutable::parse($position->sourceFacts['purchase_date'])->format('j M Y') : 'Missing' }}</dd>
                            </div>
                            <div>
                                <dt>Recorded period</dt>
                                <dd>{{ $position->sourceFacts['duration'] !== null ? $position->sourceFacts['duration'].' '.$position->sourceFacts['duration_unit'] : 'Missing' }}</dd>
                            </div>
                            <div>
                                <dt>Evaluated date</dt>
                                <dd>{{ $position->evaluatedAt->format('j M Y') }} · {{ $position->evaluationSource === 'failure_date' ? 'Problem date' : 'Today' }}</dd>
                            </div>
                            <div>
                                <dt>Recorded deadline</dt>
                                <dd>{{ $position->deadline?->format('j M Y') ?? 'Cannot calculate' }}</dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="case-columns">
            <div class="case-panel">
                <span>Problem facts</span>
                <dl>
                    <div><dt>Product</dt><dd><a href="{{ route('items.show', $resolutionCase->item) }}">{{ $resolutionCase->item->name }}</a></dd></div>
                    <div><dt>Noticed</dt><dd>{{ $resolutionCase->failure_date?->format('j M Y') ?? 'Date unknown' }}</dd></div>
                    <div><dt>Wanted outcome</dt><dd>{{ Str::headline($resolutionCase->desired_outcome) }}</dd></div>
                    <div><dt>Opened</dt><dd>{{ $resolutionCase->opened_at->format('j M Y') }}</dd></div>
                </dl>
            </div>

            <div class="case-panel">
                <span>Evidence on product</span>
                @if ($resolutionCase->item->documents->isEmpty())
                    <p>No documents are attached to this product yet.</p>
                    <a href="{{ route('items.show', $resolutionCase->item) }}">Attach evidence</a>
                @else
                    <ul>
                        @foreach ($resolutionCase->item->documents as $document)
                            <li><a href="{{ route('documents.download', $document) }}">{{ $document->original_name }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="case-timeline">
            <div class="section-title-row">
                <div>
                    <h2>Case timeline</h2>
                    <p>Add contacts, responses, repairs, expenses, promises, and factual notes. Entries are ordered by when they happened.</p>
                </div>
                <span>{{ str_pad((string) $resolutionCase->events->count(), 2, '0', STR_PAD_LEFT) }} entries</span>
            </div>

            <details class="timeline-composer" @if ($errors->hasAny(['event_type', 'occurred_at', 'summary', 'channel', 'party_name', 'reference', 'promised_follow_up_at', 'expense'])) open @endif>
                <summary>Add timeline entry</summary>
                <form method="POST" action="{{ route('cases.events.store', $resolutionCase) }}" class="timeline-form">
                    @csrf
                    @include('cases.events.fields', ['event' => null])
                    <button class="button" type="submit">Add to timeline</button>
                </form>
            </details>

            @if ($resolutionCase->events->isEmpty())
                <p class="timeline-empty">No events recorded yet. Add the first factual entry above.</p>
            @else
                <ol class="timeline-list">
                    @foreach ($resolutionCase->events as $event)
                        <li>
                            <div class="timeline-marker" aria-hidden="true"></div>
                            <article>
                                <header>
                                    <div>
                                        <span>{{ $event->typeLabel() }}</span>
                                        <time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('j M Y, g:i A') }}</time>
                                    </div>
                                    <a href="{{ route('cases.events.edit', [$resolutionCase, $event]) }}">Edit</a>
                                </header>
                                <p>{{ $event->summary }}</p>
                                @if ($event->channel || $event->party_name || $event->reference)
                                    <div class="event-metadata">
                                        @if ($event->channel)<span>{{ Str::headline($event->channel) }}</span>@endif
                                        @if ($event->party_name)<span>{{ $event->party_name }}</span>@endif
                                        @if ($event->reference)<span>Ref: {{ $event->reference }}</span>@endif
                                    </div>
                                @endif
                                @if ($event->expense_minor !== null || $event->promised_follow_up_at)
                                    <div class="event-flags">
                                        @if ($event->expense_minor !== null)
                                            <strong>Expense: {{ $resolutionCase->item->currency }} {{ number_format($event->expense_minor / 100, 2) }}</strong>
                                        @endif
                                        @if ($event->promised_follow_up_at)
                                            <strong>Promised follow-up: {{ $event->promised_follow_up_at->format('j M Y, g:i A') }}</strong>
                                        @endif
                                        @if ($event->reminder?->completed_at)
                                            <strong class="flag-complete">Follow-up completed {{ $event->reminder->completed_at->format('j M Y') }}</strong>
                                        @elseif ($event->reminder && ! $event->reminder->cancelled_at)
                                            <form method="POST" action="{{ route('reminders.complete', $event->reminder) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit">Mark follow-up complete</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="assessment-history" aria-labelledby="assessment-history-title">
            <div class="section-title-row">
                <div>
                    <h2 id="assessment-history-title">Assessment history</h2>
                    <p>Each version preserves the exact recorded facts and calculated result from when it was saved.</p>
                </div>
                <span>{{ str_pad((string) $resolutionCase->assessments->count(), 2, '0', STR_PAD_LEFT) }} snapshots</span>
            </div>

            @if ($resolutionCase->assessments->isEmpty())
                <p class="assessment-empty">No assessment has been saved yet. Use “Save assessment snapshot” above when you need a permanent record.</p>
            @else
                <div class="assessment-list">
                    @foreach ($resolutionCase->assessments as $assessment)
                        <article class="assessment-record">
                            <header class="assessment-record-header">
                                <div>
                                    <strong>Assessment v{{ $assessment->version }}</strong>
                                    <span>Saved by {{ $assessment->createdBy->name }}</span>
                                </div>
                                <time datetime="{{ $assessment->assessed_at->toIso8601String() }}">{{ $assessment->assessed_at->format('j M Y, g:i A') }}</time>
                            </header>

                            <div class="coverage-grid">
                                @foreach ($assessment->snapshot['coverage'] as $position)
                                    <article class="coverage-card coverage-{{ $position['state'] }}">
                                        <header>
                                            <span>{{ Str::headline($position['kind']) }} position</span>
                                            <strong>{{ Str::headline($position['state']) }}</strong>
                                        </header>
                                        <p>{{ $position['explanation'] }}</p>
                                        <dl>
                                            <div>
                                                <dt>Purchase date</dt>
                                                <dd>{{ $position['source_facts']['purchase_date'] ? Carbon\CarbonImmutable::parse($position['source_facts']['purchase_date'])->format('j M Y') : 'Missing' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Recorded period</dt>
                                                <dd>{{ $position['source_facts']['duration'] !== null ? $position['source_facts']['duration'].' '.$position['source_facts']['duration_unit'] : 'Missing' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Evaluated date</dt>
                                                <dd>{{ Carbon\CarbonImmutable::parse($position['evaluated_at'])->format('j M Y') }} · {{ $position['evaluation_source'] === 'failure_date' ? 'Problem date' : 'Today' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Recorded deadline</dt>
                                                <dd>{{ $position['deadline'] ? Carbon\CarbonImmutable::parse($position['deadline'])->format('j M Y') : 'Cannot calculate' }}</dd>
                                            </div>
                                        </dl>
                                    </article>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="evidence-checklist" aria-labelledby="evidence-checklist-title">
            <div class="section-title-row">
                <div>
                    <h2 id="evidence-checklist-title">Evidence checklist</h2>
                    @if ($evidenceChecklist['assessment'])
                        <p>Based on assessment v{{ $evidenceChecklist['assessment']->version }}, attached private documents, product identifiers, and the case timeline.</p>
                    @else
                        <p>Save an assessment snapshot first so every evidence state can point to a fixed coverage position.</p>
                    @endif
                </div>
                @if ($evidenceChecklist['assessment'])
                    <span>{{ str_pad((string) count($evidenceChecklist['items']), 2, '0', STR_PAD_LEFT) }} checks</span>
                @endif
            </div>

            @if (! $evidenceChecklist['assessment'])
                <p class="assessment-empty">No checklist is generated until an assessment snapshot has been saved.</p>
            @else
                <ul class="evidence-checklist-list">
                    @foreach ($evidenceChecklist['items'] as $evidence)
                        <li class="evidence-state-{{ str_replace('_', '-', $evidence['state']) }}">
                            <div class="evidence-check-header">
                                <strong>{{ $evidence['label'] }}</strong>
                                <span>{{ Str::headline($evidence['state']) }}</span>
                            </div>
                            <p>{{ $evidence['explanation'] }}</p>
                            <small>Source fact: {{ $evidence['source'] }}</small>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="next-step-slip action-pack-entry">
            <span>Action Pack</span>
            <strong>Turn this case into a printable factual record</strong>
            @if ($resolutionCase->actionPacks->isNotEmpty())
                <ul class="saved-pack-list">
                    @foreach ($resolutionCase->actionPacks as $actionPack)
                        <li>
                            <span>Version {{ $actionPack->version }} · {{ $actionPack->generated_at->format('j M Y, g:i A') }}</span>
                            <a href="{{ route('action-packs.download', $actionPack) }}">Download private PDF</a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($resolutionCase->assessments->isEmpty())
                <p>Save an assessment snapshot first, then choose evidence and edit the request letter.</p>
            @else
                <p>Select a fixed assessment, choose private evidence, and edit the factual request letter. Each generated PDF becomes a new immutable version.</p>
                <a class="button" href="{{ route('cases.action-packs.create', $resolutionCase) }}">Build Action Pack</a>
            @endif
        </aside>
    </section>
</x-layout>

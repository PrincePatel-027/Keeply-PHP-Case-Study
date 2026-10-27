<x-layout title="Action Pack preview">
    <div class="record-toolbar pack-screen-controls">
        <a href="{{ route('cases.action-packs.create', $pack['case']) }}">← Edit selection and letter</a>
        <div class="pack-preview-actions">
            <button class="button button-small" type="button" data-print-preview>Print preview</button>
            <form method="POST" action="{{ route('cases.action-packs.store', $pack['case']) }}">
                @csrf
                <input type="hidden" name="assessment_id" value="{{ $pack['assessment']->id }}">
                @foreach ($pack['documents'] as $document)
                    <input type="hidden" name="document_ids[]" value="{{ $document->id }}">
                @endforeach
                <textarea name="letter_body" hidden>{{ $pack['letter_body'] }}</textarea>
                <button class="button button-small" type="submit">Generate private PDF</button>
            </form>
        </div>
    </div>

    <article class="action-pack-preview">
        <header class="pack-preview-header">
            <div>
                <p class="section-code">Keeply Action Pack · Preview only</p>
                <h1>{{ $pack['case']->title }}</h1>
                <p>{{ $pack['item']->name }} · Assessment v{{ $pack['assessment']->version }}</p>
            </div>
            <div>
                <strong>Generated preview</strong>
                <time datetime="{{ $pack['generated_at']->toIso8601String() }}">{{ $pack['generated_at']->format('j M Y, g:i A') }}</time>
                <span>Not stored · No PDF created</span>
            </div>
        </header>

        <section class="pack-summary-grid">
            <div>
                <h2>Owner contact</h2>
                <dl>
                    <div><dt>Name</dt><dd>{{ $pack['owner']->name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $pack['owner']->email }}</dd></div>
                </dl>
            </div>
            <div>
                <h2>Product and seller</h2>
                <dl>
                    <div><dt>Product</dt><dd>{{ $pack['item']->name }}</dd></div>
                    <div><dt>Brand / model</dt><dd>{{ collect([$pack['item']->brand, $pack['item']->model])->filter()->join(' · ') ?: 'Not recorded' }}</dd></div>
                    <div><dt>Serial number</dt><dd>{{ $pack['item']->serial_number ?: 'Not recorded' }}</dd></div>
                    <div><dt>Seller</dt><dd>{{ $pack['item']->seller_name ?: 'Not recorded' }}</dd></div>
                    <div><dt>Purchase date</dt><dd>{{ $pack['assessment']->snapshot['item']['purchase_date'] ? Carbon\CarbonImmutable::parse($pack['assessment']->snapshot['item']['purchase_date'])->format('j M Y') : 'Not recorded' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="pack-preview-section">
            <h2>Recorded position</h2>
            <div class="pack-position-grid">
                @foreach ($pack['assessment']->snapshot['coverage'] as $position)
                    <div>
                        <span>{{ Str::headline($position['kind']) }}</span>
                        <strong>{{ Str::headline($position['state']) }}</strong>
                        <p>{{ $position['explanation'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="pack-preview-section">
            <h2>Issue and requested outcome</h2>
            <p>{{ $pack['case']->problem_summary }}</p>
            <dl class="pack-inline-facts">
                <div><dt>Problem date</dt><dd>{{ $pack['case']->failure_date?->format('j M Y') ?? 'Not recorded' }}</dd></div>
                <div><dt>Requested outcome</dt><dd>{{ Str::headline($pack['case']->desired_outcome) }}</dd></div>
                <div><dt>Case status</dt><dd>{{ $pack['case']->statusLabel() }}</dd></div>
            </dl>
        </section>

        <section class="pack-preview-section">
            <h2>Dated chronology</h2>
            @if ($pack['events']->isEmpty())
                <p>No timeline events were recorded.</p>
            @else
                <ol class="pack-chronology">
                    @foreach ($pack['events'] as $event)
                        <li>
                            <time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('j M Y, g:i A') }}</time>
                            <div>
                                <strong>{{ $event->typeLabel() }}</strong>
                                <p>{{ $event->summary }}</p>
                                @if ($event->reference)<small>Reference: {{ $event->reference }}</small>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="pack-preview-section">
            <h2>Selected private evidence</h2>
            @if ($pack['documents']->isEmpty())
                <p>No private documents were selected for this preview.</p>
            @else
                <ul class="pack-evidence-list">
                    @foreach ($pack['documents'] as $document)
                        <li>
                            <strong>{{ $document->original_name }}</strong>
                            <span>{{ Str::headline($document->pivot->purpose) }} · SHA-256 {{ $document->sha256 }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="pack-preview-section">
            <h2>Evidence still missing or optional</h2>
            <ul class="pack-checklist">
                @foreach ($pack['checklist']['items'] as $evidence)
                    @if ($evidence['state'] !== 'present')
                        <li>
                            <strong>{{ $evidence['label'] }} · {{ Str::headline($evidence['state']) }}</strong>
                            <p>{{ $evidence['explanation'] }}</p>
                        </li>
                    @endif
                @endforeach
            </ul>
        </section>

        <section class="pack-preview-section pack-letter">
            <h2>Editable factual request letter</h2>
            <div>{{ $pack['letter_body'] }}</div>
        </section>

        <footer class="pack-disclaimer">
            <strong>Factual record, not a legal determination</strong>
            <p>This preview organises information entered in Keeply. It does not interpret warranty terms, guarantee eligibility, submit a request, or promise an outcome.</p>
        </footer>
    </article>
</x-layout>

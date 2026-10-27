<x-layout title="Today">
    <section class="cabinet-intro dashboard-intro">
        <div>
            <p class="section-code">{{ $workspace->name }}</p>
            <h1>{{ $workspace->items_count ? 'Your cabinet is taking shape.' : 'Your cabinet is ready for its first record.' }}</h1>
        </div>
        <aside class="intro-note">
            @if ($workspace->items_count)
                {{ $workspace->items_count }} {{ Str::plural('product', $workspace->items_count) }} on file. Add another product or open the cabinet to review what you recorded.
            @else
                Start by adding one product worth protecting. Its purchase, warranty, and category details will stay together.
            @endif
        </aside>
    </section>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    @if ($dueReminders->isNotEmpty())
        <section class="action-docket" aria-labelledby="action-docket-title">
            <header>
                <div>
                    <p class="section-code">Action docket</p>
                    <h2 id="action-docket-title">Follow-ups due now.</h2>
                </div>
                <span>{{ str_pad((string) $dueReminders->count(), 2, '0', STR_PAD_LEFT) }} actions</span>
            </header>

            <ol>
                @foreach ($dueReminders as $reminder)
                    <li class="{{ $reminder->isOverdue() ? 'is-overdue' : 'is-due' }}">
                        <div class="action-date">
                            <strong>{{ $reminder->isOverdue() ? 'Overdue' : 'Due today' }}</strong>
                            <time datetime="{{ $reminder->due_at->toIso8601String() }}">{{ $reminder->due_at->format('j M, g:i A') }}</time>
                        </div>
                        <div class="action-copy">
                            <span>{{ $reminder->resolutionCase->item->name }}</span>
                            <a href="{{ route('cases.show', $reminder->resolutionCase) }}">{{ $reminder->resolutionCase->title }}</a>
                            <p>{{ $reminder->caseEvent->summary }}</p>
                        </div>
                        <form method="POST" action="{{ route('reminders.complete', $reminder) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit">Mark complete</button>
                        </form>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    <section class="cabinet-files dashboard-files" aria-label="Workspace sections">
        <article class="file-card" data-tab="Products">
            <span class="file-number">{{ str_pad((string) $workspace->items_count, 2, '0', STR_PAD_LEFT) }} OWNED ITEMS</span>
            <h2>Your products</h2>
            <p>Purchase facts, coverage periods, identifiers, and category-specific details are organised by product.</p>
            <a class="file-detail" href="{{ $workspace->items_count ? route('items.index') : route('items.create') }}">
                {{ $workspace->items_count ? 'Open product cabinet' : 'Add your first product' }}
            </a>
        </article>

        <article class="file-card file-card-offset" data-tab="Cases">
            <span class="file-number">{{ str_pad((string) $workspace->open_cases_count, 2, '0', STR_PAD_LEFT) }} OPEN CASES</span>
            <h2>Resolution files</h2>
            <p>Returns, repairs, warranty claims, and their current working state are organised here.</p>
            <a class="file-detail" href="{{ route('cases.index') }}">Open resolution files</a>
        </article>

        <article class="file-card file-card-lower" data-tab="Evidence">
            <span class="file-number">{{ str_pad((string) $workspace->documents_count, 2, '0', STR_PAD_LEFT) }} DOCUMENTS</span>
            <h2>Your evidence</h2>
            <p>Receipts, warranty documents, issue photographs, and references will remain private.</p>
            <span class="file-detail">Next implementation checkpoint</span>
        </article>
    </section>

    <footer class="workspace-strip">
        <span>Workspace <strong>Personal</strong></span>
        <span>Region <strong>{{ $workspace->country_code }}</strong></span>
        <span>Currency <strong>{{ $workspace->currency }}</strong></span>
        <span>Email <strong>Verified</strong></span>
    </footer>
</x-layout>

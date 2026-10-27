<x-layout title="Resolution cases">
    <section class="collection-heading">
        <div>
            <p class="section-code">{{ $workspace->name }} / Resolution</p>
            <h1>Cases that need a next move.</h1>
            <p class="collection-copy">Track a return, repair, warranty claim, or unresolved product problem without losing its current position.</p>
        </div>
    </section>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    @if ($cases->isEmpty())
        <section class="empty-folder" data-tab="Cases">
            <span class="file-number">00 OPEN CASES</span>
            <h2>No resolution file is open.</h2>
            @if ($items->isEmpty())
                <p>Add a product first. A case always belongs to something you own.</p>
                <a class="button" href="{{ route('items.create') }}">Add a product</a>
            @else
                <p>Choose the product that has a return, repair, warranty, or support problem.</p>
                <div class="case-product-list">
                    @foreach ($items as $item)
                        <a href="{{ route('items.cases.create', $item) }}">
                            <span>{{ $item->category->name }}</span>
                            <strong>{{ $item->name }}</strong>
                            <small>Open a case →</small>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="case-list" aria-label="Resolution cases">
            @foreach ($cases as $case)
                <a class="case-row" href="{{ route('cases.show', $case) }}">
                    <span class="case-priority case-priority-{{ $case->priority }}">{{ $case->priority }}</span>
                    <div>
                        <span>{{ $case->item->name }}</span>
                        <h2>{{ $case->title }}</h2>
                    </div>
                    <strong>{{ $case->statusLabel() }}</strong>
                    <small>Opened {{ $case->opened_at->format('j M Y') }}</small>
                </a>
            @endforeach
        </section>

        <section class="new-case-strip">
            <h2>Something else went wrong?</h2>
            <div>
                @foreach ($items as $item)
                    <a href="{{ route('items.cases.create', $item) }}">Open case for {{ $item->name }}</a>
                @endforeach
            </div>
        </section>
    @endif
</x-layout>

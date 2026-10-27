<x-layout title="Products">
    <section class="collection-heading">
        <div>
            <p class="section-code">{{ $workspace->name }} / Products</p>
            <h1>Your product cabinet.</h1>
            <p class="collection-copy">Keep the facts you will need if something breaks, needs returning, or falls under warranty.</p>
        </div>
        <a class="button" href="{{ route('items.create') }}">Add a product</a>
    </section>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    @if ($items->isEmpty())
        <section class="empty-folder" data-tab="Products">
            <span class="file-number">00 OWNED ITEMS</span>
            <h2>Start with something worth protecting.</h2>
            <p>Add one product you would want a receipt, serial number, or warranty details for later.</p>
            <a class="button" href="{{ route('items.create') }}">Add your first product</a>
        </section>
    @else
        <section class="record-grid" aria-label="Saved products">
            @foreach ($items as $item)
                <a class="record-card" href="{{ route('items.show', $item) }}">
                    <span class="record-type">{{ $item->category->name }}</span>
                    <h2>{{ $item->name }}</h2>
                    <p>{{ collect([$item->brand, $item->model])->filter()->join(' · ') ?: 'Product details on file' }}</p>
                    <span class="record-meta">Added {{ $item->created_at->format('j M Y') }}</span>
                </a>
            @endforeach
        </section>
    @endif
</x-layout>

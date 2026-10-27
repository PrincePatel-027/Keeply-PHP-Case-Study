<x-layout title="Edit timeline entry">
    <div class="record-toolbar">
        <a href="{{ route('cases.show', $resolutionCase) }}">← {{ $resolutionCase->title }}</a>
        <span>Edit timeline entry</span>
    </div>

    <section class="case-form-shell" data-tab="Edit event">
        <header class="case-form-heading">
            <p class="section-code">{{ $resolutionCase->item->name }} / {{ $caseEvent->typeLabel() }}</p>
            <h1>Correct this entry.</h1>
            <p>Changing its date or time will reposition it automatically in the case chronology.</p>
        </header>

        <form method="POST" action="{{ route('cases.events.update', [$resolutionCase, $caseEvent]) }}" class="product-form">
            @csrf
            @method('PATCH')
            @include('cases.events.fields', ['event' => $caseEvent])

            <div class="form-actions">
                <a href="{{ route('cases.show', $resolutionCase) }}">Cancel</a>
                <button class="button" type="submit">Save correction</button>
            </div>
        </form>
    </section>
</x-layout>

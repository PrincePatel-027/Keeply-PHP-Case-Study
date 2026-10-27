<x-layout title="{{ $item->name }}">
    <div class="record-toolbar">
        <a href="{{ route('items.index') }}">← All products</a>
        <span>{{ $item->category->name }}</span>
    </div>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    <section class="record-file" data-tab="{{ $item->category->name }}">
        <header class="record-header">
            <div>
                <p class="section-code">Product record #{{ str_pad((string) $item->id, 4, '0', STR_PAD_LEFT) }}</p>
                <h1>{{ $item->name }}</h1>
                <p>{{ collect([$item->brand, $item->model])->filter()->join(' · ') ?: 'No brand or model recorded' }}</p>
            </div>
            <span class="status-stamp">On file</span>
        </header>

        <div class="fact-section">
            <h2>Purchase and coverage</h2>
            <dl class="fact-grid">
                <div><dt>Purchased</dt><dd>{{ $item->purchase_date?->format('j M Y') ?? 'Not recorded' }}</dd></div>
                <div><dt>Price</dt><dd>{{ $item->purchase_price_minor !== null ? $item->currency.' '.number_format($item->purchase_price_minor / 100, 2) : 'Not recorded' }}</dd></div>
                <div><dt>Seller</dt><dd>{{ $item->seller_name ?: 'Not recorded' }}</dd></div>
                <div><dt>Return period</dt><dd>{{ $item->return_days !== null ? $item->return_days.' days' : 'Unknown' }}</dd></div>
                <div><dt>Warranty</dt><dd>{{ $item->warranty_months !== null ? $item->warranty_months.' months' : 'Unknown' }}</dd></div>
                <div><dt>Serial number</dt><dd>{{ $item->serial_number ?: 'Not recorded' }}</dd></div>
            </dl>
        </div>

        <div class="fact-section">
            <h2>{{ $item->category->name }} details</h2>
            @if ($item->values->isEmpty())
                <p class="muted-copy">No category-specific details were recorded.</p>
            @else
                <dl class="fact-grid">
                    @foreach ($item->values->sortBy('categoryField.sort_order') as $value)
                        <div>
                            <dt>{{ $value->categoryField->label }}</dt>
                            <dd>{{ $value->displayValue() }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>

        <div class="fact-section document-section">
            <div class="section-title-row">
                <div>
                    <h2>Private documents</h2>
                    <p>Receipts, warranty files, manuals, and product photos stay outside the public web directory.</p>
                </div>
                <span>{{ str_pad((string) $item->documents->count(), 2, '0', STR_PAD_LEFT) }} files</span>
            </div>

            <form method="POST" action="{{ route('items.documents.store', $item) }}" enctype="multipart/form-data" class="upload-slip">
                @csrf
                <div class="field">
                    <label for="purpose">Document type</label>
                    <select id="purpose" name="purpose" required @error('purpose') aria-invalid="true" aria-describedby="purpose-error" @enderror>
                        <option value="">Choose a type</option>
                        <option value="receipt" @selected(old('purpose') === 'receipt')>Receipt or invoice</option>
                        <option value="warranty" @selected(old('purpose') === 'warranty')>Warranty document</option>
                        <option value="manual" @selected(old('purpose') === 'manual')>Manual</option>
                        <option value="product_photo" @selected(old('purpose') === 'product_photo')>Product photo</option>
                        <option value="other" @selected(old('purpose') === 'other')>Other evidence</option>
                    </select>
                    @error('purpose') <p id="purpose-error" class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field">
                    <label for="document">Choose file</label>
                    <input id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required @error('document') aria-invalid="true" aria-describedby="document-help document-error" @else aria-describedby="document-help" @enderror>
                    <p id="document-help" class="field-hint">PDF, JPG, PNG, or WebP. Maximum 10 MB.</p>
                    @error('document') <p id="document-error" class="field-error">{{ $message }}</p> @enderror
                </div>

                <button class="button" type="submit">Attach privately</button>
            </form>

            @if ($item->documents->isEmpty())
                <p class="document-empty">No documents attached yet.</p>
            @else
                <ul class="document-list">
                    @foreach ($item->documents as $document)
                        <li>
                            <div>
                                <span>{{ Str::headline($document->pivot->purpose) }}</span>
                                <strong>{{ $document->original_name }}</strong>
                                <small>{{ number_format($document->size_bytes / 1024, 1) }} KB · Added {{ $document->created_at->format('j M Y') }}</small>
                            </div>
                            <a href="{{ route('documents.download', $document) }}">Download</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="fact-section">
            <div class="section-title-row">
                <div>
                    <h2>Resolution cases</h2>
                    <p>Open a file when this product needs a return, repair, warranty claim, or support follow-up.</p>
                </div>
                <a class="button button-small" href="{{ route('items.cases.create', $item) }}">Open a case</a>
            </div>

            @if ($item->resolutionCases->isEmpty())
                <p class="document-empty">No resolution cases for this product.</p>
            @else
                <ul class="product-case-list">
                    @foreach ($item->resolutionCases as $case)
                        <li>
                            <div>
                                <span>{{ $case->statusLabel() }}</span>
                                <strong>{{ $case->title }}</strong>
                            </div>
                            <a href="{{ route('cases.show', $case) }}">Open file</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <aside class="next-step-slip">
            <span>Next capability</span>
            <strong>Chronological contacts, promises, costs, and follow-ups</strong>
            <p>Product facts, private evidence, and resolution cases now work together. Timeline events are next.</p>
        </aside>
    </section>
</x-layout>

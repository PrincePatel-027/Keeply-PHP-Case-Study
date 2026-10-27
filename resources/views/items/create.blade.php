<x-layout title="Add product">
    <section class="form-heading">
        <p class="section-code">{{ $workspace->name }} / New record</p>
        <h1>Add a product.</h1>
        <p>Record what you know now. Optional facts can be left blank.</p>
    </section>

    <section class="product-folder" data-tab="Product record">
        <form method="GET" action="{{ route('items.create') }}" class="category-picker">
            <div class="field">
                <label for="category">Product category</label>
                <select id="category" name="category" required>
                    <option value="">Choose a category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategory?->is($category))>{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="field-hint">The category decides which useful details Keeply asks for.</p>
            </div>
            <button class="button button-secondary" type="submit">Use this category</button>
        </form>

        @if ($selectedCategory)
            <form method="POST" action="{{ route('items.store') }}" class="product-form">
                @csrf
                <input type="hidden" name="category_id" value="{{ $selectedCategory->id }}">

                <div class="form-section">
                    <div class="form-section-heading">
                        <span>01</span>
                        <div>
                            <h2>Identify it</h2>
                            <p>The everyday name is required. Everything else can be added when available.</p>
                        </div>
                    </div>

                    <div class="field">
                        <label for="name">Product name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name') <p id="name-error" class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label for="brand">Brand</label>
                            <input id="brand" name="brand" type="text" value="{{ old('brand') }}" @error('brand') aria-invalid="true" aria-describedby="brand-error" @enderror>
                            @error('brand') <p id="brand-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="model">Model</label>
                            <input id="model" name="model" type="text" value="{{ old('model') }}" @error('model') aria-invalid="true" aria-describedby="model-error" @enderror>
                            @error('model') <p id="model-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="serial_number">Serial number</label>
                        <input id="serial_number" name="serial_number" type="text" value="{{ old('serial_number') }}" autocomplete="off" @error('serial_number') aria-invalid="true" aria-describedby="serial-number-error" @enderror>
                        <p class="field-hint">Encrypted before it is stored.</p>
                        @error('serial_number') <p id="serial-number-error" class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-heading">
                        <span>02</span>
                        <div>
                            <h2>Purchase facts</h2>
                            <p>These facts will later power return and warranty deadlines.</p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label for="purchase_date">Purchase date</label>
                            <input id="purchase_date" name="purchase_date" type="date" value="{{ old('purchase_date') }}" @error('purchase_date') aria-invalid="true" aria-describedby="purchase-date-error" @enderror>
                            @error('purchase_date') <p id="purchase-date-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="purchase_price">Purchase price ({{ $workspace->currency }})</label>
                            <input id="purchase_price" name="purchase_price" type="number" min="0" step="0.01" value="{{ old('purchase_price') }}" @error('purchase_price') aria-invalid="true" aria-describedby="purchase-price-error" @enderror>
                            @error('purchase_price') <p id="purchase-price-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="seller_name">Seller</label>
                        <input id="seller_name" name="seller_name" type="text" value="{{ old('seller_name') }}" @error('seller_name') aria-invalid="true" aria-describedby="seller-name-error" @enderror>
                        @error('seller_name') <p id="seller-name-error" class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label for="return_days">Return period (days)</label>
                            <input id="return_days" name="return_days" type="number" min="0" value="{{ old('return_days') }}" @error('return_days') aria-invalid="true" aria-describedby="return-days-error" @enderror>
                            @error('return_days') <p id="return-days-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="warranty_months">Warranty length (months)</label>
                            <input id="warranty_months" name="warranty_months" type="number" min="0" value="{{ old('warranty_months') }}" @error('warranty_months') aria-invalid="true" aria-describedby="warranty-months-error" @enderror>
                            @error('warranty_months') <p id="warranty-months-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-heading">
                        <span>03</span>
                        <div>
                            <h2>{{ $selectedCategory->name }} details</h2>
                            <p>These fields come from Keeply's category definitions, not a hard-coded form.</p>
                        </div>
                    </div>

                    @foreach ($selectedCategory->fields as $field)
                        <div class="field">
                            @if ($field->data_type === 'boolean')
                                <input type="hidden" name="dynamic[{{ $field->key }}]" value="0">
                                <label class="check-row" for="dynamic_{{ $field->key }}">
                                    <input id="dynamic_{{ $field->key }}" name="dynamic[{{ $field->key }}]" type="checkbox" value="1" @checked(old('dynamic.'.$field->key))>
                                    {{ $field->label }}
                                </label>
                            @else
                                <label for="dynamic_{{ $field->key }}">{{ $field->label }} @if ($field->is_required)<span aria-hidden="true">*</span>@endif</label>

                                @if ($field->data_type === 'select')
                                    <select id="dynamic_{{ $field->key }}" name="dynamic[{{ $field->key }}]" @required($field->is_required) @error('dynamic.'.$field->key) aria-invalid="true" aria-describedby="dynamic-{{ $field->key }}-error" @enderror>
                                        <option value="">Choose one</option>
                                        @foreach ($field->options as $value => $label)
                                            <option value="{{ $value }}" @selected(old('dynamic.'.$field->key) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="dynamic_{{ $field->key }}" name="dynamic[{{ $field->key }}]" type="{{ match ($field->data_type) { 'integer', 'decimal' => 'number', 'date' => 'date', default => 'text' } }}" @if ($field->data_type === 'decimal') step="any" @endif value="{{ old('dynamic.'.$field->key) }}" @required($field->is_required) @error('dynamic.'.$field->key) aria-invalid="true" aria-describedby="dynamic-{{ $field->key }}-error" @enderror>
                                @endif
                            @endif

                            @if ($field->help_text)<p class="field-hint">{{ $field->help_text }}</p>@endif
                            @error('dynamic.'.$field->key) <p id="dynamic-{{ $field->key }}-error" class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="form-actions">
                    <a href="{{ route('items.index') }}">Cancel</a>
                    <button class="button" type="submit">Save product</button>
                </div>
            </form>
        @else
            <div class="category-placeholder">
                <span>Step 1 of 2</span>
                <h2>Choose what you are filing.</h2>
                <p>Keeply will reveal only the details relevant to that category.</p>
            </div>
        @endif
    </section>
</x-layout>

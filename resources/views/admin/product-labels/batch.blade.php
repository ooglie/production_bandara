@extends('layouts.company')

@section('title', 'Batch labels - ' . $product->name)

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-gray-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
        $labelClass = 'block text-[11px] font-medium text-gray-700 dark:text-gray-300';
        $oldPieceIds = array_map('strval', (array) old('inventory_piece_ids', []));
        $oldPackIds = array_map('strval', (array) old('inventory_pack_ids', []));
    @endphp

    <style>
        @include('labels._styles')
        .label-browser-preview { width: 384px; max-width: 100%; }
        .label-browser-preview .product-label-canvas { transform-origin: top left; }
    </style>

    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('admin.labels.index') }}" class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">&larr; Product labels</a>
                <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">Variable-weight batch: {{ $product->name }}</h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Select available inventory and/or enter weights. One 4 x 3 inch label is generated for every weight.
                </p>
            </div>
            <a href="{{ route('admin.labels.edit', $product) }}"
               class="rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                Single label
            </a>
        </div>

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                Please correct the highlighted fields before generating the batch.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.labels.batch.pdf', $product) }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_430px]">
            @csrf

            <div class="space-y-5">
                <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Automatic price calculation</h2>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Total label price = database price per kg x exact weight.</p>
                        </div>
                        <div class="w-52">
                            <label for="price_per_kg" class="{{ $labelClass }}">Retail price per kg (₹)</label>
                            <input id="price_per_kg" name="price_per_kg" type="number" min="0.01" max="999999.99" step="0.01"
                                   value="{{ old('price_per_kg', $form['price_per_kg']) }}" required class="{{ $input }}">
                            @error('price_per_kg') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 rounded-lg bg-gray-50 p-4 text-center sm:grid-cols-3 dark:bg-gray-900">
                        <div><div class="text-[10px] uppercase text-gray-400">Labels</div><div id="batch-count" class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">0</div></div>
                        <div><div class="text-[10px] uppercase text-gray-400">Total weight</div><div id="batch-weight" class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">0 kg</div></div>
                        <div><div class="text-[10px] uppercase text-gray-400">Combined value</div><div id="batch-value" class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-100">₹0.00</div></div>
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Available inventory weights</h2>
                            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Weights and expiry months are read directly from available pieces and packs.</p>
                        </div>
                        @if($inventory['pieces'] || $inventory['packs'])
                            <button type="button" id="select-all-inventory" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] dark:border-gray-700">Select all</button>
                        @endif
                    </div>

                    @error('inventory_piece_ids') <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @error('inventory_pack_ids') <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p> @enderror

                    @if($inventory['pieces'])
                        <div class="mt-4">
                            <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Individual pieces</h3>
                            <div class="mt-2 max-h-64 overflow-auto rounded-md border border-gray-200 dark:border-gray-800">
                                @foreach($inventory['pieces'] as $item)
                                    <label class="grid cursor-pointer grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-b border-gray-100 px-3 py-2 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900">
                                        <input type="checkbox" name="inventory_piece_ids[]" value="{{ $item['source_id'] }}"
                                               class="js-inventory-weight rounded border-gray-300"
                                               data-weight="{{ $item['weight_kg'] }}"
                                               data-best-before="{{ $item['best_before'] }}"
                                               @checked(in_array((string) $item['source_id'], $oldPieceIds, true))>
                                        <span>
                                            <span class="block font-medium text-gray-800 dark:text-gray-200">{{ $item['reference'] }}</span>
                                            <span class="block text-[10px] text-gray-400">
                                                {{ $item['batch_code'] ? 'Batch ' . $item['batch_code'] : 'No batch code' }}
                                                @if($item['expiry_label']) &middot; Best before {{ $item['expiry_label'] }} @endif
                                            </span>
                                        </span>
                                        <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $item['weight_label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($inventory['packs'])
                        <div class="mt-4">
                            <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Finished packs</h3>
                            <div class="mt-2 max-h-64 overflow-auto rounded-md border border-gray-200 dark:border-gray-800">
                                @foreach($inventory['packs'] as $item)
                                    <label class="grid cursor-pointer grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-b border-gray-100 px-3 py-2 last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900">
                                        <input type="checkbox" name="inventory_pack_ids[]" value="{{ $item['source_id'] }}"
                                               class="js-inventory-weight rounded border-gray-300"
                                               data-weight="{{ $item['weight_kg'] }}"
                                               data-best-before="{{ $item['best_before'] }}"
                                               @checked(in_array((string) $item['source_id'], $oldPackIds, true))>
                                        <span>
                                            <span class="block font-medium text-gray-800 dark:text-gray-200">{{ $item['reference'] }}</span>
                                            <span class="block text-[10px] text-gray-400">
                                                {{ $item['batch_code'] ? 'Batch ' . $item['batch_code'] : 'No batch code' }}
                                                @if($item['expiry_label']) &middot; Best before {{ $item['expiry_label'] }} @endif
                                            </span>
                                        </span>
                                        <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $item['weight_label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!$inventory['pieces'] && !$inventory['packs'])
                        <div class="mt-4 rounded-md border border-dashed border-gray-300 px-4 py-5 text-center text-[11px] text-gray-500 dark:border-gray-700">
                            No available weighted inventory was found for this product. Enter weights manually below.
                        </div>
                    @endif

                    <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <label for="manual_weights" class="{{ $labelClass }}">Additional/manual weights in kg</label>
                        <textarea id="manual_weights" name="manual_weights" rows="4" class="{{ $input }}"
                                  placeholder="3.5&#10;4.2&#10;5.5">{{ old('manual_weights', $form['manual_weights']) }}</textarea>
                        <p class="mt-1 text-[10px] text-gray-400">Enter one weight per line, or separate weights with commas. Up to 100 labels per batch.</p>
                        @error('manual_weights') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Shared label information</h2>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">These details are reused on every label in this batch.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="category" class="{{ $labelClass }}">Category badge</label>
                            <input id="category" name="category" value="{{ old('category', $form['category']) }}" maxlength="24" required class="{{ $input }}">
                            @error('category') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="country" class="{{ $labelClass }}">Country of origin</label>
                            <input id="country" name="country" value="{{ old('country', $form['country']) }}" maxlength="32" required class="{{ $input }}">
                            @error('country') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="product_name" class="{{ $labelClass }}">Product name</label>
                            <input id="product_name" name="product_name" value="{{ old('product_name', $form['product_name']) }}" maxlength="64" required class="{{ $input }}">
                            @error('product_name') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="company_name" class="{{ $labelClass }}">Company name</label>
                            <input id="company_name" name="company_name" value="{{ old('company_name', $form['company_name']) }}" maxlength="40" required class="{{ $input }}">
                            @error('company_name') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="fssai" class="{{ $labelClass }}">FSSAI licence number</label>
                            <input id="fssai" name="fssai" inputmode="numeric" value="{{ old('fssai', $form['fssai']) }}" maxlength="14" required class="{{ $input }}">
                            @error('fssai') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="website" class="{{ $labelClass }}">Website</label>
                            <input id="website" name="website" type="url" value="{{ old('website', $form['website']) }}" maxlength="100" required class="{{ $input }}">
                            @error('website') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="best_before" class="{{ $labelClass }}">Default best-before month</label>
                            <input id="best_before" name="best_before" type="month" value="{{ old('best_before', $form['best_before']) }}" required class="{{ $input }}">
                            <p class="mt-1 text-[10px] text-gray-400">Inventory expiry overrides this value when available.</p>
                            @error('best_before') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-200 pt-5 dark:border-gray-800">
                        <button type="submit" name="disposition" value="inline" formtarget="_blank"
                                class="rounded-md bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                            Preview batch PDF
                        </button>
                        <button type="submit" name="disposition" value="download"
                                class="rounded-md border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                            Download batch PDF
                        </button>
                    </div>
                </section>
            </div>

            <aside class="space-y-3 xl:sticky xl:top-5 xl:self-start">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-xs font-semibold text-gray-900 dark:text-gray-100">First-label preview</h2>
                        <span class="text-[10px] text-gray-400">4 x 3 inches</span>
                    </div>
                    <div class="overflow-auto rounded-md border border-gray-200 bg-gray-100 p-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="label-browser-preview">
                            @include('labels._canvas')
                        </div>
                    </div>
                </div>
                <p class="text-[10px] leading-4 text-gray-500 dark:text-gray-400">
                    Each selected or entered weight becomes its own PDF page. Print at 100% / actual size.
                </p>
            </aside>
        </form>
    </div>

    <script>
        (() => {
            const field = (name) => document.getElementById(name);
            const targets = (name) => document.querySelectorAll(`[data-label-field="${name}"]`);
            const write = (name, value) => targets(name).forEach((node) => node.textContent = value);
            const money = new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const number = (value) => Number(value.toFixed(3)).toString();
            const formatWeight = (kg) => kg < 1 ? `${number(kg * 1000)} gms` : `${number(kg)} kg`;
            const categorySize = (value) => value.length <= 5 ? 21.3 : value.length <= 8 ? 16 : value.length <= 12 ? 12 : 9;
            const productSize = (value) => value.length <= 20 ? 10 : value.length <= 28 ? 8.2 : value.length <= 36 ? 7 : 6;

            const inventoryRows = () => Array.from(document.querySelectorAll('.js-inventory-weight:checked')).map((box) => ({
                weight: Number.parseFloat(box.dataset.weight),
                bestBefore: box.dataset.bestBefore || null,
            }));

            const manualRows = () => field('manual_weights').value
                .replace(/\bkg\b/gi, ' ')
                .trim()
                .split(/[\s,;]+/)
                .map((value) => Number.parseFloat(value))
                .filter((value) => Number.isFinite(value) && value > 0)
                .map((weight) => ({ weight, bestBefore: null }));

            const monthLabel = (value) => {
                if (!value) return '';
                const [year, month] = value.split('-').map(Number);
                return new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric', timeZone: 'UTC' })
                    .format(new Date(Date.UTC(year, month - 1, 1)));
            };

            const refresh = () => {
                const rows = [...inventoryRows(), ...manualRows()];
                const rate = Number.parseFloat(field('price_per_kg').value || '0');
                const totalWeight = rows.reduce((sum, row) => sum + row.weight, 0);
                const totalValue = rows.reduce((sum, row) => sum + (row.weight * rate), 0);

                document.getElementById('batch-count').textContent = rows.length;
                document.getElementById('batch-weight').textContent = `${number(totalWeight)} kg`;
                document.getElementById('batch-value').textContent = `₹${money.format(totalValue)}`;

                const first = rows[0] || { weight: 1, bestBefore: null };
                const category = field('category').value.trim();
                const country = field('country').value.trim().toUpperCase();
                const productName = field('product_name').value.trim().toUpperCase();
                const company = field('company_name').value.trim().toUpperCase();
                const website = field('website').value.trim();

                write('category', category);
                targets('category').forEach((node) => {
                    const size = categorySize(category);
                    node.style.fontSize = `${size}pt`;
                    node.style.top = `${-5.2 + ((21.3 - size) * 0.45)}pt`;
                });
                write('country', country);
                targets('country').forEach((node) => node.style.fontSize = `${country.length <= 18 ? 8 : 6.6}pt`);
                write('product_name', productName);
                targets('product_name').forEach((node) => node.style.fontSize = `${productSize(productName)}pt`);
                write('price', (first.weight * rate).toFixed(2));
                write('unit_label', formatWeight(first.weight));
                write('company_name', company);
                targets('company_name').forEach((node) => node.style.fontSize = `${company.length <= 16 ? 8 : 6.8}pt`);
                write('fssai', field('fssai').value.trim());
                write('website', website);
                targets('website').forEach((node) => node.style.fontSize = `${website.length <= 27 ? 6 : 5}pt`);
                write('best_before_label', monthLabel(first.bestBefore || field('best_before').value));
            };

            document.querySelectorAll('.js-inventory-weight').forEach((box) => box.addEventListener('change', refresh));
            ['price_per_kg', 'manual_weights', 'category', 'country', 'product_name', 'company_name', 'fssai', 'website', 'best_before']
                .forEach((name) => field(name).addEventListener('input', refresh));

            document.getElementById('select-all-inventory')?.addEventListener('click', (event) => {
                const boxes = Array.from(document.querySelectorAll('.js-inventory-weight'));
                const select = boxes.some((box) => !box.checked);
                boxes.forEach((box) => box.checked = select);
                event.currentTarget.textContent = select ? 'Clear all' : 'Select all';
                refresh();
            });

            refresh();
        })();
    </script>
@endsection

@extends('layouts.company')

@section('title', 'Label - ' . $product->name)

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-xs text-gray-900 shadow-sm focus:border-gray-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
        $labelClass = 'block text-[11px] font-medium text-gray-700 dark:text-gray-300';
    @endphp

    <style>
        @include('labels._styles')
        .label-browser-preview { width: 384px; max-width: 100%; }
        .label-browser-preview .product-label-canvas { transform-origin: top left; }
    </style>

    <div class="max-w-7xl mx-auto space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('admin.labels.index') }}" class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">← Product labels</a>
                <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $product->name }}</h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Database values are prefilled. Changes here affect only this print job.</p>
            </div>
            @if($batchEnabled)
                <a href="{{ route('admin.labels.batch.edit', $product) }}"
                   class="rounded-md bg-gray-900 px-3 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                    Batch by weight
                </a>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.labels.pdf', $product) }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_430px]">
            @csrf

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950">
                <div class="grid gap-4 sm:grid-cols-2">
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
                        <label for="price" class="{{ $labelClass }}">MRP including taxes (₹)</label>
                        <input id="price" name="price" type="number" min="0" max="999999.99" step="0.01" value="{{ old('price', $form['price']) }}" required class="{{ $input }}">
                        @error('price') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="unit_label" class="{{ $labelClass }}">Weight / pack text</label>
                        <input id="unit_label" name="unit_label" value="{{ old('unit_label', $form['unit_label']) }}" maxlength="24" required class="{{ $input }}">
                        @error('unit_label') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
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
                    <div class="sm:col-span-2">
                        <label for="website" class="{{ $labelClass }}">Website</label>
                        <input id="website" name="website" type="url" value="{{ old('website', $form['website']) }}" maxlength="100" required class="{{ $input }}">
                        @error('website') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="best_before" class="{{ $labelClass }}">Best before month</label>
                        <input id="best_before" name="best_before" type="month" value="{{ old('best_before', $form['best_before']) }}" required class="{{ $input }}">
                        @error('best_before') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="copies" class="{{ $labelClass }}">Copies</label>
                        <input id="copies" name="copies" type="number" min="1" max="100" value="{{ old('copies', $form['copies']) }}" required class="{{ $input }}">
                        @error('copies') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-200 pt-5 dark:border-gray-800">
                    <button type="submit" name="disposition" value="inline" formtarget="_blank"
                            class="rounded-md bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">
                        Preview PDF
                    </button>
                    <button type="submit" name="disposition" value="download"
                            class="rounded-md border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">
                        Download PDF
                    </button>
                </div>
            </div>

            <aside class="space-y-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-xs font-semibold text-gray-900 dark:text-gray-100">Live preview</h2>
                        <span class="text-[10px] text-gray-400">4 × 3 inches</span>
                    </div>
                    <div class="overflow-auto rounded-md border border-gray-200 bg-gray-100 p-2 dark:border-gray-700 dark:bg-gray-900">
                        <div class="label-browser-preview">
                            @include('labels._canvas')
                        </div>
                    </div>
                </div>
                <p class="text-[10px] leading-4 text-gray-500 dark:text-gray-400">
                    Print at 100% / actual size. Disable “fit to page” in the printer dialog.
                </p>
            </aside>
        </form>
    </div>

    <script>
        (() => {
            const field = (name) => document.getElementById(name);
            const targets = (name) => document.querySelectorAll(`[data-label-field="${name}"]`);
            const write = (name, value) => targets(name).forEach((node) => node.textContent = value);

            const categorySize = (value) => value.length <= 5 ? 21.3 : value.length <= 8 ? 16 : value.length <= 12 ? 12 : 9;
            const productSize = (value) => value.length <= 20 ? 10 : value.length <= 28 ? 8.2 : value.length <= 36 ? 7 : 6;

            const refresh = () => {
                const category = field('category').value.trim();
                const country = field('country').value.trim().toUpperCase();
                const productName = field('product_name').value.trim().toUpperCase();
                const price = Number.parseFloat(field('price').value || '0').toFixed(2);
                const unit = field('unit_label').value.trim();
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
                write('price', price);
                write('unit_label', unit);
                write('company_name', company);
                targets('company_name').forEach((node) => node.style.fontSize = `${company.length <= 16 ? 8 : 6.8}pt`);
                write('fssai', field('fssai').value.trim());
                write('website', website);
                targets('website').forEach((node) => node.style.fontSize = `${website.length <= 27 ? 6 : 5}pt`);

                const month = field('best_before').value;
                if (month) {
                    const [year, monthNumber] = month.split('-').map(Number);
                    const label = new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric', timeZone: 'UTC' })
                        .format(new Date(Date.UTC(year, monthNumber - 1, 1)));
                    write('best_before_label', label);
                }
            };

            ['category', 'country', 'product_name', 'price', 'unit_label', 'company_name', 'fssai', 'website', 'best_before']
                .forEach((name) => field(name).addEventListener('input', refresh));

            refresh();
        })();
    </script>
@endsection

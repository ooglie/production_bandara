@extends('layouts.company')

@section('title', 'Edit Customer Price')

@section('content')
@php
    $variantsRouteExists = \Illuminate\Support\Facades\Route::has('admin.products.variants.options');
@endphp

<div class="max-w-4xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Edit Customer-specific Price
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer: <span class="text-gray-900 dark:text-gray-50 font-medium">{{ $user->name ?? '—' }}</span>
                <span class="text-gray-400">(#{{ $user->id }})</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.b2b.prices.index', $user) }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.b2b.prices.update', [$user, $price]) }}"
          class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Product
                </label>
                <select name="product_id" id="product_id"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                    <option value="">Select product…</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}"
                            @selected((int)old('product_id', $price->product_id) === (int)$p->id)>
                            {{ $p->name }} @if($p->sku) ({{ $p->sku }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Variant (optional)
                </label>

                <select name="product_variant_id" id="product_variant_id"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                    <option value="">Product-level (no variant)</option>
                </select>

                @error('product_variant_id')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Price (₹)
                </label>
                <input type="number" step="0.01" min="0"
                       name="price"
                       value="{{ old('price', $price->price) }}"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                @error('price')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valid from (optional)
                </label>
                <input type="date" name="valid_from"
                       value="{{ old('valid_from', optional($price->valid_from)->format('Y-m-d')) }}"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Valid to (optional)
                </label>
                <input type="date" name="valid_to"
                       value="{{ old('valid_to', optional($price->valid_to)->format('Y-m-d')) }}"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (bool)$price->is_active))>
                <span>Active</span>
            </label>

            <input type="hidden" name="currency" value="{{ old('currency', $price->currency ?? 'INR') }}">
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="{{ route('admin.b2b.prices.index', $user) }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Update
            </button>
        </div>
    </form>

    @if(!$variantsRouteExists)
        <div class="rounded border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
            Variant dropdown endpoint missing. Add route <code>admin.products.variants.options</code> to enable variants.
        </div>
    @endif
</div>

<script>
(function () {
    const productSelect = document.getElementById('product_id');
    const variantSelect = document.getElementById('product_variant_id');

    const selectedVariantId = @json(old('product_variant_id', $price->product_variant_id));
    const routeExists = @json(\Illuminate\Support\Facades\Route::has('admin.products.variants.options'));

    const urlTemplate = routeExists
        ? @json(route('admin.products.variants.options', ['product' => '__PRODUCT__']))
        : null;

    function resetVariants() {
        variantSelect.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Product-level (no variant)';
        variantSelect.appendChild(opt);
    }

    async function loadVariants(productId, preselect = null) {
        resetVariants();
        if (!productId || !urlTemplate) return;

        const url = urlTemplate.replace('__PRODUCT__', productId);

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
            if (!res.ok) return;

            const data = await res.json();
            const variants = (data && data.variants) ? data.variants : [];

            variants.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label || ('Variant #' + v.id);
                variantSelect.appendChild(opt);
            });

            if (preselect) {
                variantSelect.value = String(preselect);
            }
        } catch (e) {
            // silent
        }
    }

    if (productSelect) {
        productSelect.addEventListener('change', function () {
            loadVariants(this.value, null);
        });

        if (productSelect.value) {
            loadVariants(productSelect.value, selectedVariantId);
        }
    }
})();
</script>
@endsection

@php
    /** @var \App\Models\User $user */
    /** @var \App\Models\CustomerProductPrice|null $price */
    $isEdit = isset($price);
    $selectedProductId = old('product_id', $price->product_id ?? '');
    $selectedVariantId = old('product_variant_id', $price->product_variant_id ?? '');
    $selectedSellUnitId = old('product_sell_unit_id', $price->product_sell_unit_id ?? '');
@endphp

<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
    <div class="grid gap-3 md:grid-cols-3">
        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Product</label>
            <select id="b2b-price-product"
                    name="product_id"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                    required>
                <option value="">Select…</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}"
                        @selected((int) $selectedProductId === (int) $p->id)>
                        {{ $p->name }} @if($p->sku) ({{ $p->sku }}) @endif
                    </option>
                @endforeach
            </select>
            @error('product_id')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">
                Variant (optional)
            </label>

            <select id="b2b-price-variant"
                    name="product_variant_id"
                    data-url-template="{{ route('admin.products.variants.index', ['product' => '__PRODUCT__']) }}"
                    data-selected="{{ $selectedVariantId }}"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                <option value="">Product-level (no variant)</option>
                {{-- JS will load variants when product is selected --}}
            </select>

            <p class="mt-1 text-[10px] text-gray-400">
                If you choose a variant, this price applies only to that variant.
            </p>

            @error('product_variant_id')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">
                Sellable unit (optional)
            </label>
            <select id="b2b-price-sell-unit"
                    name="product_sell_unit_id"
                    data-selected="{{ $selectedSellUnitId }}"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
                <option value="">Product-level / variant-level</option>
                @foreach($products as $p)
                    @foreach(($p->sellUnits ?? collect()) as $unit)
                        <option value="{{ $unit->id }}" data-product-id="{{ $p->id }}" @selected((int)$selectedSellUnitId === (int)$unit->id)>
                            {{ $unit->display_label }}
                        </option>
                    @endforeach
                @endforeach
            </select>
            <p class="mt-1 text-[10px] text-gray-400">
                Use for pack/box-specific B2B pricing. Leave variant blank when using a sellable-unit price.
            </p>
            @error('product_sell_unit_id')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-4">
        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Price (₹, excl GST by default)</label>
            <input type="number" step="0.01" min="0" name="price"
                   value="{{ old('price', $price->price ?? '') }}"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                   required>
            @error('price')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Currency</label>
            <input type="text" name="currency" maxlength="3"
                   value="{{ old('currency', $price->currency ?? 'INR') }}"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            @error('currency')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Valid from (optional)</label>
            <input type="date" name="valid_from"
                   value="{{ old('valid_from', $price->valid_from ?? '') }}"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            @error('valid_from')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] text-gray-600 dark:text-gray-300 mb-1">Valid to (optional)</label>
            <input type="date" name="valid_to"
                   value="{{ old('valid_to', $price->valid_to ?? '') }}"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]">
            @error('valid_to')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $price->is_active ?? true))>
        <span>Active</span>
    </label>
</div>

<script>
(function () {
    const productSelect = document.getElementById('b2b-price-product');
    const variantSelect = document.getElementById('b2b-price-variant');
    const sellUnitSelect = document.getElementById('b2b-price-sell-unit');
    if (!productSelect || !variantSelect) return;

    const urlTemplate = variantSelect.dataset.urlTemplate;
    const selectedVariantId = (variantSelect.dataset.selected || '').toString();

    function filterSellUnits(productId) {
        if (!sellUnitSelect) return;
        Array.from(sellUnitSelect.options).forEach(opt => {
            if (!opt.value) { opt.hidden = false; return; }
            opt.hidden = productId && opt.dataset.productId !== productId.toString();
        });

        if (sellUnitSelect.value) {
            const selected = sellUnitSelect.options[sellUnitSelect.selectedIndex];
            if (selected && selected.hidden) sellUnitSelect.value = '';
        }
    }

    function setVariantOptions(options, selectedId) {
        // Keep first option as "Product-level"
        variantSelect.innerHTML = '<option value="">Product-level (no variant)</option>';

        (options || []).forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = v.label || ('Variant #' + v.id);
            variantSelect.appendChild(opt);
        });

        if (selectedId) {
            variantSelect.value = selectedId;
        }
    }

    async function loadVariants(productId, selectedId) {
        if (!productId) {
            setVariantOptions([], '');
            variantSelect.disabled = true;
            return;
        }

        variantSelect.disabled = true;
        setVariantOptions([], selectedId);

        const url = urlTemplate.replace('__PRODUCT__', productId);

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Failed to load variants');
            const data = await res.json();

            if (!data || !data.ok) throw new Error('Failed to load variants');

            setVariantOptions(data.variants || [], selectedId);
        } catch (e) {
            // still allow product-level pricing even if variant load fails
            setVariantOptions([], '');
        } finally {
            variantSelect.disabled = false;
        }
    }

    productSelect.addEventListener('change', function () {
        filterSellUnits(productSelect.value);
        loadVariants(productSelect.value, '');
    });

    // Initial load for edit/old input
    const initialProductId = productSelect.value;
    filterSellUnits(initialProductId);
    loadVariants(initialProductId, selectedVariantId);
})();
</script>

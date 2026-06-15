@php
    $selectedVariantIds = collect(old('variant_ids', $selectedVariantIds ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();

    $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
    $factor = 1 + ($gstRate / 100);

    $b2cIncludesGst = (bool) old('b2c_price_includes_gst', $sellUnit->b2c_price_includes_gst ?? true);

    $basePriceInput = old('base_price');
    if ($basePriceInput === null) {
        $stored = $sellUnit->base_price ?? null;
        $basePriceInput = $stored === null || $stored === ''
            ? ''
            : ($b2cIncludesGst && $factor > 0 ? round((float) $stored * $factor, 2) : round((float) $stored, 2));
    }

    $mrpInput = old('mrp_price');
    if ($mrpInput === null) {
        $stored = $sellUnit->mrp_price ?? null;
        $mrpInput = $stored === null || $stored === ''
            ? ''
            : ($b2cIncludesGst && $factor > 0 ? round((float) $stored * $factor, 2) : round((float) $stored, 2));
    }

    $b2bIncludesGst = (bool) ($product->b2b_price_includes_gst ?? false);
    $standardB2BInput = old('standard_b2b_price');
    if ($standardB2BInput === null) {
        $stored = $sellUnit->standard_b2b_price ?? null;
        $standardB2BInput = $stored === null || $stored === ''
            ? ''
            : ($b2bIncludesGst && $factor > 0 ? round((float) $stored * $factor, 2) : round((float) $stored, 2));
    }

    $saleType = old('sale_type', $sellUnit->sale_type ?? 'fixed_piece_pack');
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
        <div class="font-semibold">Please fix the following:</div>
        <ul class="mt-1 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-5" data-sell-unit-form>
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Sellable format</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    This is the admin/inventory pack or selling format: 500g sliced pack, 1kg slab, 10pc dimsum pack, B2B box, etc. It does not create product variants or change the storefront display.
                </p>
            </div>
            <div class="rounded-lg bg-gray-50 px-3 py-2 text-[11px] text-gray-600 dark:bg-gray-900 dark:text-gray-300">
                GST from product HSN: {{ rtrim(rtrim(number_format($gstRate, 2), '0'), '.') }}%
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Format name</label>
                <input type="text" name="name" value="{{ old('name', $sellUnit->name) }}" required
                       placeholder="Examples: 500g Slice Pack, 10pc Pack, 1kg Slab"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Sort order</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $sellUnit->sort_order ?? 0) }}"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Format SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $sellUnit->sku) }}"
                       placeholder="Optional; generated if blank"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Barcode</label>
                <input type="text" name="barcode" value="{{ old('barcode', $sellUnit->barcode) }}"
                       placeholder="Optional"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">How this is sold</label>
                <select name="sale_type" required data-sale-type
                        class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                    <option value="fixed_weight_pack" @selected($saleType === 'fixed_weight_pack')>Fixed weight pack</option>
                    <option value="fixed_piece_pack" @selected($saleType === 'fixed_piece_pack')>Fixed piece pack</option>
                    <option value="variable_weight" @selected($saleType === 'variable_weight')>Variable-weight prepack / by kg</option>
                </select>
            </div>
        </div>

        <input type="hidden" name="unit_type" data-unit-type value="{{ old('unit_type', $sellUnit->unit_type ?? 'pack') }}">
        <input type="hidden" name="pricing_unit" data-pricing-unit value="{{ old('pricing_unit', $sellUnit->pricing_unit ?? 'pack') }}">

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div data-fixed-piece-fields>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Pieces per pack</label>
                <input type="number" step="0.001" min="0" name="pieces_per_unit" value="{{ old('pieces_per_unit', $sellUnit->pieces_per_unit) }}"
                       placeholder="Example: 10 for dimsum 10pc pack"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Used for dimsum, kebabs, rolls, and other piece-count packs.</p>
            </div>

            <div data-weight-fields>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Pack weight / billing step (kg)</label>
                <input type="number" step="0.001" min="0" name="weight_per_unit_kg" value="{{ old('weight_per_unit_kg', $sellUnit->weight_per_unit_kg) }}"
                       placeholder="Example: 0.500 for 500g pack, or average/target pack weight for by-kg stock"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Used for fixed-weight packs and variable-weight prepacked stock such as whole/slab salmon or pork sold by kg.</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Prices</h2>
        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
            Enter MRP and retail selling price for this format. Values are stored GST-exclusive internally, same as the product screen.
        </p>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">MRP {{ $b2cIncludesGst ? '(incl GST)' : '(excl GST)' }}</label>
                <input type="number" step="0.01" min="0" name="mrp_price" value="{{ $mrpInput }}"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Retail selling price {{ $b2cIncludesGst ? '(incl GST)' : '(excl GST)' }}</label>
                <input type="number" step="0.01" min="0" name="base_price" value="{{ $basePriceInput }}"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="b2c_price_includes_gst" value="0">
                <input type="checkbox" name="b2c_price_includes_gst" value="1" @checked($b2cIncludesGst) class="mt-0.5 rounded border-gray-300">
                <span>
                    <span class="block font-medium text-gray-800 dark:text-gray-100">Retail prices include GST</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Recommended for MRP-style pack pricing.</span>
                </span>
            </label>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Standard B2B price {{ $b2bIncludesGst ? '(incl GST)' : '(excl GST)' }}</label>
                <input type="number" step="0.01" min="0" name="standard_b2b_price" value="{{ $standardB2BInput }}"
                       placeholder="Optional"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Standard B2B MOQ</label>
                <input type="number" step="0.001" min="0.001" name="standard_b2b_min_order_quantity" value="{{ old('standard_b2b_min_order_quantity', $sellUnit->standard_b2b_min_order_quantity) }}"
                       placeholder="Default 1 if empty"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Visibility</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-3">
            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $sellUnit->exists ? $sellUnit->is_active : true)) class="mt-0.5 rounded border-gray-300">
                <span><span class="block font-medium text-gray-800 dark:text-gray-100">Active</span><span class="text-[10px] text-gray-500 dark:text-gray-400">Can receive/repack stock.</span></span>
            </label>

            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="is_retail_visible" value="0">
                <input type="checkbox" name="is_retail_visible" value="1" @checked(old('is_retail_visible', $sellUnit->exists ? $sellUnit->is_retail_visible : true)) class="mt-0.5 rounded border-gray-300">
                <span><span class="block font-medium text-gray-800 dark:text-gray-100">B2C visible</span><span class="text-[10px] text-gray-500 dark:text-gray-400">Normal customers can buy this format.</span></span>
            </label>

            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="is_b2b_visible" value="0">
                <input type="checkbox" name="is_b2b_visible" value="1" @checked(old('is_b2b_visible', $sellUnit->exists ? $sellUnit->is_b2b_visible : true)) class="mt-0.5 rounded border-gray-300">
                <span><span class="block font-medium text-gray-800 dark:text-gray-100">B2B visible</span><span class="text-[10px] text-gray-500 dark:text-gray-400">Restaurants/wholesale customers can buy this format.</span></span>
            </label>
        </div>
    </div>

    @if(($variants ?? collect())->isNotEmpty())
        <details class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
            <summary class="cursor-pointer text-sm font-semibold text-gray-900 dark:text-gray-50">Advanced: link an existing legacy variant</summary>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Usually leave this alone. Use this only if an older manually-created variant must be associated for reporting.
            </p>
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach($variants as $variant)
                    <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                        <input type="checkbox" name="variant_ids[]" value="{{ $variant->id }}" @checked(in_array((int) $variant->id, $selectedVariantIds, true)) class="mt-0.5 rounded border-gray-300">
                        <span>
                            <span class="block font-medium text-gray-800 dark:text-gray-100">{{ $variant->name ?: ($variant->sku ?: 'Option #' . $variant->id) }}</span>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400">SKU: {{ $variant->sku ?: '—' }} · Price: ₹{{ number_format((float) ($variant->price ?? 0), 2) }} · Stock: {{ $variant->stock_quantity ?? 0 }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </details>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Internal notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900" placeholder="Internal notes, vendor pack details, or B2B usage notes.">{{ old('notes', $sellUnit->notes) }}</textarea>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ route('admin.products.sell-units.index', $product) }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Cancel</a>
        <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Save sellable format</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-sell-unit-form]');
    if (!form) return;

    const saleType = form.querySelector('[data-sale-type]');
    const unitType = form.querySelector('[data-unit-type]');
    const pricingUnit = form.querySelector('[data-pricing-unit]');
    const pieceFields = form.querySelector('[data-fixed-piece-fields]');
    const weightFields = form.querySelector('[data-weight-fields]');

    const sync = () => {
        const type = saleType.value || 'fixed_piece_pack';
        const isPiece = type === 'fixed_piece_pack';
        const isVariableWeight = type === 'variable_weight';
        pieceFields.style.display = isPiece ? '' : 'none';
        weightFields.style.display = isPiece ? 'none' : '';
        unitType.value = isVariableWeight ? 'kg' : 'pack';
        pricingUnit.value = isVariableWeight ? 'kg' : 'pack';
    };

    saleType.addEventListener('change', sync);
    sync();
});
</script>

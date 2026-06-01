@php
    $unitTypes = [
        'piece' => 'Piece',
        'pack' => 'Pack',
        'box' => 'Box',
        'kg' => 'Kg',
        'request_piece' => 'Request by pieces',
        'request_weight' => 'Request by weight',
    ];

    $pricingUnits = [
        'unit' => 'Per unit',
        'piece' => 'Per piece',
        'pack' => 'Per pack',
        'box' => 'Per box',
        'kg' => 'Per kg',
    ];

    $selectedVariantIds = collect(old('variant_ids', $selectedVariantIds ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();


    $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
    $factor = 1 + ($gstRate / 100);
    $b2bIncludesGst = (bool) ($product->b2b_price_includes_gst ?? false);
    $standardB2BInput = old('standard_b2b_price');
    if ($standardB2BInput === null) {
        $storedB2B = $sellUnit->standard_b2b_price ?? null;
        $standardB2BInput = $storedB2B === null || $storedB2B === ''
            ? ''
            : ($b2bIncludesGst && $factor > 0 ? round((float) $storedB2B * $factor, 2) : round((float) $storedB2B, 2));
    }
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

<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @isset($method)
        @method($method)
    @endisset

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Sellable unit</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Define how this product can be sold, such as a 10pc pack, 20pc pack, box, or kg-based request. This is a foundation layer and does not change existing B2C checkout behaviour by itself.
                </p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Name</label>
                <input type="text" name="name" value="{{ old('name', $sellUnit->name) }}" required
                       placeholder="20pc Pack"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $sellUnit->sku) }}"
                       placeholder="Optional pack/unit SKU"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Barcode</label>
                <input type="text" name="barcode" value="{{ old('barcode', $sellUnit->barcode) }}"
                       placeholder="Optional barcode"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Sort order</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $sellUnit->sort_order ?? 0) }}"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Unit type</label>
                <select name="unit_type" required class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                    @foreach($unitTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('unit_type', $sellUnit->unit_type ?? 'pack') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Pricing unit</label>
                <select name="pricing_unit" required class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                    @foreach($pricingUnits as $value => $label)
                        <option value="{{ $value }}" @selected(old('pricing_unit', $sellUnit->pricing_unit ?? 'pack') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Pieces per unit</label>
                <input type="number" step="0.001" min="0" name="pieces_per_unit" value="{{ old('pieces_per_unit', $sellUnit->pieces_per_unit) }}"
                       placeholder="Example: 20"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Use this for packs/boxes such as 10pc, 20pc, or 100pc box.</p>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Weight per unit kg</label>
                <input type="number" step="0.001" min="0" name="weight_per_unit_kg" value="{{ old('weight_per_unit_kg', $sellUnit->weight_per_unit_kg) }}"
                       placeholder="Optional fixed pack weight"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Standard B2B price (₹, excl GST by default)</label>
                <input type="number" step="0.01" min="0" name="standard_b2b_price" value="{{ $standardB2BInput }}"
                       placeholder="Default B2B price for this pack/unit"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Customer-specific prices override this value. B2B entry is GST-exclusive by default.</p>
                @error('standard_b2b_price') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Standard B2B MOQ</label>
                <input type="number" step="0.001" min="0.001" name="standard_b2b_min_order_quantity" value="{{ old('standard_b2b_min_order_quantity', $sellUnit->standard_b2b_min_order_quantity) }}"
                       placeholder="Default 1 if empty"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                @error('standard_b2b_min_order_quantity') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $sellUnit->exists ? $sellUnit->is_active : true)) class="mt-0.5 rounded border-gray-300">
                <span>
                    <span class="block font-medium text-gray-800 dark:text-gray-100">Active</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Available for channel rules when wired.</span>
                </span>
            </label>

            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="is_retail_visible" value="0">
                <input type="checkbox" name="is_retail_visible" value="1" @checked(old('is_retail_visible', $sellUnit->exists ? $sellUnit->is_retail_visible : true)) class="mt-0.5 rounded border-gray-300">
                <span>
                    <span class="block font-medium text-gray-800 dark:text-gray-100">Retail/B2C unit</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Mark if this unit can be used by normal shop later.</span>
                </span>
            </label>

            <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                <input type="hidden" name="is_b2b_visible" value="0">
                <input type="checkbox" name="is_b2b_visible" value="1" @checked(old('is_b2b_visible', $sellUnit->exists ? $sellUnit->is_b2b_visible : true)) class="mt-0.5 rounded border-gray-300">
                <span>
                    <span class="block font-medium text-gray-800 dark:text-gray-100">B2B unit</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">Mark if this option may carry B2B pricing/MOQ terms.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Link existing variants</h2>
        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
            Optional. Link current B2C variants like “10pc pack” or “20pc pack” to this sellable unit. This does not change how B2C customers currently buy those variants.
        </p>

        <div class="mt-3 grid gap-2 md:grid-cols-2">
            @forelse($variants as $variant)
                <label class="flex items-start gap-2 rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-800">
                    <input type="checkbox" name="variant_ids[]" value="{{ $variant->id }}" @checked(in_array((int) $variant->id, $selectedVariantIds, true)) class="mt-0.5 rounded border-gray-300">
                    <span>
                        <span class="block font-medium text-gray-800 dark:text-gray-100">{{ $variant->name ?: ($variant->sku ?: 'Variant #' . $variant->id) }}</span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400">
                            SKU: {{ $variant->sku ?: '—' }} · Price: ₹{{ number_format((float) ($variant->price ?? 0), 2) }} · Stock: {{ $variant->stock_quantity ?? 0 }}
                        </span>
                    </span>
                </label>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-4 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No variants exist for this product yet. You can create the sellable unit now and link variants later.
                </div>
            @endforelse
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950">
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900" placeholder="Internal notes, vendor pack details, or B2B usage notes.">{{ old('notes', $sellUnit->notes) }}</textarea>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ route('admin.products.sell-units.index', $product) }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
            Cancel
        </a>
        <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
            Save sellable unit
        </button>
    </div>
</form>

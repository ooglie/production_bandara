@php
    /** @var \App\Models\ProductVariant|null $variant */
    $isEdit = isset($variant);

    $defaultPricingUnit = old(
        'pricing_unit',
        $variant->pricing_unit ?? (($product->sell_unit ?? 'piece') === 'kg' ? 'kg' : 'pack')
    );

    $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
    $factor = 1 + ($gstRate / 100);
    $b2cIncludesGst = (bool) ($product->b2c_price_includes_gst ?? true);
    $b2bIncludesGst = (bool) ($product->b2b_price_includes_gst ?? false);

    $variantPriceInput = old('price');
    if ($variantPriceInput === null) {
        $stored = (float) ($variant->price ?? $product->base_price ?? 0);
        $variantPriceInput = $b2cIncludesGst && $factor > 0 ? round($stored * $factor, 2) : round($stored, 2);
    }

    $variantB2BPriceInput = old('standard_b2b_price');
    if ($variantB2BPriceInput === null) {
        $storedB2B = $variant->standard_b2b_price ?? null;
        $variantB2BPriceInput = $storedB2B === null || $storedB2B === ''
            ? ''
            : ($b2bIncludesGst && $factor > 0 ? round((float) $storedB2B * $factor, 2) : round((float) $storedB2B, 2));
    }
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="space-y-5">
        @if(session('status'))
            <div class="rounded-sm border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    SKU
                </label>
                <input
                    type="text"
                    name="sku"
                    value="{{ old('sku', $variant->sku ?? '') }}"
                    required
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('sku')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Name / Label (optional)
                </label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $variant->name ?? '') }}"
                    placeholder="e.g. 125 gms"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('name')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    B2C variant price (₹)
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="price"
                    value="{{ $variantPriceInput }}"
                    required
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('price')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Standard B2B price (₹, excl GST by default)
                </label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="standard_b2b_price"
                    value="{{ $variantB2BPriceInput }}"
                    placeholder="Optional B2B price"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('standard_b2b_price')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Standard B2B MOQ
                </label>
                <input
                    type="number"
                    step="0.001"
                    min="0"
                    name="standard_b2b_min_order_quantity"
                    value="{{ old('standard_b2b_min_order_quantity', $variant->standard_b2b_min_order_quantity ?? '') }}"
                    placeholder="Optional"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('standard_b2b_min_order_quantity')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Variant weight (kg)
                </label>
                <input
                    type="number"
                    step="0.001"
                    min="0"
                    name="product_weight"
                    value="{{ old('product_weight', $variant->product_weight ?? '') }}"
                    required
                    placeholder="e.g. 0.125"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    Enter in kg. Example: 0.125 = 125 gms, 0.250 = 250 gms, 1.000 = 1 kg.
                </p>
                @error('product_weight')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Pricing unit
                </label>
                <select
                    name="pricing_unit"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="pack" @selected($defaultPricingUnit === 'pack')>Pack</option>
                    <option value="kg" @selected($defaultPricingUnit === 'kg')>Kg</option>
                </select>
                @error('pricing_unit')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Stock qty
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="stock_quantity"
                    value="{{ old('stock_quantity', $variant->stock_quantity ?? '') }}"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('stock_quantity')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Low stock threshold
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="low_stock_threshold"
                    value="{{ old('low_stock_threshold', $variant->low_stock_threshold ?? '') }}"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('low_stock_threshold')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Min order qty
                </label>
                <input
                    type="number"
                    step="0.01"
                    name="min_order_quantity"
                    value="{{ old('min_order_quantity', $variant->min_order_quantity ?? '') }}"
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                @error('min_order_quantity')
                    <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-4 text-xs">
            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="manage_stock" value="0">
                <input
                    type="checkbox"
                    name="manage_stock"
                    value="1"
                    @checked(old('manage_stock', $variant->manage_stock ?? true))
                >
                <span>Manage stock</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', $variant->is_active ?? true))
                >
                <span>Active</span>
            </label>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                Attribute values for this variant
            </label>

            @if($attributeValuesByAttribute->isEmpty())
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    No attributes configured for this product.
                    Set attribute values on the product first.
                </p>
            @else
                <div class="space-y-3 text-xs">
                    @foreach($attributeValuesByAttribute as $attributeId => $values)
                        @php
                            $attribute = $values->first()->attribute;
                            $name = $attribute->display_name ?? $attribute->name;
                            $selectedValueId = old("variant_attributes.$attributeId", $existingVariantAttributes[$attributeId] ?? null);
                        @endphp

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $name }}
                            </label>
                            <select
                                name="variant_attributes[{{ $attributeId }}]"
                                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                            >
                                <option value="">— Not set —</option>
                                @foreach($values->sortBy('position') as $value)
                                    <option value="{{ $value->id }}" @selected((int)$selectedValueId === (int)$value->id)>
                                        {{ $value->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            @endif

            @error('variant_attributes')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
            @error('variant_attributes.*')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                {{ $isEdit ? 'Update variant' : 'Create variant' }}
            </button>

            <a href="{{ route('admin.products.variants.index', $product) }}"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>
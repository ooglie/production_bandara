@php
    /** @var \App\Models\User $user */
    /** @var \App\Models\B2BCustomerProduct|null $row */
    $isEdit = isset($row);
    $priceValue = old('price', isset($priceOverride) ? $priceOverride?->price : null);
@endphp

<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
    @unless($isEdit)
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Product / sellable unit
            </label>
            <select name="assignment_target"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                    required>
                <option value="">Select product or sellable unit…</option>
                @foreach($products as $p)
                    <optgroup label="{{ $p->name }}@if($p->sku) ({{ $p->sku }})@endif">
                        <option value="product:{{ $p->id }}" @selected(old('assignment_target') === 'product:' . $p->id)>
                            Product-level access
                        </option>
                        @foreach($p->sellUnits as $unit)
                            <option value="unit:{{ $unit->id }}" @selected(old('assignment_target') === 'unit:' . $unit->id)>
                                {{ $unit->display_label }} @if($unit->sku) · {{ $unit->sku }} @endif
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="mt-1 text-[10px] text-gray-400">
                Use product-level access for legacy/simple B2B terms. Use sellable units for pack/box-specific MOQ and pricing.
            </p>
            @error('assignment_target')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div class="rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Product:</span>
                <span class="font-medium">{{ $row->product?->name ?? ('Product #' . $row->product_id) }}</span>
            </div>
            <div class="mt-0.5">
                <span class="text-gray-500 dark:text-gray-400">Sellable unit:</span>
                <span class="font-medium">{{ $row->sellUnit?->display_label ?? 'Product-level access' }}</span>
            </div>
        </div>
    @endunless

    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                MOQ
            </label>
            <input type="number"
                   step="0.01"
                   min="0.01"
                   name="min_order_quantity"
                   value="{{ old('min_order_quantity', $row->min_order_quantity ?? 1) }}"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            <p class="mt-1 text-[10px] text-gray-400">
                Applies to this product option only. Default is 1.
            </p>
            @error('min_order_quantity')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                B2B price optional
            </label>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="price"
                   value="{{ $priceValue }}"
                   placeholder="Leave blank to keep existing/fallback"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            <p class="mt-1 text-[10px] text-gray-400">
                Saved as a customer-specific product/sell-unit price.
            </p>
            @error('price')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2 pt-5">
            <input type="checkbox"
                   id="is_active"
                   name="is_active"
                   value="1"
                   @checked(old('is_active', $row->is_active ?? true))>
            <label for="is_active" class="text-[11px] text-gray-700 dark:text-gray-200">
                Active (visible to customer)
            </label>
        </div>
    </div>
</div>

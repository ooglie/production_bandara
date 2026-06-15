{{-- resources/views/admin/products/_form.blade.php --}}
@php
    /** @var \App\Models\Product|null $product */

    $isEdit = isset($product) && $product instanceof \App\Models\Product && $product->exists;

    $vendors    = $vendors ?? collect();
    $hsnCodes   = $hsnCodes ?? collect();
    $countries  = $countries ?? collect();
    $categories = $categories ?? collect();
    $attributes = $attributes ?? collect();

    $selectedCategoryIds       = $selectedCategoryIds ?? [];
    $selectedAttributeValueIds = $selectedAttributeValueIds ?? [];

    $oldB2CIncludes = old('b2c_price_includes_gst', $product->b2c_price_includes_gst ?? true);
    $b2cIncludesGst = (int) $oldB2CIncludes === 1 || $oldB2CIncludes === true || $oldB2CIncludes === '1';

    $oldB2BIncludes = old('b2b_price_includes_gst', $product->b2b_price_includes_gst ?? false);
    $b2bIncludesGst = (int) $oldB2BIncludes === 1 || $oldB2BIncludes === true || $oldB2BIncludes === '1';

    $specialAudience = old('special_audience', $product->special_audience ?? 'b2c');

    $selectedHsnId    = old('hsn_code_id', $product->hsn_code_id ?? '');
    $selectedHsnIdInt = $selectedHsnId !== '' ? (int) $selectedHsnId : null;
    $selectedHsn      = $selectedHsnIdInt ? $hsnCodes->firstWhere('id', $selectedHsnIdInt) : null;

    $gstRate = (float) old('gst_rate', $selectedHsn?->gst_rate ?? ($product->gst_rate ?? 5));
    $divisor = 1 + ($gstRate / 100);

    // SELL PRICE (admin input) - stored in DB as base_price EXCL GST
    $sellInput = old('base_price');
    if ($sellInput === null) {
        $storedSellExcl = (float) ($product->base_price ?? 0);
        if ($b2cIncludesGst) {
            $sellInput = $divisor > 0 ? round($storedSellExcl * $divisor, 2) : round($storedSellExcl, 2);
        } else {
            $sellInput = round($storedSellExcl, 2);
        }
    }

    // MRP (admin input) - stored in DB as mrp_price EXCL GST
    $mrpInput = old('mrp_price');
    if ($mrpInput === null) {
        $storedMrpExcl = (float) ($product->mrp_price ?? 0);

        if (($product->mrp_price ?? null) === null) {
            $mrpInput = '';
        } else {
            if ($b2cIncludesGst) {
                $mrpInput = $divisor > 0 ? round($storedMrpExcl * $divisor, 2) : round($storedMrpExcl, 2);
            } else {
                $mrpInput = round($storedMrpExcl, 2);
            }
        }
    }

    // Standard B2B price (admin input) - stored in DB as EXCL GST
    $standardB2BInput = old('standard_b2b_price');
    if ($standardB2BInput === null) {
        $storedB2BExcl = $product->standard_b2b_price ?? null;
        if ($storedB2BExcl === null || $storedB2BExcl === '') {
            $standardB2BInput = '';
        } else {
            $storedB2BExcl = (float) $storedB2BExcl;
            $standardB2BInput = $b2bIncludesGst && $divisor > 0
                ? round($storedB2BExcl * $divisor, 2)
                : round($storedB2BExcl, 2);
        }
    }

    // Special price - B2C/all specials follow B2C price mode; B2B specials follow B2B price mode.
    $specialInput = old('special_price');
    if ($specialInput === null) {
        $storedSpecialExcl = $product->special_price ?? null;
        if ($storedSpecialExcl === null || $storedSpecialExcl === '') {
            $specialInput = '';
        } else {
            $specialIncludesGst = $specialAudience === 'b2b' ? $b2bIncludesGst : $b2cIncludesGst;
            $storedSpecialExcl = (float) $storedSpecialExcl;
            $specialInput = $specialIncludesGst && $divisor > 0
                ? round($storedSpecialExcl * $divisor, 2)
                : round($storedSpecialExcl, 2);
        }
    }

    $weight = old('product_weight', $product->product_weight ?? '');
    $piecesPerPack = old('pieces_per_pack', $product->pieces_per_pack ?? '');
    $inventoryRole = old('inventory_role', $product->inventory_role ?? (($product->is_active ?? true) ? 'saleable' : 'internal'));
    $packType = old('pack_type', $product->pack_type ?? 'quantity');

    $hsnManageUrl = \Illuminate\Support\Facades\Route::has('admin.hsn-codes.index')
        ? route('admin.hsn-codes.index')
        : null;

    $hsnCreateUrl = \Illuminate\Support\Facades\Route::has('admin.hsn-codes.create')
        ? route('admin.hsn-codes.create')
        : null;

    $selectedCountry = old('country_of_origin', $product->country_of_origin ?? '');

    $card  = "rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900";
    $input = "mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-600";
    $select = $input;
    $hint  = "mt-1 text-[11px] text-gray-500 dark:text-gray-400";

    $oldCategoryIds = collect(old('category_ids', $selectedCategoryIds ?? []))
        ->map(fn($v) => (int) $v)
        ->toArray();

    $oldAttributeValueIds = collect(old('attribute_value_ids', $selectedAttributeValueIds ?? []))
        ->map(fn($v) => (int) $v)
        ->toArray();
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4" data-product-form>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if(session('status'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-[12px] text-red-800">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- REQUIRED PRODUCT SETUP --}}
    <section class="{{ $card }}">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                        Product setup
                    </h2>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Save inactive drafts while price and weight are still pending; active products still require complete commercial data.
                    </p>
                </div>

                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    Fields marked <span class="text-red-500">*</span> are always required. Price and weight are required before activation.
                </div>
            </div>
        </div>

        <div class="px-5 py-5 space-y-5">
            {{-- Core identity --}}
            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name ?? '') }}"
                        class="{{ $input }}"
                        required
                        placeholder="Product name"
                    >
                    @error('name') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        SKU <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="sku"
                        value="{{ old('sku', $product->sku ?? '') }}"
                        class="{{ $input }}"
                        required
                        placeholder="Unique SKU"
                    >
                    @error('sku') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-3">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Short description <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="short_description"
                        value="{{ old('short_description', $product->short_description ?? '') }}"
                        class="{{ $input }}"
                        maxlength="255"
                        required
                        placeholder="Short customer-facing summary"
                    >
                    <div class="{{ $hint }}">Keep this concise. It appears in product cards and summary sections.</div>
                    @error('short_description') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-3">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Full description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="description"
                        rows="6"
                        class="{{ $input }}"
                        required
                        placeholder="Detailed product description"
                    >{{ old('description', $product->description ?? '') }}</textarea>
                    <div class="{{ $hint }}">Use this for the full product page description.</div>
                    @error('description') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-3 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                            Storage guidance
                        </label>
                        <textarea
                            name="storage_guidance"
                            rows="5"
                            class="{{ $input }}"
                            placeholder="One guidance point per line"
                        >{{ old('storage_guidance', $product->storage_guidance ?? implode("\n", \App\Models\Product::DEFAULT_STORAGE_GUIDANCE)) }}</textarea>
                        <div class="{{ $hint }}">Shown in the product page Storage &amp; Delivery tab. Add one bullet point per line.</div>
                        @error('storage_guidance') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                            Delivery &amp; support
                        </label>
                        <textarea
                            name="delivery_support"
                            rows="5"
                            class="{{ $input }}"
                            placeholder="One delivery/support point per line"
                        >{{ old('delivery_support', $product->delivery_support ?? implode("\n", \App\Models\Product::DEFAULT_DELIVERY_SUPPORT)) }}</textarea>
                        <div class="{{ $hint }}">Shown in the product page Storage &amp; Delivery tab. Add one bullet point per line.</div>
                        @error('delivery_support') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

            </div>

            {{-- Type + HSN + Country --}}
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select name="type" class="{{ $select }}" required>
                        <option value="simple" @selected(old('type', $product->type ?? 'simple') === 'simple')>Simple</option>
                        <option value="variable" @selected(old('type', $product->type ?? 'simple') === 'variable')>Variable pack product</option>
                    </select>
                    <p class="{{ $hint }}">Use variable pack product for Dimsum 10 pcs / 20 pcs style options. Use simple for slabs, single packs, and normal products.</p>
                    @error('type') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        HSN Code <span class="text-red-500">*</span>
                    </label>
                    @if($hsnCodes->isEmpty())
                        <div class="mt-1 rounded-sm border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-3 py-3 text-[12px] text-gray-500 dark:text-gray-400">
                            No HSN codes yet.
                            @if($hsnCreateUrl)
                                <a href="{{ $hsnCreateUrl }}" class="underline">Create one</a>
                            @endif
                        </div>
                    @else
                        <select id="hsn_code_id" name="hsn_code_id" class="{{ $select }}" required>
                            <option value="" data-rate="">Select HSN…</option>
                            @foreach($hsnCodes as $hsn)
                                <option value="{{ $hsn->id }}"
                                        data-rate="{{ (float) $hsn->gst_rate }}"
                                        @selected((int) old('hsn_code_id', $product->hsn_code_id ?? 0) === (int) $hsn->id)>
                                    {{ $hsn->code }}
                                    @if($hsn->name) — {{ $hsn->name }} @endif
                                    ({{ number_format((float)$hsn->gst_rate, 2) }}% GST)
                                </option>
                            @endforeach
                        </select>
                    @endif
                    <div class="{{ $hint }}">
                        @if($hsnManageUrl)
                            <a href="{{ $hsnManageUrl }}" class="underline">Manage HSN codes</a>
                        @endif
                    </div>
                    @error('hsn_code_id') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Country of origin <span class="text-red-500">*</span>
                    </label>
                    <select name="country_of_origin" class="{{ $select }}" required>
                        <option value="">Select country…</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->code }}" @selected($selectedCountry === $c->code)>
                                {{ country_flag_emoji($c->code) }} {{ $c->name }} ({{ $c->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('country_of_origin') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Pricing --}}
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        MRP (₹) <span class="text-red-500">*</span> <span class="text-[10px] font-normal text-gray-400">when active</span>
                    </label>
                    <input
                        id="mrp_input"
                        type="number"
                        step="0.01"
                        min="0"
                        name="mrp_price"
                        value="{{ $mrpInput }}"
                        class="{{ $input }}"
                        placeholder="Maximum retail price"
                    >
                    <div class="{{ $hint }}">Leave blank only while the product is an inactive draft.</div>
                    @error('mrp_price') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Sell price (₹) <span class="text-red-500">*</span> <span class="text-[10px] font-normal text-gray-400">when active</span>
                    </label>
                    <input
                        id="price_input"
                        type="number"
                        step="0.01"
                        min="0"
                        name="base_price"
                        value="{{ $sellInput }}"
                        class="{{ $input }}"
                        placeholder="Actual selling price"
                    >
                    <div class="{{ $hint }}">Leave blank only while the product is an inactive draft.</div>
                    @error('base_price') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        B2C price mode <span class="text-red-500">*</span>
                    </label>
                    <select id="b2c_price_includes_gst" name="b2c_price_includes_gst" class="{{ $select }}" required>
                        <option value="1" @selected($b2cIncludesGst)>With GST (inclusive)</option>
                        <option value="0" @selected(!$b2cIncludesGst)>Without GST (exclusive)</option>
                    </select>
                    <div class="{{ $hint }}">Default for retail/B2C is GST-inclusive.</div>
                    @error('b2c_price_includes_gst') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Product / pack weight (kg) <span class="text-red-500">*</span> <span class="text-[10px] font-normal text-gray-400">when needed</span>
                    </label>
                    <input
                        type="number"
                        name="product_weight"
                        step="0.001"
                        min="0"
                        value="{{ $weight }}"
                        class="{{ $input }}"
                        placeholder="e.g. 0.500 for 500g pack"
                    >
                    <div class="{{ $hint }}">For fixed-weight packs, enter the pack weight. For by-kg products, this can be the target/average pack weight.</div>
                    @error('product_weight') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pack / sale type</label>
                    <select name="pack_type" class="{{ $select }}">
                        <option value="quantity" @selected($packType === 'quantity')>Quantity item</option>
                        <option value="bulk" @selected($packType === 'bulk')>Bulk/internal stock</option>
                        <option value="fixed_weight_pack" @selected($packType === 'fixed_weight_pack')>Fixed weight pack</option>
                        <option value="fixed_piece_pack" @selected($packType === 'fixed_piece_pack')>Fixed piece pack</option>
                        <option value="variable_weight" @selected($packType === 'variable_weight')>Variable-weight prepacked / by kg</option>
                    </select>
                    <div class="{{ $hint }}">This is used by vendor inward and create pack stock; the frontend remains product-based.</div>
                    @error('pack_type') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pieces per pack</label>
                    <input type="number" name="pieces_per_pack" step="0.001" min="0" value="{{ $piecesPerPack }}" class="{{ $input }}" placeholder="e.g. 10 for dimsum">
                    <div class="{{ $hint }}">Use for dimsum, kebabs, rolls and other fixed piece packs.</div>
                    @error('pieces_per_pack') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-600 dark:text-gray-300">
                    <div class="font-semibold text-gray-900 dark:text-gray-50">Simple rule</div>
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">If the customer buys it, create it as a product. Use inactive products only for raw/inward stock.</div>
                </div>
            </div>

            {{-- Standard B2B pricing --}}
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Standard B2B price (₹)</label>
                    <input type="number" step="0.01" min="0" name="standard_b2b_price"
                           value="{{ $standardB2BInput }}"
                           class="{{ $input }}" placeholder="Leave empty if not available for B2B by default">
                    <div class="{{ $hint }}">Customer-specific B2B prices override this. Default B2B entry is excl. GST.</div>
                    @error('standard_b2b_price') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">B2B price mode <span class="text-red-500">*</span></label>
                    <select id="b2b_price_includes_gst" name="b2b_price_includes_gst" class="{{ $select }}" required>
                        <option value="0" @selected(!$b2bIncludesGst)>Without GST (exclusive)</option>
                        <option value="1" @selected($b2bIncludesGst)>With GST (inclusive)</option>
                    </select>
                    <div class="{{ $hint }}">Default for B2B/account prices is GST-exclusive.</div>
                    @error('b2b_price_includes_gst') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Standard B2B MOQ</label>
                    <input type="number" step="0.001" min="0.001" name="standard_b2b_min_order_quantity"
                           value="{{ old('standard_b2b_min_order_quantity', $product->standard_b2b_min_order_quantity ?? '') }}"
                           class="{{ $input }}" placeholder="Default 1 if empty">
                    <div class="{{ $hint }}">Customer/sellable-unit MOQ overrides this when configured.</div>
                    @error('standard_b2b_min_order_quantity') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Pricing helper --}}
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="text-[10px] uppercase tracking-wide text-gray-400">GST %</div>
                    <input
                        id="gst_rate"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        name="gst_rate"
                        value="{{ number_format($gstRate, 2, '.', '') }}"
                        class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px] text-gray-900 dark:text-gray-100"
                    >
                    <div class="mt-1 text-[10px] text-gray-400">Auto-filled from HSN.</div>
                    @error('gst_rate') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="text-gray-600 dark:text-gray-300">Base price (excl. GST)</span>
                        <span id="calc_base" class="font-semibold text-gray-900 dark:text-gray-50">₹0.00</span>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[12px]">
                        <span class="text-gray-600 dark:text-gray-300">Price (incl. GST)</span>
                        <span id="calc_incl" class="font-semibold text-gray-900 dark:text-gray-50">₹0.00</span>
                    </div>
                </div>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="flex items-center justify-between text-[12px]">
                        <span class="text-gray-600 dark:text-gray-300">Discount vs MRP</span>
                        <span id="mrp_discount" class="font-semibold text-gray-900 dark:text-gray-50">—</span>
                    </div>
                    <div class="mt-1 text-[10px] text-gray-400">
                        Based on current sell price and MRP.
                    </div>
                </div>
            </div>

            {{-- Required classification --}}
            <div id="category-required-block">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Classification (Categories) <span class="text-red-500">*</span>
                </label>

                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-3 max-h-56 overflow-y-auto space-y-2">
                    @forelse($categories as $category)
                        <label class="flex items-start gap-2">
                            <input type="checkbox"
                                   name="category_ids[]"
                                   value="{{ $category->id }}"
                                   class="mt-0.5 rounded border-gray-300 dark:border-gray-700"
                                   @checked(in_array($category->id, $oldCategoryIds, true))>
                            <span class="text-[12px] text-gray-800 dark:text-gray-200">
                                {{ $category->name }}
                                @if($category->parent)
                                    <span class="text-[11px] text-gray-400 block">Child of {{ $category->parent->name }}</span>
                                @endif
                            </span>
                        </label>
                    @empty
                        <div class="text-[12px] text-gray-500 dark:text-gray-400">
                            No categories defined yet.
                            @if(\Illuminate\Support\Facades\Route::has('admin.categories.create'))
                                <a href="{{ route('admin.categories.create') }}" class="underline">Create one</a>.
                            @endif
                        </div>
                    @endforelse
                </div>

                <p id="category-required-error" class="hidden mt-2 text-[11px] text-red-600">
                    Please select at least one category.
                </p>

                @error('category_ids') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                @error('category_ids.*') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- OPTIONAL DETAILS --}}
    <details class="fb-acc {{ $card }}">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Optional details</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Slug, vendor, barcode, unit and extra product data.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Slug</label>
                    <input
                        type="text"
                        name="slug"
                        value="{{ old('slug', $product->slug ?? '') }}"
                        class="{{ $input }}"
                        placeholder="Auto-generated if empty"
                    >
                    @error('slug') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Product role</label>
                    <select name="inventory_role" class="{{ $select }}">
                        <option value="saleable" @selected($inventoryRole === 'saleable')>Saleable frontend product</option>
                        <option value="internal" @selected($inventoryRole === 'internal')>Internal / inward stock only</option>
                        <option value="both" @selected($inventoryRole === 'both')>Both inward and saleable</option>
                    </select>
                    <div class="{{ $hint }}">Use inactive/internal products for raw stock such as Full Pork Belly. Customer-facing packs remain normal saleable products.</div>
                    @error('inventory_role') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Vendor</label>
                    <select name="vendor_id" class="{{ $select }}">
                        <option value="">— None —</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected((int)old('vendor_id', $product->vendor_id ?? 0) === (int)$v->id)>
                                {{ $v->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('vendor_id') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Sell as</label>
                    <select name="sell_unit" class="{{ $select }}">
                        <option value="piece" @selected(old('sell_unit', $product->sell_unit ?? 'piece') === 'piece')>Quantity (per piece / pack)</option>
                        <option value="kg" @selected(old('sell_unit', $product->sell_unit ?? 'piece') === 'kg')>Weight (per kg)</option>
                        <option value="pack" @selected(old('sell_unit', $product->sell_unit ?? 'piece') === 'pack')>Pack</option>
                    </select>
                    <div class="{{ $hint }}">Controls ₹/pc vs ₹/kg display and cart behaviour.</div>
                    @error('sell_unit') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Product barcode</label>
                <input
                    type="text"
                    name="barcode"
                    value="{{ old('barcode', $product->barcode ?? '') }}"
                    class="{{ $input }}"
                    placeholder="EAN-13 / UPC / internal code"
                >
                <div class="{{ $hint }}">Useful for simple products without variants.</div>
                @error('barcode') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </details>

    {{-- ATTRIBUTES --}}
    <details class="fb-acc {{ $card }}">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Attributes & values</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Optional attribute mapping for filters and variants.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5">
            @if($attributes->isEmpty())
                <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-500 dark:text-gray-400">
                    No attributes defined yet.
                    @if(\Illuminate\Support\Facades\Route::has('admin.attributes.index'))
                        <a href="{{ route('admin.attributes.index') }}" class="underline">Manage attributes</a>.
                    @endif
                </div>
            @else
                <div class="space-y-3">
                    @foreach($attributes as $attribute)
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-3">
                            <div class="flex items-center justify-between">
                                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">
                                    {{ $attribute->display_name ?? $attribute->name }}
                                </div>
                                <div class="text-[10px] text-gray-400">{{ ucfirst($attribute->frontend_type) }}</div>
                            </div>

                            @if($attribute->values->isEmpty())
                                <div class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                                    No values defined.
                                    @if(\Illuminate\Support\Facades\Route::has('admin.attributes.values.index'))
                                        <a href="{{ route('admin.attributes.values.index', $attribute) }}" class="underline">Add values</a>.
                                    @endif
                                </div>
                            @else
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($attribute->values->sortBy('position') as $value)
                                        <label class="inline-flex items-center gap-2 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-1.5">
                                            <input type="checkbox"
                                                   name="attribute_value_ids[]"
                                                   value="{{ $value->id }}"
                                                   class="rounded border-gray-300 dark:border-gray-700"
                                                   @checked(in_array($value->id, $oldAttributeValueIds, true))>
                                            <span class="text-[12px] text-gray-800 dark:text-gray-200">{{ $value->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @error('attribute_value_ids') <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p> @enderror
            @error('attribute_value_ids.*') <p class="mt-2 text-[11px] text-red-600">{{ $message }}</p> @enderror
        </div>
    </details>

    {{-- INVENTORY & ORIGIN --}}
    <details class="fb-acc {{ $card }}">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Inventory & operations</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Stock, order controls and lot behaviour.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Stock quantity</label>
                    <input type="number" step="0.01" min="0" name="stock_quantity"
                           value="{{ old('stock_quantity', $product->stock_quantity ?? '') }}" class="{{ $input }}">
                    @error('stock_quantity') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Low stock threshold</label>
                    <input type="number" step="0.01" min="0" name="low_stock_threshold"
                           value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? '') }}" class="{{ $input }}">
                    @error('low_stock_threshold') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Min order qty</label>
                    <input type="number" step="0.01" min="0" name="min_order_quantity"
                           value="{{ old('min_order_quantity', $product->min_order_quantity ?? '') }}" class="{{ $input }}">
                    @error('min_order_quantity') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">
                        Default lot stage
                    </label>
                    <select name="lot_stage_default" class="{{ $select }}">
                        <option value="">— None —</option>
                        <option value="raw"   @selected(old('lot_stage_default', $product->lot_stage_default ?? '') === 'raw')>Raw</option>
                        <option value="slab"  @selected(old('lot_stage_default', $product->lot_stage_default ?? '') === 'slab')>Slab</option>
                        <option value="slice" @selected(old('lot_stage_default', $product->lot_stage_default ?? '') === 'slice')>Slice</option>
                        <option value="trim"  @selected(old('lot_stage_default', $product->lot_stage_default ?? '') === 'trim')>Trim</option>
                        <option value="waste" @selected(old('lot_stage_default', $product->lot_stage_default ?? '') === 'waste')>Waste</option>
                    </select>
                    <div class="{{ $hint }}">Used when inward or output lots are created.</div>
                    @error('lot_stage_default') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <div class="grid gap-2 md:grid-cols-2">
                        <input type="hidden" name="inventory_is_saleable" value="0">
                        <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                            <input type="checkbox"
                                   name="inventory_is_saleable"
                                   value="1"
                                   class="rounded border-gray-300 dark:border-gray-700"
                                   @checked(old('inventory_is_saleable', $product->inventory_is_saleable ?? true))>
                            <span class="text-[12px] text-gray-800 dark:text-gray-200">Saleable inventory</span>
                        </label>

                        <input type="hidden" name="inventory_can_repack" value="0">
                        <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                            <input type="checkbox"
                                   name="inventory_can_repack"
                                   value="1"
                                   class="rounded border-gray-300 dark:border-gray-700"
                                   @checked(old('inventory_can_repack', $product->inventory_can_repack ?? false))>
                            <span class="text-[12px] text-gray-800 dark:text-gray-200">Can be used in production / repack</span>
                        </label>
                    </div>

                    <div class="{{ $hint }} mt-3">
                        Examples:
                        <br>• Whole belly / salmon side → <strong>Raw + saleable + repackable</strong>
                        <br>• Slab pack → <strong>Slab + saleable + repackable</strong>
                        <br>• Slice pack → <strong>Slice + saleable + not repackable</strong>
                    </div>

                    @error('inventory_is_saleable') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                    @error('inventory_can_repack') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </details>

    {{-- VISIBILITY --}}
    <details class="fb-acc {{ $card }}">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Visibility & storefront options</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Store display and merchandising flags.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5 space-y-3">
            <input type="hidden" name="manage_stock" value="0">
            <input type="hidden" name="dynamic_pricing_enabled" value="0">
            <input type="hidden" name="is_featured" value="0">
            <input type="hidden" name="is_new" value="0">
            <input type="hidden" name="is_special" value="0">
            <input type="hidden" name="is_active" value="0">

            <div class="grid gap-2 md:grid-cols-3">
                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="manage_stock" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           @checked(old('manage_stock', $product->manage_stock ?? false))>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Manage stock</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="dynamic_pricing_enabled" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           @checked(old('dynamic_pricing_enabled', $product->dynamic_pricing_enabled ?? false))>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Dynamic pricing</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           @checked(old('is_featured', $product->is_featured ?? false))>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Featured</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_new" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           @checked(old('is_new', $product->is_new ?? false))>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">New</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_special" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           @checked(old('is_special', $product->is_special ?? false))>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Special</span>
                </label>

                <label class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-700"
                           @checked(old('is_active', $product->is_active ?? true))>
                    <span class="text-[12px] text-gray-800 dark:text-gray-200">Active / publish to storefront</span>
                </label>
            </div>
        </div>
    </details>

    {{-- SPECIAL PRICING --}}
    <details class="fb-acc {{ $card }}">
        <summary class="px-5 py-4 flex items-center justify-between gap-3 cursor-pointer">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Special pricing</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Optional override pricing window.</div>
            </div>
            <span class="text-[12px] text-gray-500 dark:text-gray-400 select-none">▾</span>
        </summary>

        <div class="px-5 pb-5">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special price (₹)</label>
                    <input type="number" step="0.01" min="0" name="special_price"
                           value="{{ $specialInput }}" class="{{ $input }}"
                           placeholder="Leave empty if none">
                    @error('special_price') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special audience</label>
                    <select name="special_audience" class="{{ $select }}">
                        <option value="b2c" @selected($specialAudience === 'b2c')>B2C / public only</option>
                        <option value="b2b" @selected($specialAudience === 'b2b')>B2B only</option>
                        <option value="all" @selected($specialAudience === 'all')>All customers</option>
                    </select>
                    @error('special_audience') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special starts at</label>
                    <input type="datetime-local" name="special_starts_at"
                           value="{{ old('special_starts_at', optional($product->special_starts_at ?? null)->format('Y-m-d\TH:i')) }}"
                           class="{{ $input }}">
                    @error('special_starts_at') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300">Special ends at</label>
                    <input type="datetime-local" name="special_ends_at"
                           value="{{ old('special_ends_at', optional($product->special_ends_at ?? null)->format('Y-m-d\TH:i')) }}"
                           class="{{ $input }}">
                    @error('special_ends_at') <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </details>

    {{-- Sticky actions --}}
    <div class="sticky bottom-3 z-10">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur px-4 py-3 flex items-center justify-between gap-3">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ $isEdit ? 'Editing existing product.' : 'Create a saleable product or save an inactive draft while pricing/weight are pending.' }}
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.index') }}"
                   class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Cancel
                </a>

                <button type="submit"
                        name="save_as_draft"
                        value="1"
                        class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-5 py-2 text-[12px] font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">
                    Save inactive draft
                </button>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-5 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                    {{ $isEdit ? 'Update product' : 'Create product' }}
                </button>
            </div>
        </div>
    </div>
</form>

<style>
    details.fb-acc summary {
        list-style: none;
        cursor: pointer;
    }
    details.fb-acc summary::-webkit-details-marker {
        display: none;
    }
</style>

<script>
(function () {
    const form = document.querySelector('form[data-product-form]');
    const categoryBoxes = Array.from(document.querySelectorAll('input[name="category_ids[]"]'));
    const categoryError = document.getElementById('category-required-error');
    const categoryBlock = document.getElementById('category-required-block');

    const modeEl  = document.getElementById('b2c_price_includes_gst');
    const hsnEl   = document.getElementById('hsn_code_id');
    const rateEl  = document.getElementById('gst_rate');
    const sellEl  = document.getElementById('price_input');
    const mrpEl   = document.getElementById('mrp_input');

    const outBase = document.getElementById('calc_base');
    const outIncl = document.getElementById('calc_incl');
    const outDisc = document.getElementById('mrp_discount');

    function validateCategories() {
        if (!categoryBoxes.length) return true;

        const hasOne = categoryBoxes.some(cb => cb.checked);
        if (categoryError) {
            categoryError.classList.toggle('hidden', hasOne);
        }
        return hasOne;
    }

    if (form && categoryBoxes.length) {
        form.addEventListener('submit', function (e) {
            if (!validateCategories()) {
                e.preventDefault();
                if (categoryBlock) {
                    categoryBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        categoryBoxes.forEach(cb => {
            cb.addEventListener('change', validateCategories);
        });

        validateCategories();
    }

    if (!modeEl || !rateEl || !sellEl || !outBase || !outIncl) return;

    function money(n) {
        n = Number.isFinite(n) ? n : 0;
        return '₹' + n.toFixed(2);
    }

    function syncRateFromHsn() {
        if (!hsnEl) return;
        const opt = hsnEl.options[hsnEl.selectedIndex];
        const rate = opt ? parseFloat(opt.dataset.rate || '') : NaN;

        if (!isNaN(rate)) {
            rateEl.value = rate.toFixed(2);
            rateEl.readOnly = true;
            rateEl.classList.add('opacity-80');
        } else {
            rateEl.readOnly = false;
            rateEl.classList.remove('opacity-80');
        }
    }

    function recalc() {
        const includes = String(modeEl.value) === '1';
        const rate = parseFloat(rateEl.value || '0');
        const sellEntered = parseFloat(sellEl.value || '0');
        const mul = 1 + (rate / 100);

        let base = 0;
        let incl = 0;

        if (includes) {
            incl = sellEntered;
            base = mul > 0 ? (sellEntered / mul) : sellEntered;
        } else {
            base = sellEntered;
            incl = sellEntered * mul;
        }

        outBase.textContent = money(base);
        outIncl.textContent = money(incl);

        if (outDisc && mrpEl) {
            const mrp = parseFloat(mrpEl.value || '');
            if (Number.isFinite(mrp) && mrp > 0) {
                const diff = mrp - incl;
                const pct = mrp > 0 ? (diff / mrp) * 100 : 0;

                if (diff > 0.009) outDisc.textContent = `-${money(diff)} (${pct.toFixed(1)}%)`;
                else if (Math.abs(diff) <= 0.009) outDisc.textContent = 'No discount';
                else outDisc.textContent = 'Sell > MRP';
            } else {
                outDisc.textContent = '—';
            }
        }
    }

    if (hsnEl) {
        hsnEl.addEventListener('change', function () {
            syncRateFromHsn();
            recalc();
        });
    }

    ['change', 'input'].forEach(evt => {
        modeEl.addEventListener(evt, recalc);
        rateEl.addEventListener(evt, recalc);
        sellEl.addEventListener(evt, recalc);
        if (mrpEl) mrpEl.addEventListener(evt, recalc);
    });

    syncRateFromHsn();
    recalc();
})();
</script>
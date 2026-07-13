@extends('layouts.company')

@section('title', 'Products')

@section('breadcrumb', 'Admin · Products')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Products
            </h1>

            <a href="{{ route('admin.products.create') }}"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                + New product
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <div class="flex items-center gap-2">
                    <label for="scan-barcode-open" class="text-[10px] text-gray-600 dark:text-gray-300">
                        Scan product to open:
                    </label>
                    <input id="scan-barcode-open"
                        type="text"
                        name="barcode"
                        autocomplete="off"
                        class="w-40 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                        placeholder="Focus & scan">
                </div>

                @if(session('error'))
                    <span class="text-[10px] text-red-500">
                        {{ session('error') }}
                    </span>
                @endif
                @if(session('status'))
                    <span class="text-[10px] text-emerald-600">
                        {{ session('status') }}
                    </span>
                @endif
            </div>
            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Name or SKU"
                    class="w-40 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Status
                </label>
                <select
                    name="status"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Storefront
                </label>
                <select
                    name="type"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="simple" @selected(request('type') === 'simple')>Direct / Physical</option>
                    <option value="variable" @selected(request('type') === 'variable')>Variant choice</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Product model
                </label>
                <select
                    name="model"
                    class="mt-1 w-44 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="simple" @selected(request('model') === 'simple')>Simple Products</option>
                    <option value="variable_pack" @selected(request('model') === 'variable_pack')>Variant Choice Products</option>
                    <option value="catchweight" @selected(request('model') === 'catchweight')>Catchweight Products</option>
                    <option value="produced" @selected(request('model') === 'produced')>Produced Products</option>
                    <option value="raw_source" @selected(request('model') === 'raw_source')>Raw Source Products</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Flag
                </label>
                <select
                    name="flag"
                    class="mt-1 w-32 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="">All</option>
                    <option value="featured" @selected(request('flag') === 'featured')>Featured</option>
                    <option value="new"      @selected(request('flag') === 'new')>New</option>
                    <option value="special"  @selected(request('flag') === 'special')>Special</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-medium text-gray-600 dark:text-gray-300">
                    Rows
                </label>
                <select
                    name="per_page"
                    onchange="this.form.submit()"
                    class="mt-1 w-24 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    @foreach(($allowedPerPage ?? [20, 50, 100]) as $option)
                        <option value="{{ $option }}" @selected((int) request('per_page', $perPage ?? 20) === (int) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Apply
                </button>

                @if(request()->hasAny(['q', 'status', 'type', 'model', 'flag', 'per_page']))
                    <a href="{{ route('admin.products.index') }}"
                       class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-transparent text-xs text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100">
                        Reset
                    </a>
                @endif
            </div>
        </form>
        {{-- Table --}}
        @php
            $formatStockNumber = static function ($value, int $decimals = 3): string {
                if ($value === null || $value === '') {
                    return '—';
                }

                $formatted = number_format((float) $value, $decimals);

                return rtrim(rtrim($formatted, '0'), '.') ?: '0';
            };
        @endphp

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
            <div>
                @if($products->total() > 0)
                    Showing
                    <span class="font-medium text-gray-900 dark:text-gray-50">{{ $products->firstItem() }}</span>
                    –
                    <span class="font-medium text-gray-900 dark:text-gray-50">{{ $products->lastItem() }}</span>
                    of
                    <span class="font-medium text-gray-900 dark:text-gray-50">{{ $products->total() }}</span>
                    products
                @else
                    No products match the current filters.
                @endif
            </div>

            @if($products->hasPages())
                <div class="product-pagination product-pagination-top">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">Storefront</th>
                        <th class="px-3 py-2 text-right">Price (₹)</th>
                        <th class="px-3 py-2 text-right">Stock Weight</th>
                        <th class="px-3 py-2 text-right">Stock Qty</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-3 py-2 align-top">
                                @php
                                    $hasPublicProductUrl = (string) ($product->slug ?? '') !== '';
                                    $productUrl = $hasPublicProductUrl
                                        ? route('product.show', $product->slug)
                                        : route('admin.products.edit', $product);

                                    $packType = (string) ($product->pack_type ?? 'quantity');
                                    $sellUnit = (string) ($product->sell_unit ?? '');
                                    $isRawSource = (int) ($product->produces_count ?? 0) > 0;
                                    $isProduced = (int) ($product->produced_from_count ?? 0) > 0;
                                    $modelLabel = (string) ($product->type ?? 'simple') === 'variable'
                                        ? 'Variable Pack'
                                        : ($packType === 'variable_weight' || $sellUnit === 'kg' ? 'Catchweight' : 'Simple');
                                @endphp

                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    <a href="{{ $productUrl }}"
                                       @if($hasPublicProductUrl) target="_blank" rel="noopener" @endif
                                       class="hover:underline hover:text-gray-700 dark:hover:text-gray-200"
                                       title="{{ $hasPublicProductUrl ? 'View product page' : 'Open product edit page' }}">
                                        {{ $product->name }}
                                    </a>
                                </div>

                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    @forelse($product->categories as $cat)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-600 dark:text-gray-300">
                                            {{ $cat->name }}
                                        </span>
                                    @empty
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                            Uncategorised
                                        </span>
                                    @endforelse

                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-600 dark:text-gray-300">
                                        {{ $modelLabel }}
                                    </span>
                                    @if($isProduced)
                                        <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 text-[10px] text-indigo-700 dark:text-indigo-300">
                                            Produced Product
                                        </span>
                                    @endif
                                    @if($isRawSource)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 text-[10px] text-amber-700 dark:text-amber-300">
                                            Raw Source Product
                                        </span>
                                    @endif
                                </div>

                                @if($product->producedFromProducts->isNotEmpty())
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                        ↳ Produced from:
                                        {{ $product->producedFromProducts->pluck('name')->take(3)->implode(', ') }}{{ $product->producedFromProducts->count() > 3 ? ' +' . ($product->producedFromProducts->count() - 3) : '' }}
                                    </div>
                                @endif

                                @if((int)($product->variants_count ?? 0) > 0)
                                    <div class="mt-1 text-[11px]">
                                        <a href="{{ route('admin.products.variants.index', $product) }}"
                                        class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                            {{ (int)($product->variants_count ?? 0) }} variant(s)
                                        </a>
                                    </div>
                                @endif
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($product->is_featured)
                                        <span class="inline-flex items-center rounded-full bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-2 py-0.5 text-[10px]">
                                            Featured
                                        </span>
                                    @endif
                                    @if($product->is_new)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 text-[10px]">
                                            New
                                        </span>
                                    @endif
                                    @if($product->is_special)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-0.5 text-[10px]">
                                            Special
                                        </span>
                                    @endif
                                </div>

                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                @php
                                    $packType = (string) ($product->pack_type ?? 'quantity');
                                    $sellUnit = (string) ($product->sell_unit ?? '');
                                    $typeLabel = (string) ($product->type ?? 'simple') === 'variable'
                                        ? 'Variable choice'
                                        : ($packType === 'variable_weight' || $sellUnit === 'kg' ? 'Physical choice' : 'Direct buy');
                                @endphp
                                <span class="rounded-full border border-gray-200 dark:border-gray-700 px-2 py-0.5 text-[11px]">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                @php
                                    $isVariablePriceProduct = (string) ($product->type ?? 'simple') === 'variable';
                                    $variantMinPrice = $product->variant_min_price !== null ? (float) $product->variant_min_price : null;
                                    $variantMaxPrice = $product->variant_max_price !== null ? (float) $product->variant_max_price : null;
                                @endphp

                                @if($isVariablePriceProduct)
                                    @if($variantMinPrice !== null && $variantMinPrice > 0)
                                        <div class="font-medium text-gray-900 dark:text-gray-50">
                                            ₹{{ number_format($variantMinPrice, 2) }}
                                            @if($variantMaxPrice !== null && $variantMaxPrice > $variantMinPrice)
                                                – ₹{{ number_format($variantMaxPrice, 2) }}
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-gray-400">Variant prices</div>
                                    @else
                                        <span class="text-[11px] text-amber-600 dark:text-amber-300">Set on variants</span>
                                    @endif
                                @elseif($product->base_price !== null)
                                    ₹{{ number_format((float) $product->base_price, 2) }}
                                @else
                                    <span class="text-[11px] text-gray-400">—</span>
                                @endif
                            </td>
                            @php
                                $storedStock = $product->stock_quantity !== null ? (float) $product->stock_quantity : null;
                                $unitWeight = (float) ($product->product_weight ?? 0);
                                $inventoryStockWeight = (float) ($product->inventory_stock_weight_kg ?? 0);
                                $inventoryStockQty = (float) ($product->inventory_stock_qty ?? 0);
                                $variantStockWeight = (float) ($product->variant_stock_weight_kg ?? 0);
                                $variantStockQty = (float) ($product->variant_stock_qty ?? 0);
                                $isVariableProduct = (string) ($product->type ?? 'simple') === 'variable';
                                $usesWeightStock = $sellUnit === 'kg' || $packType === 'variable_weight';

                                if ($inventoryStockWeight > 0) {
                                    $stockWeight = $inventoryStockWeight;
                                } elseif ($isVariableProduct && $variantStockWeight > 0) {
                                    $stockWeight = $variantStockWeight;
                                } elseif ($storedStock !== null && $usesWeightStock) {
                                    $stockWeight = $storedStock;
                                } elseif ($storedStock !== null && abs($storedStock) < 0.0005) {
                                    $stockWeight = 0;
                                } elseif ($storedStock !== null && $unitWeight > 0) {
                                    $stockWeight = $storedStock * $unitWeight;
                                } else {
                                    $stockWeight = null;
                                }

                                if ($inventoryStockQty > 0) {
                                    $stockQty = $inventoryStockQty;
                                } elseif ($isVariableProduct && $variantStockQty > 0) {
                                    $stockQty = $variantStockQty;
                                } elseif ($storedStock !== null && ! $usesWeightStock) {
                                    $stockQty = $storedStock;
                                } elseif ($storedStock !== null && $usesWeightStock && $unitWeight > 0) {
                                    $stockQty = $storedStock / $unitWeight;
                                } elseif ($storedStock !== null && abs($storedStock) < 0.0005) {
                                    $stockQty = 0;
                                } else {
                                    $stockQty = null;
                                }
                            @endphp
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                @if($stockWeight !== null)
                                    {{ $formatStockNumber($stockWeight) }} kg
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                {{ $stockQty !== null ? $formatStockNumber($stockQty) : '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-center">
                                @if($product->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.products.images.index', $product) }}"
                                    class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                                        Images
                                    </a>

                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        Edit
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.products.destroy', $product) }}"
                                          onsubmit="return confirm('Delete this product?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No products found. <a href="{{ route('admin.products.create') }}" class="underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="product-pagination product-pagination-bottom">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection




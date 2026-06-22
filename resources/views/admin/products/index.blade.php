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
                <button
                    type="submit"
                    class="mt-5 inline-flex items-center px-3 py-1.5 rounded border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Apply
                </button>
            </div>
        </form>
        {{-- Table --}}
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Name</th>
                        <th class="px-3 py-2 text-left">SKU</th>
                        <th class="px-3 py-2 text-left">Storefront</th>
                        <th class="px-3 py-2 text-right">Price (₹)</th>
                        <th class="px-3 py-2 text-right">Stock</th>
                        <th class="px-3 py-2 text-left">Vendor</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-950">
                    @forelse($products as $product)
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    {{ $product->name }}
                                </div>
                                @if($product->short_description)
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-1">
                                        {{ $product->short_description }}
                                    </div>
                                @endif

                                @if($product->categories->isNotEmpty())
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($product->categories as $cat)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[10px] text-gray-600 dark:text-gray-300">
                                                {{ $cat->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @php
                                    $packType = (string) ($product->pack_type ?? 'quantity');
                                    $isRawSource = (int) ($product->produces_count ?? 0) > 0;
                                    $isProduced = (int) ($product->produced_from_count ?? 0) > 0;
                                    $modelLabel = (string) ($product->type ?? 'simple') === 'variable'
                                        ? 'Variable Pack'
                                        : ($packType === 'variable_weight' || (string)($product->sell_unit ?? '') === 'kg' ? 'Catchweight' : 'Simple');
                                @endphp

                                <div class="mt-1 flex flex-wrap gap-1">
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
                                @else
                                    {{-- <div class="mt-1 text-[11px]">
                                        {{-- <a href="{{ route('admin.products.variants.index', $product) }}" 
                                        <p class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                            No variants 
                                    </p>
                                    </div> --}}
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
                                {{ $product->sku ?: '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                @php
                                    $packType = (string) ($product->pack_type ?? 'quantity');
                                    $typeLabel = (string) ($product->type ?? 'simple') === 'variable'
                                        ? 'Variable choice'
                                        : ($packType === 'variable_weight' || (string)($product->sell_unit ?? '') === 'kg' ? 'Physical choice' : 'Direct buy');
                                @endphp
                                <span class="rounded-full border border-gray-200 dark:border-gray-700 px-2 py-0.5 text-[11px]">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                ₹{{ number_format($product->base_price, 2) }}
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                {{ $product->stock_quantity ?? '—' }}
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ $product->vendor->name ?? '—' }}
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
                            <td colspan="8" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No products found. <a href="{{ route('admin.products.create') }}" class="underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $products->links() }}
        </div>
    </div>
@endsection




@extends('layouts.company')

@section('title', 'Edit product')

@section('breadcrumb', 'Admin · Products · Edit')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Edit product
            </h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Update the core product details first, then manage images, variants, and optional settings.
            </p>

            <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-gray-700 dark:text-gray-200">
                    Name: {{ $product->name }}
                </span>

                @if(!empty($product->sku))
                    <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-gray-700 dark:text-gray-200">
                        SKU: {{ $product->sku }}
                    </span>
                @endif

                <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1 text-gray-700 dark:text-gray-200 capitalize">
                    Type: {{ $product->type ?? 'simple' }}
                </span>

                <span class="inline-flex items-center rounded-sm border px-3 py-1
                    {{ ($product->is_active ?? true)
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                        : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ ($product->is_active ?? true) ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.products.index') }}"
               class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back
            </a>

            <a href="{{ route('admin.products.images.index', $product) }}"
               class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Manage images
            </a>

            <a href="{{ route('admin.products.variants.index', $product) }}"
               class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Manage variants
            </a>

            <a href="{{ route('admin.products.sell-units.index', $product) }}"
               class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Sellable units
            </a>

            @if(Route::has('admin.recipes.index'))
                <a href="{{ route('admin.recipes.index', ['product_id' => $product->id]) }}"
                   class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Recipes
                </a>
            @endif
        </div>
    </div>

    @include('admin.products._form', [
        'action'  => route('admin.products.update', $product),
        'product' => $product,
        'vendors' => $vendors ?? collect(),

        'categories'               => $categories ?? collect(),
        'selectedCategoryIds'      => $selectedCategoryIds ?? [],
        'attributes'               => $attributes ?? collect(),
        'selectedAttributeValueIds'=> $selectedAttributeValueIds ?? [],
        'hsnCodes'                 => $hsnCodes ?? collect(),
        'countries'                => $countries ?? collect(),
        'supplierVendors'          => $supplierVendors ?? collect(),
    ])
</div>
@endsection
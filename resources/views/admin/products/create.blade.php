@extends('layouts.company')

@section('title', 'Create product')

@section('breadcrumb', 'Admin · Products · Create')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Create product</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Start with the essential product details first. Optional settings can be added below.
            </p>
        </div>

        <a href="{{ route('admin.products.index') }}"
           class="text-[12px] px-4 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @include('admin.products._form', [
        'action'  => route('admin.products.store'),
        'product' => null,
        'vendors' => $vendors ?? collect(),

        'countries' => $countries ?? collect(),
        'hsnCodes'  => $hsnCodes ?? collect(),
        'categories' => $categories ?? collect(),
        'attributes' => $attributes ?? collect(),

        'selectedCategoryIds' => $selectedCategoryIds ?? [],
        'selectedAttributeValueIds' => $selectedAttributeValueIds ?? [],
    ])
</div>
@endsection
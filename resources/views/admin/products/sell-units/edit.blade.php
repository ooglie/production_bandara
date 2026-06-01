@extends('layouts.company')

@section('title', 'Edit sellable unit')
@section('breadcrumb', 'Admin · Products · Sellable units · Edit')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Edit sellable unit</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">{{ $product->name }} · {{ $sellUnit->name }}</p>
        </div>
        <a href="{{ route('admin.products.sell-units.index', $product) }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Back</a>
    </div>

    @include('admin.products.sell-units._form', [
        'action' => route('admin.sell-units.update', $sellUnit),
        'method' => 'PUT',
        'product' => $product,
        'sellUnit' => $sellUnit,
        'variants' => $variants,
        'selectedVariantIds' => $selectedVariantIds,
    ])
</div>
@endsection

@extends('layouts.company')

@section('title', 'New Recipe')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">New Recipe</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Create a reusable recipe and link it to one or more products.
            </p>
        </div>
    </div>

    @include('admin.recipes._form', [
        'action' => route('admin.recipes.store'),
        'recipe' => null,
        'products' => $products,
        'selectedProductIds' => $selectedProductIds ?? [],
    ])
</div>
@endsection
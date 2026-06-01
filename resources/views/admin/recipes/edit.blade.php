@extends('layouts.company')

@section('title', 'Edit Recipe')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit Recipe</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Update recipe details and linked products.
            </p>
        </div>
    </div>

    @include('admin.recipes._form', [
        'action' => route('admin.recipes.update', $recipe),
        'recipe' => $recipe,
        'products' => $products,
        'selectedProductIds' => $selectedProductIds ?? [],
    ])
</div>
@endsection
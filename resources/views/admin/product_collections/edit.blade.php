@extends('layouts.company')

@section('title', 'Edit Collection')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Edit collection</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Update content, homepage settings, and attached products.
            </p>
        </div>

        <a
            href="{{ route('admin.product-collections.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
        >
            Back
        </a>
    </div>

    <form method="POST" action="{{ route('admin.product-collections.update', $productCollection) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.product_collections._form', [
            'productCollection' => $productCollection,
            'products' => $products,
        ])
    </form>
</div>
@endsection
@extends('layouts.company')

@section('title', 'Create variant')

@section('breadcrumb')
    Admin · Products · {{ $product->name }} · Variants · Create
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create variant – {{ $product->name }}
        </h1>

        @include('admin.products.variants._form', [
            'action'  => route('admin.products.variants.store', $product),
            'product' => $product,
            'variant' => null,
        ])
    </div>
@endsection

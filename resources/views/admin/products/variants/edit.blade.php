@extends('layouts.company')

@section('title', 'Edit variant')

@section('breadcrumb')
    Admin · Products · {{ $product->name }} · Variants · Edit
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit variant – {{ $product->name }}
        </h1>

        @include('admin.products.variants._form', [
            'action'  => route('admin.variants.update', $variant),
            'product' => $product,
            'variant' => $variant,
        ])
    </div>
@endsection

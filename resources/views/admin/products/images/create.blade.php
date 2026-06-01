@extends('layouts.company')

@section('title', 'Upload product image')

@section('breadcrumb')
    Admin · Products · {{ $product->name }} · Images · Upload
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Upload image – {{ $product->name }}
        </h1>

        @include('admin.products.images._form', [
            'action' => route('admin.products.images.store', $product),
            'product'=> $product,
            'image'  => null,
        ])
    </div>
@endsection

@extends('layouts.company')

@section('title', 'Edit product image')

@section('breadcrumb')
    Admin · Products · {{ $product->name }} · Images · Edit
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit image – {{ $product->name }}
        </h1>

        @include('admin.products.images._form', [
            'action' => route('admin.images.update', $image),
            'product'=> $product,
            'image'  => $image,
        ])
    </div>
@endsection

@extends('layouts.company')

@section('title', 'Create variant option value')

@section('breadcrumb')
    Admin · Variant Options · {{ $attribute->name }} · Option Values · Create
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create option value – {{ $attribute->name }}
        </h1>

        @include('admin.attributes.values._form', [
            'action'   => route('admin.attributes.values.store', $attribute),
            'attribute'=> $attribute,
            'value'    => null,
        ])
    </div>
@endsection

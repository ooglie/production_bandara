@extends('layouts.company')

@section('title', 'Edit variant option value')

@section('breadcrumb')
    Admin · Variant Options · {{ $attribute->name }} · Option Values · Edit
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit option value – {{ $attribute->name }}
        </h1>

        @include('admin.attributes.values._form', [
            'action'   => route('admin.values.update', $value),
            'attribute'=> $attribute,
            'value'    => $value,
        ])
    </div>
@endsection

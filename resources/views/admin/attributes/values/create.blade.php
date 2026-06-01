@extends('layouts.company')

@section('title', 'Create attribute value')

@section('breadcrumb')
    Admin · Attributes · {{ $attribute->name }} · Values · Create
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create value – {{ $attribute->name }}
        </h1>

        @include('admin.attributes.values._form', [
            'action'   => route('admin.attributes.values.store', $attribute),
            'attribute'=> $attribute,
            'value'    => null,
        ])
    </div>
@endsection

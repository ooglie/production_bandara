@extends('layouts.company')

@section('title', 'Edit attribute value')

@section('breadcrumb')
    Admin · Attributes · {{ $attribute->name }} · Values · Edit
@endsection

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit value – {{ $attribute->name }}
        </h1>

        @include('admin.attributes.values._form', [
            'action'   => route('admin.attributes.values.update', $value),
            'attribute'=> $attribute,
            'value'    => $value,
        ])
    </div>
@endsection

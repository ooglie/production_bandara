@extends('layouts.company')

@section('title', 'Edit attribute')

@section('breadcrumb', 'Admin · Attributes · Edit')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit attribute
        </h1>

        @include('admin.attributes._form', [
            'action'    => route('admin.attributes.update', $attribute),
            'attribute' => $attribute,
        ])
    </div>
@endsection

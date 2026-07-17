@extends('layouts.company')

@section('title', 'Edit variant option group')

@section('breadcrumb', 'Admin · Variant Options · Edit')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit variant option group
        </h1>

        @include('admin.attributes._form', [
            'action'    => route('admin.attributes.update', $attribute),
            'attribute' => $attribute,
        ])
    </div>
@endsection

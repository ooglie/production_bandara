@extends('layouts.company')

@section('title', 'Create variant option group')

@section('breadcrumb', 'Admin · Variant Options · Create')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create variant option group
        </h1>

        @include('admin.attributes._form', [
            'action'    => route('admin.attributes.store'),
            'attribute' => null,
        ])
    </div>
@endsection

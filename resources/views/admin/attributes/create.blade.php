@extends('layouts.company')

@section('title', 'Create attribute')

@section('breadcrumb', 'Admin · Attributes · Create')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create attribute
        </h1>

        @include('admin.attributes._form', [
            'action'    => route('admin.attributes.store'),
            'attribute' => null,
        ])
    </div>
@endsection

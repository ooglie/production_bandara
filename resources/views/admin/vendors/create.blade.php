@extends('layouts.company')

@section('title', 'Create vendor')

@section('breadcrumb', 'Admin · Vendors · Create')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create vendor
        </h1>

        @include('admin.vendors._form', [
            'action' => route('admin.vendors.store'),
            'vendor' => null,
        ])
    </div>
@endsection

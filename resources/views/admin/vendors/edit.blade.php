@extends('layouts.company')

@section('title', 'Edit vendor')

@section('breadcrumb', 'Admin · Vendors · Edit')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit vendor
        </h1>

        @include('admin.vendors._form', [
            'action' => route('admin.vendors.update', $vendor),
            'vendor' => $vendor,
        ])
    </div>
@endsection

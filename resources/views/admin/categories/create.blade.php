@extends('layouts.company')

@section('title', 'Create category')

@section('breadcrumb', 'Admin · Categories · Create')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Create category
        </h1>

        @include('admin.categories._form', [
            'action'  => route('admin.categories.store'),
            'category'=> null,
            'parents' => $parents,
        ])
    </div>
@endsection

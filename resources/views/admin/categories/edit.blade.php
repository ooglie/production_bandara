@extends('layouts.company')

@section('title', 'Edit category')

@section('breadcrumb', 'Admin · Categories · Edit')

@section('content')
    <div class="space-y-4">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            Edit category
        </h1>

        @include('admin.categories._form', [
            'action'  => route('admin.categories.update', $category),
            'category'=> $category,
            'parents' => $parents,
        ])
    </div>
@endsection

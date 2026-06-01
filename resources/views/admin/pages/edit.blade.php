@extends('layouts.company')

@section('title', 'Edit Page')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit Page</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Update static page content and metadata.
            </p>
        </div>
    </div>

    @include('admin.pages._form', [
        'action' => route('admin.pages.update', $page),
        'page' => $page,
    ])
</div>
@endsection
@extends('layouts.company')

@section('title', 'Edit Announcement')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Edit announcement</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Update the home page banner content and schedule.
            </p>
        </div>

        <a
            href="{{ route('admin.announcements.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
        >
            Back
        </a>
    </div>

    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('admin.announcements._form', ['announcement' => $announcement])
    </form>
</div>
@endsection
@extends('layouts.customer')

@section('title', 'Open support ticket')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 text-xs space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50 mb-2">
        Open a support ticket
    </h1>

    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-3">
        @csrf

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Category
            </label>
            <select name="category_id"
                    class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">Select a category…</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Subject
            </label>
            <input type="text" name="subject" value="{{ old('subject') }}"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            @error('subject')
                <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Describe your issue
            </label>
            <textarea name="message" rows="5"
                      class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">{{ old('message') }}</textarea>
            @error('message')
                <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Attachments (optional)
            </label>
            <input type="file" name="attachments[]" multiple
                   class="w-full text-[11px] text-gray-600 dark:text-gray-300">
            <p class="mt-1 text-[10px] text-gray-400">
                Up to 5MB per file.
            </p>
            @error('attachments.*')
                <p class="mt-1 text-[10px] text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2 flex items-center justify-between">
            <a href="{{ route('tickets.index') }}"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Submit ticket
            </button>
        </div>
    </form>
</div>
@endsection

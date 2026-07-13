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

        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 p-4 space-y-3">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Generated collage</h2>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Generate a category image from active product images in this category and its child categories.
                        Manual category images still take priority on the storefront.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('admin.categories.collage.generate', $category) }}" class="inline-flex items-center gap-2">
                        @csrf
                        <select name="limit" class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-[11px]">
                            <option value="4">4 images</option>
                            <option value="6" selected>6 images</option>
                            <option value="9">9 images</option>
                        </select>
                        <button type="submit" class="inline-flex items-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                            {{ $category->collage_image_path ? 'Regenerate collage' : 'Generate collage' }}
                        </button>
                    </form>

                    @if($category->collage_image_path)
                        <form method="POST" action="{{ route('admin.categories.collage.destroy', $category) }}" onsubmit="return confirm('Remove the generated category collage?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center rounded border border-red-200 px-3 py-1.5 text-[11px] font-medium text-red-700 hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-950/20">
                                Remove collage
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if($category->collage_image_path)
                <div class="grid gap-3 lg:grid-cols-[240px,1fr] items-start">
                    <img src="{{ Storage::disk('public')->url($category->collage_image_path) }}" alt="Generated collage for {{ $category->name }}" class="w-full rounded border border-gray-200 dark:border-gray-800 aspect-[3/2] object-cover">
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 space-y-1">
                        <div>Path: <span class="font-mono">{{ $category->collage_image_path }}</span></div>
                        <div>Last generated: {{ optional($category->collage_updated_at)->format('d M Y, H:i') ?: '—' }}</div>
                    </div>
                </div>
            @else
                <div class="rounded border border-dashed border-gray-300 dark:border-gray-700 px-3 py-4 text-[11px] text-gray-500 dark:text-gray-400">
                    No generated collage yet.
                </div>
            @endif
        </div>
    </div>
@endsection

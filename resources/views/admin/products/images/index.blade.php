@extends('layouts.company')

@section('title', 'Product images')

@section('breadcrumb')
    Admin · Products · {{ $product->name }} · Images
@endsection

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Images – {{ $product->name }}
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    SKU: {{ $product->sku ?: '—' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.products.edit', $product) }}"
                   class="text-[11px] text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                    Back to product
                </a>

                <a href="{{ route('admin.products.images.create', $product) }}"
                   class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200">
                    + Upload image
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if($images->isEmpty())
            <div class="rounded border border-gray-200 dark:border-gray-800 px-3 py-4 text-xs text-gray-500 dark:text-gray-400">
                No images yet. <a href="{{ route('admin.products.images.create', $product) }}" class="underline">Upload one</a>.
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($images as $image)
                    <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden bg-white dark:bg-gray-950 flex flex-col">
                        <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                            @if($image->file_path)
                                <img
                                    src="{{ Storage::url($image->file_path) }}"
                                    alt="{{ $image->alt_text }}"
                                    class="object-contain max-h-full max-w-full"
                                >
                            @else
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">No preview</span>
                            @endif
                        </div>

                        <div class="p-3 space-y-2 text-xs flex-1 flex flex-col">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-800 dark:text-gray-100">
                                    #{{ $image->id }}
                                </span>
                                @if($image->is_primary)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[10px]">
                                        Primary
                                    </span>
                                @endif
                            </div>

                            @if($image->alt_text)
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                                    Alt: {{ $image->alt_text }}
                                </div>
                            @endif

                            <div class="text-[10px] text-gray-400 dark:text-gray-500">
                                Position: {{ $image->position ?? 0 }}
                            </div>

                            <div class="mt-auto pt-2 flex items-center justify-between">
                                <a href="{{ route('admin.images.edit', $image) }}"
                                   class="text-[11px] text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="{{ route('admin.images.destroy', $image) }}"
                                      onsubmit="return confirm('Delete this image?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] text-red-600 hover:text-red-700">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

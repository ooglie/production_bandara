@extends('layouts.company')

@section('title', 'Collections')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Collections</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage curated product collections for occasions, chef picks, and homepage campaigns.
            </p>
        </div>

        <a
            href="{{ route('admin.product-collections.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
        >
            New collection
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @if($collections->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Collection</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Kind</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Products</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Home</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($collections as $collection)
                            <tr>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">
                                        {{ $collection->name }}
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        /collections/{{ $collection->slug }}
                                    </div>

                                    @if($collection->description)
                                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                                            {{ $collection->description }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                        {{ ucfirst($collection->kind) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 align-top text-sm text-gray-700 dark:text-gray-200">
                                    {{ $collection->products_count }}
                                </td>

                                <td class="px-4 py-4 align-top text-sm text-gray-600 dark:text-gray-300">
                                    @if($collection->show_on_home)
                                        <div class="font-medium">{{ $collection->home_section ?: 'General' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Order: {{ $collection->home_order }}</div>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Not shown on home</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        @if($collection->is_active)
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                Inactive
                                            </span>
                                        @endif

                                        @if($collection->show_on_home)
                                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">
                                                Home
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('admin.product-collections.edit', $collection) }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        >
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.product-collections.destroy', $collection) }}" onsubmit="return confirm('Delete this collection?')">
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center rounded-lg border border-red-300 px-3 py-1.5 text-sm text-red-700 hover:bg-red-50 dark:border-red-900/50 dark:text-red-300 dark:hover:bg-red-950/30"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-800">
                {{ $collections->links() }}
            </div>
        @else
            <div class="px-6 py-10 text-center">
                <h2 class="text-base font-medium text-gray-900 dark:text-gray-50">No collections yet</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Create your first curated collection for occasions, chef picks, or seasonal campaigns.
                </p>

                <div class="mt-4">
                    <a
                        href="{{ route('admin.product-collections.create') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                    >
                        Create collection
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
@extends('layouts.company')

@section('title', 'Pages')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Pages</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage About, Terms, Privacy and other static pages.
            </p>
        </div>

        <a href="{{ route('admin.pages.create') }}"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            New page
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}"
               placeholder="Search key / title"
               class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 text-[11px]">

        <select name="status"
                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 text-[11px]">
            <option value="">All</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>

        <button class="text-[11px] px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Apply
        </button>

        @if(request()->query())
            <a href="{{ route('admin.pages.index') }}"
               class="text-[11px] px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        @endif
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Key</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Sort</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($pages as $page)
                    <tr>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                            {{ $page->key }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $page->tr('title', 'en') }}</div>
                            <div class="text-[10px] text-gray-400">{{ $page->tr('slug', 'en') }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                {{ $page->is_active
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                                    : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400' }}">
                                {{ $page->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            {{ $page->sort_order }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="{{ route('admin.pages.destroy', $page) }}"
                                      onsubmit="return confirm('Delete this page?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] text-red-600 dark:text-red-400 underline">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                            No pages found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $pages->links() }}
</div>
@endsection
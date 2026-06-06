@extends('layouts.company')

@section('title', 'Homepage Sections')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Homepage</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage home page sections, copy, CTAs, images, linked records, scheduling, and display order.
            </p>
        </div>
        <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
            Preview homepage
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Section</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($sections as $section)
                        @php($previewUrl = route('home') . '#home-section-' . $section->key)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900 dark:text-gray-50">{{ $section->title ?: $section->key }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $section->key }}</div>
                                @if($section->starts_at || $section->ends_at)
                                    <div class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">
                                        @if($section->starts_at) Starts {{ $section->starts_at->format('d M Y H:i') }} @endif
                                        @if($section->ends_at) · Ends {{ $section->ends_at->format('d M Y H:i') }} @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $section->type }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $section->items_count }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $section->sort_order }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    @if($section->is_active)
                                        <span class="inline-flex w-max rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">Active</span>
                                    @else
                                        <span class="inline-flex w-max rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                                    @endif

                                    @if($section->isCurrentlyVisible())
                                        <span class="inline-flex w-max rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">Visible now</span>
                                    @elseif($section->is_active)
                                        <span class="inline-flex w-max rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Scheduled / outside window</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.home-sections.move-up', $section) }}">@csrf<button class="rounded-xl border border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" title="Move up">↑</button></form>
                                    <form method="POST" action="{{ route('admin.home-sections.move-down', $section) }}">@csrf<button class="rounded-xl border border-gray-300 px-2.5 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800" title="Move down">↓</button></form>
                                    <form method="POST" action="{{ route('admin.home-sections.toggle', $section) }}">@csrf<button class="rounded-xl border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">{{ $section->is_active ? 'Disable' : 'Enable' }}</button></form>
                                    <a href="{{ $previewUrl }}" target="_blank" class="inline-flex rounded-xl border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Preview</a>
                                    <a href="{{ route('admin.home-sections.edit', $section) }}" class="inline-flex rounded-xl bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                No home sections found. Run migrations to create the default home page records.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

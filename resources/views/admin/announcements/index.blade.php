@extends('layouts.company')

@section('title', 'Announcements')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Announcements</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage home page banners for specials, festive wishes and top-page messages.
            </p>
        </div>

        <a
            href="{{ route('admin.announcements.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
        >
            New announcement
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @php $now = now(); @endphp

        @if($announcements->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Announcement</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Schedule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($announcements as $announcement)
                            @php
                                $scheduled = $announcement->starts_at && $announcement->starts_at->isFuture();
                                $expired = $announcement->ends_at && $announcement->ends_at->lt($now);
                                $live = $announcement->is_active && $announcement->show_on_home && !$scheduled && !$expired;
                            @endphp

                            <tr class="align-top">
                                <td class="px-4 py-4">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">
                                        {{ $announcement->title }}
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $announcement->label ?: ucfirst($announcement->type) }}
                                    </div>

                                    @if($announcement->message)
                                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                                            {{ $announcement->message }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                        {{ ucfirst($announcement->type) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div>
                                        <span class="font-medium">Start:</span>
                                        {{ $announcement->starts_at ? $announcement->starts_at->format('d M Y, h:i A') : 'Immediate' }}
                                    </div>
                                    <div class="mt-1">
                                        <span class="font-medium">End:</span>
                                        {{ $announcement->ends_at ? $announcement->ends_at->format('d M Y, h:i A') : 'No expiry' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-200">
                                    {{ $announcement->priority }}
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @if($live)
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">Live</span>
                                        @endif

                                        @if(!$announcement->is_active)
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Inactive</span>
                                        @endif

                                        @if($announcement->show_on_home)
                                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-300">Home</span>
                                        @endif

                                        @if($announcement->is_dismissible)
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Dismissible</span>
                                        @endif

                                        @if($scheduled)
                                            <span class="inline-flex rounded-full bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">Scheduled</span>
                                        @endif

                                        @if($expired)
                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-300">Expired</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('admin.announcements.edit', $announcement) }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        >
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
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
                {{ $announcements->links() }}
            </div>
        @else
            <div class="px-6 py-10 text-center">
                <h2 class="text-base font-medium text-gray-900 dark:text-gray-50">No announcements yet</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Create your first home page banner for festive wishes, special offers or general messages.
                </p>

                <div class="mt-4">
                    <a
                        href="{{ route('admin.announcements.create') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                    >
                        Create announcement
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
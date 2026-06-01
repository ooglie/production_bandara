@extends('layouts.company')

@section('title', 'Newsletter subscribers')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Newsletter subscribers
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage newsletter signup, double opt-in, and exports.
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <a href="{{ route('admin.newsletter-subscribers.index', array_merge(request()->all(), ['export' => 'csv'])) }}"
               class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5">
                Export CSV
            </a>
            <a href="{{ route('admin.newsletter-subscribers.create') }}"
               class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5">
                + Add subscriber
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" class="flex flex-wrap items-center gap-2 text-xs mb-3">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search email / name / source"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <select
            name="status"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
            <option value="">All statuses</option>
            @foreach(['pending', 'active', 'unsubscribed', 'bounced'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>
        <button
            class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5">
            Filter
        </button>
    </form>

    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-[11px] text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2.5">Email</th>
                    <th class="px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">Confirmed</th>
                    <th class="px-3 py-2.5">Unsubscribed</th>
                    <th class="px-3 py-2.5">Source</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            {{ $subscriber->email }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $subscriber->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                @if($subscriber->status === 'active')
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @elseif($subscriber->status === 'unsubscribed')
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                @elseif($subscriber->status === 'pending')
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @else
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                @endif
                            ">
                                {{ ucfirst($subscriber->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ optional($subscriber->confirmed_at)->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ optional($subscriber->unsubscribed_at)->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ $subscriber->source ?? '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.newsletter-subscribers.edit', $subscriber) }}"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                @if($subscriber->status === 'pending')
                                    <form method="POST"
                                          action="{{ route('admin.newsletter-subscribers.resend-confirmation', $subscriber) }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                            Resend
                                        </button>
                                    </form>
                                @endif

                                <form method="POST"
                                      action="{{ route('admin.newsletter-subscribers.destroy', $subscriber) }}"
                                      onsubmit="return confirm('Delete this subscriber?');">
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
                        <td colspan="7" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No subscribers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            {{ $subscribers->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

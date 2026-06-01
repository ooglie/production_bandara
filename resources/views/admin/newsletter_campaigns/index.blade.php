@extends('layouts.company')

@section('title', 'Newsletter campaigns')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Newsletter campaigns
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Create and send email campaigns to active newsletter subscribers.
            </p>
        </div>
        <a href="{{ route('admin.newsletter-campaigns.create') }}"
           class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs">
            + New campaign
        </a>
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
            placeholder="Search name / subject"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <select
            name="status"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
            <option value="">All statuses</option>
            @foreach(['draft','scheduled','sending','sent','cancelled'] as $status)
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
                    <th class="px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Subject</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">Recipients</th>
                    <th class="px-3 py-2.5">Created by</th>
                    <th class="px-3 py-2.5">Sent at</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                {{ $campaign->name }}
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $campaign->subject }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                @if($campaign->status === 'sent')
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @elseif($campaign->status === 'sending')
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @elseif($campaign->status === 'cancelled')
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                @else
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                @endif
                            ">
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $campaign->recipients_count }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $campaign->createdBy->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ optional($campaign->sent_at)->format('d M Y H:i') ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.newsletter-campaigns.edit', $campaign) }}"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                @if(in_array($campaign->status, ['draft','scheduled'], true))
                                    <form method="POST"
                                          action="{{ route('admin.newsletter-campaigns.send', $campaign) }}"
                                          onsubmit="return confirm('Send this campaign to all active subscribers now?');">
                                        @csrf
                                        <button type="submit"
                                                class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                            Send now
                                        </button>
                                    </form>
                                @endif

                                @if(!in_array($campaign->status, ['sending','sent'], true))
                                    <form method="POST"
                                          action="{{ route('admin.newsletter-campaigns.destroy', $campaign) }}"
                                          onsubmit="return confirm('Delete this campaign?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-[11px] text-red-600 dark:text-red-400 underline">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No campaigns found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            {{ $campaigns->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

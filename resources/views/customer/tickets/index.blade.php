@extends('layouts.customer')

@section('title', 'My support tickets')

@php
    $openUrl   = \Illuminate\Support\Facades\Route::has('tickets.create') ? route('tickets.create') : '#';
    $indexUrl  = \Illuminate\Support\Facades\Route::has('tickets.index') ? route('tickets.index') : url()->current();

    $statusLabel = function ($st) {
        $st = (string) $st;
        return ucfirst(str_replace('_', ' ', $st));
    };

    $badgeClass = function ($st) {
        $st = (string) $st;

        return match ($st) {
            'open' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200',
            'awaiting_customer' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
            'awaiting_agent' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200',
            'resolved' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
            'closed' => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
            default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
        };
    };
@endphp

@section('content')

<div class="max-w-6xl mx-auto px-4 py-6 space-y-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Support tickets</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Track your requests and replies from the support team.
            </p>
        </div>

        <a href="{{ $openUrl }}"
           class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
            + Open new ticket
        </a>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    {{-- List --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-4 py-3 font-medium">Ticket</th>
                    <th class="px-4 py-3 font-medium">Category</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Updated</th>
                    <th class="px-4 py-3 font-medium text-right">Action</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($tickets as $ticket)
                    @php
                        $showUrl = \Illuminate\Support\Facades\Route::has('tickets.show') ? route('tickets.show', $ticket) : '#';
                        $tn = $ticket->ticket_number ?? ('#' . $ticket->id);
                        $subject = $ticket->subject ?? 'Ticket';
                        $desc = $ticket->description ?? null;
                        $status = $ticket->status ?? 'open';
                        $updated = $ticket->updated_at ?? null;
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/30">
                        <td class="px-4 py-3 align-top">
                            <a href="{{ $showUrl }}" class="block">
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $tn }}</div>
                                <div class="font-semibold text-gray-900 dark:text-gray-50">{{ $subject }}</div>
                                @if($desc)
                                    <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                                        {{ $desc }}
                                    </div>
                                @endif
                            </a>
                        </td>

                        <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                            {{ $ticket->category?->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3 align-top">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] {{ $badgeClass($status) }}">
                                {{ $statusLabel($status) }}
                            </span>
                        </td>

                        <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                            {{ $updated ? \Illuminate\Support\Carbon::parse($updated)->diffForHumans() : '—' }}
                        </td>

                        <td class="px-4 py-3 align-top text-right">
                            <a href="{{ $showUrl }}"
                               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            No tickets yet. Open a ticket and our team will help you.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $tickets->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
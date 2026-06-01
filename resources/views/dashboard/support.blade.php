@extends('layouts.company')

@section('title', 'Support Dashboard')

@section('breadcrumb', 'Support · Dashboard')

@section('content')
@php
    $unassigned = (int)($unassigned ?? 0);
    $mine = (int)($mine ?? 0);
    $awaitingCustomer = (int)($awaitingCustomer ?? 0);

    $statusLabel = function ($st) {
        $st = (string)($st ?? 'open');
        return ucfirst(str_replace('_', ' ', $st));
    };

    $statusPill = function ($st) {
        $st = (string)($st ?? 'open');

        $cls = 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300';
        if ($st === 'awaiting_customer') $cls = 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200';
        elseif ($st === 'awaiting_agent') $cls = 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200';
        elseif ($st === 'new') $cls = 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-800 dark:bg-purple-900/30 dark:text-purple-200';
        elseif ($st === 'resolved') $cls = 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200';
        elseif ($st === 'closed') $cls = 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400';

        return $cls;
    };

    $ticketCustomer = function ($t) {
        return data_get($t, 'customer.name')
            ?? data_get($t, 'user.name')
            ?? data_get($t, 'customer_email')
            ?? 'Customer';
    };

    $ticketSubject = function ($t) {
        return $t->subject ?? $t->title ?? 'Ticket';
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-5 text-xs">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Ticket queue</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Quick view of your workload + recent tickets.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('support.tickets.index') }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                All tickets
            </a>
            <a href="{{ route('support.tickets.unassigned') }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Unassigned
            </a>
            <a href="{{ route('support.tickets.mine') }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Mine
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <a href="{{ route('support.tickets.unassigned') }}"
           class="group rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 hover:bg-gray-50 dark:hover:bg-gray-900/60 transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Unassigned</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-gray-50 mt-1">{{ $unassigned }}</p>
                </div>
                <span class="text-[10px] text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200">
                    View →
                </span>
            </div>
            <p class="text-[10px] text-gray-400 mt-2">Tickets waiting to be picked up.</p>
        </a>

        <a href="{{ route('support.tickets.mine') }}"
           class="group rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 hover:bg-gray-50 dark:hover:bg-gray-900/60 transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Assigned to you</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-gray-50 mt-1">{{ $mine }}</p>
                </div>
                <span class="text-[10px] text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200">
                    View →
                </span>
            </div>
            <p class="text-[10px] text-gray-400 mt-2">Your active queue.</p>
        </a>

        <a href="{{ route('support.tickets.index', ['status' => 'awaiting_customer']) }}"
           class="group rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 hover:bg-gray-50 dark:hover:bg-gray-900/60 transition">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">Awaiting customer reply</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-gray-50 mt-1">{{ $awaitingCustomer }}</p>
                </div>
                <span class="text-[10px] text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200">
                    View →
                </span>
            </div>
            <p class="text-[10px] text-gray-400 mt-2">Support replied; waiting on customer.</p>
        </a>
    </div>

    {{-- Ticket lists --}}
    <div class="grid gap-4 lg:grid-cols-2">

        {{-- My queue --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">Your queue</div>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">Recently updated tickets assigned to you</div>
                </div>
                <a href="{{ route('support.tickets.mine') }}" class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">View all</a>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse(($myTickets ?? []) as $t)
                    <a href="{{ route('support.tickets.show', $t) }}"
                       class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50 truncate">
                                    #{{ $t->id }} · {{ $ticketSubject($t) }}
                                    {{-- {{ $t->displayCustomerName() }} · {{ $t->category?->name ?? '—' }} --}}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                                    {{ $ticketCustomer($t) }} · {{ $t->category?->name ?? '—' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] {{ $statusPill($t->status) }}">
                                    {{ $statusLabel($t->status) }}
                                </span>
                                <span class="text-[10px] text-gray-400">
                                    {{ optional($t->updated_at)->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-6 text-center text-[11px] text-gray-500 dark:text-gray-400">
                        No tickets assigned to you yet.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Unassigned preview --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">Unassigned</div>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">Pick up the next tickets</div>
                </div>
                <a href="{{ route('support.tickets.unassigned') }}" class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">View all</a>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse(($unassignedTickets ?? []) as $t)
                    <a href="{{ route('support.tickets.show', $t) }}"
                       class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] font-medium text-gray-900 dark:text-gray-50 truncate">
                                    #{{ $t->id }} · {{ $ticketSubject($t) }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                                    {{ $ticketCustomer($t) }} · {{ $t->category?->name ?? '—' }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] {{ $statusPill($t->status) }}">
                                    {{ $statusLabel($t->status) }}
                                </span>
                                <span class="text-[10px] text-gray-400">
                                    {{ optional($t->updated_at)->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-6 text-center text-[11px] text-gray-500 dark:text-gray-400">
                        No unassigned tickets right now 🎉
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
@extends('layouts.customer')

@section('title', 'My support tickets')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-8">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
            My support tickets
        </h1>
        <a href="{{ route('tickets.create') }}"
           class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
            Open new ticket
        </a>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Ticket</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Category</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Last updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($tickets as $ticket)
                    <tr>
                        <td class="px-3 py-2">
                            <a href="{{ route('tickets.show', $ticket) }}
                               class="text-gray-900 dark:text-gray-50 hover:underline">
                                <p class="text-gray-500 dark:text-gray-400 hover:underline">#{{ $ticket->ticket_number }}</p>
                                <p class="text-gray-900 dark:text-gray-50 hover:underline">{{ $ticket->subject }}</p> 
                                <p class="text-gray-500 dark:text-gray-400 hover:underline">{{ $ticket->description }}</p>

                            </a>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            {{ $ticket->category?->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                @if($ticket->status === 'open') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                @elseif($ticket->status === 'awaiting_customer') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                @elseif($ticket->status === 'resolved') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                @elseif($ticket->status === 'closed') border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                @endif">
                                {{ str_replace('_',' ', $ticket->status) }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                            {{ optional($ticket->updated_at)->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                            No tickets yet. If you need help, open your first ticket.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $tickets->links() }}
    </div>
</div>
@endsection

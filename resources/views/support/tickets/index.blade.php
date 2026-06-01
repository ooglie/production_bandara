@extends('layouts.company')

@section('title', 'Support Tickets')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Support Tickets</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage customer tickets (Admin / Manager / Support).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('support.tickets.index') }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                All
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

    {{-- Filters --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
        <form method="GET" action="{{ url()->current() }}" class="grid gap-2 sm:grid-cols-4 items-end">
            <div>
                <label class="block text-[10px] text-gray-600 dark:text-gray-300 mb-1">Search</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Ticket # / subject / email"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            </div>

            <div>
                <label class="block text-[10px] text-gray-600 dark:text-gray-300 mb-1">Status</label>
                <select name="status"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <option value="">All</option>
                    @foreach(['awaiting_customer_reply','resolved','closed','open','new'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>
                            {{ str_replace('_',' ', ucfirst($st)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-gray-600 dark:text-gray-300 mb-1">Tag</label>
                <input type="text" name="tag" value="{{ request('tag') }}"
                       placeholder="billing / technical / sales"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Apply
                </button>

                <a href="{{ url()->current() }}"
                   class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- List --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-3 py-2 font-medium">Ticket</th>
                        <th class="px-3 py-2 font-medium">Subject</th>
                        <th class="px-3 py-2 font-medium">Customer</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Assigned</th>
                        <th class="px-3 py-2 font-medium">Updated</th>
                        <th class="px-3 py-2 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse(($tickets ?? []) as $ticket)
                        @php
                            // $subject = $ticket->subject ?? $ticket->title ?? 'Ticket';
                            $status  = $ticket->status ?? 'open';

                            // $customerName = data_get($ticket, 'user.name')
                            //     ?? data_get($ticket, 'customer.name')
                            //     ?? data_get($ticket, 'customer_email')
                            //     ?? 'Customer';

                            $subject = $ticket->displaySubject();
                            $customerName = $ticket->displayCustomerName();
                            
                            $assigneeName = data_get($ticket, 'assignee.name')
                                ?? data_get($ticket, 'assignedTo.name')
                                ?? '—';

                            $updated = $ticket->updated_at ?? null;
                        @endphp
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-3 py-2 whitespace-nowrap">
                                #{{ $ticket->id }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    {{ $subject }}
                                </div>
                                @if(!empty($ticket->category))
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                        {{ is_string($ticket->category) ? $ticket->category : ($ticket->category->name ?? '') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $customerName }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-2 py-0.5 text-[10px]">
                                    {{ ucfirst(str_replace('_',' ', $status)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $assigneeName }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                @if($updated)
                                    {{ \Illuminate\Support\Carbon::parse($updated)->diffForHumans() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                <a href="{{ route('support.tickets.show', $ticket) }}"
                                   class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                No tickets found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (if $tickets is paginator) --}}
        @if(isset($tickets) && is_object($tickets) && method_exists($tickets, 'links'))
            <div class="px-3 py-3 border-t border-gray-200 dark:border-gray-800">
                {{ $tickets->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

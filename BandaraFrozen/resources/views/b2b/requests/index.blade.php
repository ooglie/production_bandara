@extends('layouts.customer')

@section('title', 'B2B Order Requests')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-5 text-xs">
    <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-gray-400">B2B requests</p>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Piece / weight order requests</h1>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Track variable-weight requests submitted for team allocation. Final weights and invoice values are confirmed after allocation.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('b2b.dashboard') }}" class="rounded-full border border-gray-300 px-4 py-2 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Dashboard</a>
            <a href="{{ route('b2b.catalog.index') }}" class="rounded-full bg-gray-900 px-4 py-2 text-[11px] font-medium text-white dark:bg-gray-100 dark:text-gray-900">Explore catalogue</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        @foreach(['open' => 'Open', 'pending_allocation' => 'Pending allocation', 'reviewing' => 'Reviewing', 'partially_allocated' => 'Partially allocated', 'allocated' => 'Allocated', 'finalized' => 'Finalized', 'all' => 'All'] as $key => $label)
            <a href="{{ route('b2b.requests.index', ['status' => $key]) }}" class="rounded-full border px-3 py-1.5 text-[11px] {{ $status === $key ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($requests as $orderRequest)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $orderRequest->request_number ?? ('Request #' . $orderRequest->id) }}</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $orderRequest->status_label }}</div>
                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Submitted {{ optional($orderRequest->created_at)->format('d M Y, h:i A') }}</div>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <span class="inline-flex w-fit rounded-full border border-gray-200 px-2 py-1 text-[10px] text-gray-600 dark:border-gray-700 dark:text-gray-300">{{ $orderRequest->items->count() }} item(s)</span>
                        @if($orderRequest->finalized_order_id)
                            <a href="{{ route('orders.show', $orderRequest->finalized_order_id) }}" class="text-[11px] font-medium text-gray-700 underline dark:text-gray-200">View finalized order</a>
                        @endif
                    </div>
                </div>

                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($orderRequest->items as $item)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ $item->product?->name ?? 'Product removed' }}</div>
                                    @if($item->sellUnit)
                                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $item->sellUnit->display_label }}</div>
                                    @endif
                                    @if($item->customer_note)
                                        <div class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-[11px] text-gray-600 dark:bg-gray-950/50 dark:text-gray-300">{{ $item->customer_note }}</div>
                                    @endif
                                </div>
                                <div class="text-left sm:text-right">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $item->request_summary }}</div>
                                    <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $item->request_mode === 'weight' ? 'Requested weight' : 'Requested pieces' }}</div>
                                    @if($item->tolerance_range)
                                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Target range: {{ $item->tolerance_range }}</div>
                                    @endif
                                    @if($item->quoted_unit_price)
                                        <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Quoted: ₹{{ number_format((float) $item->quoted_unit_price, 2) }} / {{ $item->pricing_unit }}</div>
                                    @endif
                                    @if(in_array($item->status, ['allocated', 'finalized'], true))
                                        <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">
                                            {{ $item->status === 'finalized' ? 'Finalized' : 'Allocated' }}: {{ (int) ($item->allocated_piece_count ?? $item->allocations->whereIn('status', ['reserved', 'sold'])->count()) }} piece(s)
                                            @if($item->allocated_weight_kg)
                                                · {{ rtrim(rtrim(number_format((float) $item->allocated_weight_kg, 3), '0'), '.') }} kg
                                            @endif
                                            @if($item->allocated_subtotal)
                                                · Estimated value ₹{{ number_format((float) $item->allocated_subtotal, 2) }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center text-[11px] text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                No B2B order requests found for this filter.
            </div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
@endsection

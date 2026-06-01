@extends('layouts.company')

@section('title', 'B2B Allocation Queue')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">B2B Allocation Queue</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Review B2B variable-weight requests. Stores/Admin can reserve actual pieces and finalize allocated requests into B2B orders/invoices.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach(['open' => 'Open', 'pending_allocation' => 'Pending', 'reviewing' => 'Reviewing', 'partially_allocated' => 'Partial', 'allocated' => 'Allocated', 'finalized' => 'Finalized', 'all' => 'All'] as $key => $label)
                <a href="{{ route('admin.b2b.order-requests.index', ['status' => $key]) }}" class="rounded-full border px-3 py-1.5 text-[11px] {{ $status === $key ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950/60">
                <tr class="text-left text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">Request</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($requests as $orderRequest)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $orderRequest->request_number ?? ('#' . $orderRequest->id) }}</div>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ optional($orderRequest->created_at)->format('d M Y, h:i A') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $orderRequest->user?->name ?? 'Customer removed' }}</div>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $orderRequest->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3 space-y-2">
                            @foreach($orderRequest->items as $item)
                                <div>
                                    <div class="font-medium text-gray-800 dark:text-gray-100">{{ $item->product?->name ?? 'Product removed' }}</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $item->request_summary }}
                                        @if($item->sellUnit)
                                            · {{ $item->sellUnit->display_label }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full border border-gray-200 px-2 py-1 text-[10px] text-gray-600 dark:border-gray-700 dark:text-gray-300">{{ $orderRequest->status_label }}</span>
                            @if($orderRequest->finalized_order_id)
                                <div class="mt-2 text-[11px] text-emerald-700 dark:text-emerald-300">{{ $orderRequest->finalizedOrder?->order_number ? 'Order ' . $orderRequest->finalizedOrder->order_number : 'Order #' . $orderRequest->finalized_order_id }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.b2b.order-requests.show', $orderRequest) }}" class="inline-flex rounded bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-[11px] text-gray-500 dark:text-gray-400">No B2B order requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $requests->links() }}
</div>
@endsection

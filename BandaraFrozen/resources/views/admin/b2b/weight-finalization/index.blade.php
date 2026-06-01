@extends('layouts.company')

@section('title', 'B2B Weight Finalization')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-gray-400">B2B Operations</p>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Pending weight finalization</h1>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                These are normal B2B orders with weight-based lines. Enter the actual supplied weight to finalize invoice amount.
            </p>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="overflow-hidden rounded border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 text-left text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                <tr>
                    <th class="px-3 py-2">Order</th>
                    <th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2">Pending lines</th>
                    <th class="px-3 py-2">Created</th>
                    <th class="px-3 py-2 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($orders as $order)
                    @php
                        $pendingCount = $order->items->filter(fn($item) => in_array((string)($item->b2b_order_mode ?? ''), ['pieces','weight'], true) && empty($item->actual_weight_kg))->count();
                    @endphp
                    <tr>
                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-50">{{ $order->order_number }}</td>
                        <td class="px-3 py-2">{{ $order->user?->name ?? 'Customer #' . $order->user_id }}</td>
                        <td class="px-3 py-2">{{ $pendingCount }}</td>
                        <td class="px-3 py-2">{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2 text-right">
                            <a href="{{ route('admin.b2b.weight-finalization.show', $order) }}" class="rounded bg-gray-900 px-3 py-1.5 text-white dark:bg-gray-100 dark:text-gray-900">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500">No B2B orders need weight finalization.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection

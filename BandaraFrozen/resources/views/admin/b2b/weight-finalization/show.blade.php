@extends('layouts.company')

@section('title', 'Finalize B2B Weight')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.b2b.weight-finalization.index') }}" class="text-[11px] text-gray-500 underline">← Back</a>
            <h1 class="mt-2 text-xl font-semibold text-gray-900 dark:text-gray-50">{{ $order->order_number }}</h1>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Customer: {{ $order->user?->name ?? 'Customer #' . $order->user_id }} · Payment: {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'razorpay')) }}
            </p>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $errors->first() }}</div>
    @endif

    @if($pendingItems->isEmpty())
        <div class="rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            This order has no pending weight lines.
        </div>
    @else
        <form method="POST" action="{{ route('admin.b2b.weight-finalization.finalize', $order) }}" class="space-y-4">
            @csrf

            <div class="overflow-hidden rounded border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-gray-50 text-left text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">Item</th>
                            <th class="px-3 py-2">Requested</th>
                            <th class="px-3 py-2">Price</th>
                            <th class="px-3 py-2">Actual pieces</th>
                            <th class="px-3 py-2">Actual kg</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($pendingItems as $item)
                            @php
                                $mode = $item->b2b_order_mode;
                                $requested = $mode === 'pieces'
                                    ? ((int)($item->requested_piece_count ?? $item->quantity)) . ' pieces'
                                    : rtrim(rtrim(number_format((float)($item->requested_weight_kg ?? $item->quantity), 3), '0'), '.') . ' kg approx';
                            @endphp
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ $item->product_name }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $item->sellUnit?->display_label ?? $item->sku }}</div>
                                </td>
                                <td class="px-3 py-2">{{ $requested }}</td>
                                <td class="px-3 py-2">₹{{ number_format((float)$item->unit_price, 2) }}/kg</td>
                                <td class="px-3 py-2">
                                    <input type="number" min="1" step="1" name="items[{{ $item->id }}][actual_piece_count]" value="{{ old('items.' . $item->id . '.actual_piece_count', $item->requested_piece_count ?: (int)$item->quantity ?: 1) }}" class="w-24 rounded border border-gray-300 bg-white px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0.001" step="0.001" name="items[{{ $item->id }}][actual_weight_kg]" value="{{ old('items.' . $item->id . '.actual_weight_kg') }}" class="w-28 rounded border border-gray-300 bg-white px-2 py-1.5 dark:border-gray-700 dark:bg-gray-950" required>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                Finalizing updates the order and invoice totals. For Pay Later orders, stock is committed after finalization. For Pay Now orders, stock remains committed only after payment success.
            </div>

            <button class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Finalize invoice weight</button>
        </form>
    @endif
</div>
@endsection

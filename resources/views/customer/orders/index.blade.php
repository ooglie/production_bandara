@extends('layouts.customer')

@section('title', 'My orders')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                My orders
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                View your past orders, status and download invoices.
            </p>
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="rounded border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 text-xs text-gray-500 dark:text-gray-400">
            You don’t have any orders yet.
        </div>
    @else
        <div class="overflow-x-auto border border-gray-200 dark:border-gray-800 rounded-lg text-xs bg-white dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-950">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Order</th>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Total (₹)</th>
                        <th class="px-3 py-2 text-right">Invoice</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($orders as $order)
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-900 dark:text-gray-50">
                                <div class="font-mono text-xs">
                                    {{ $order->order_number ?? ('#'.$order->id) }}
                                </div>
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                {{ $order->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                                @php
                                    $status = strtolower($order->status ?? '');
                                @endphp
                                @if($status === 'processing')
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-2 py-0.5 text-[11px]">
                                        Processing
                                    </span>
                                @elseif($status === 'shipped')
                                    <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 text-[11px]">
                                        Shipped
                                    </span>
                                @elseif($status === 'delivered')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[11px]">
                                        Delivered
                                    </span>
                                @elseif($status === 'cancelled')
                                    <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-2 py-0.5 text-[11px]">
                                        Cancelled
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 px-2 py-0.5 text-[11px]">
                                        {{ $order->status ?? 'Unknown' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-900 dark:text-gray-50">
                                {{ number_format($order->grand_total ?? 0, 2) }}
                            </td>
                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                @if(optional($order->invoice)->status ?? false)
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-[11px]">
                                            {{ $order->invoice->invoice_number ?? 'Invoice' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                            @if($order->invoice->status === 'paid')
                                                bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                            @elseif($order->invoice->status === 'past_due')
                                                bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                            @elseif($order->invoice->status === 'due')
                                                bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                            @else
                                                bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                            @endif
                                        ">
                                            {{ ucfirst($order->invoice->status ?? '') }}
                                            {{-- {{ $order->invoice->status ?? 'Status' }} --}}
                                        </span>
                                        
                                    </div>
                                @else
                                    <span class="text-[11px] text-gray-400">Not generated yet</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 align-top text-right">
                                <a href="{{ route('orders.show', $order) }}"
                                   class="text-[11px] text-gray-600 dark:text-gray-300 underline">
                                    View
                                </a>
                                <br>
                                <a href="{{ route('orders.invoice', $order) }}"
                                    class="text-[11px] text-gray-600 dark:text-gray-300 underline">
                                    Download
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection

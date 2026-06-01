@extends('layouts.customer')

@section('title', 'Invoice')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 text-xs">
    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-5 py-5 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                    Tax Invoice
                </h1>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    Frozen – Bandara by Maytira
                </p>
            </div>
            <div class="text-right text-[11px] text-gray-700 dark:text-gray-300">
                <div>Invoice no: {{ $invoice->invoice_number ?? $invoice->id }}</div>
                <div>Date: {{ ($invoice->created_at ?? now())->format('d M Y') }}</div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 text-[11px] text-gray-700 dark:text-gray-300">
            <div>
                <div class="font-semibold mb-1">Billed to</div>
                {{-- Replace with real billing data once you wire addresses --}}
                <div>{{ auth()->user()->name }}</div>
                <div>{{ auth()->user()->email }}</div>
            </div>
            <div>
                <div class="font-semibold mb-1">Order details</div>
                <div>Order: {{ $order->order_number ?? ('#'.$order->id) }}</div>
                <div>Date: {{ $order->created_at->format('d M Y') }}</div>
                <div>Status: {{ ucfirst($order->status ?? 'pending') }}</div>
            </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800 pt-3">
            <table class="w-full text-[11px] text-gray-700 dark:text-gray-300">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="py-1 text-left">Sr. No.</th>
                        <th class="py-1 text-left">Item</th>
                        <th class="py-1 text-right">Qty</th>
                        <th class="py-1 text-right">Price (₹)</th>
                        <th class="py-1 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items ?? [] as $item)
                        @php
                            static $count = 0;
                            $count++;
                        @endphp
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-1">
                                {{ $count }}
                            </td>
                            <td class="py-1">
                                {{ $item->product_name ?? 'Item' }}
                            </td>
                            <td class="py-1 text-right">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-1 text-right">
                                {{ number_format($item->unit_price ?? 0, 2) }}
                            </td>
                            <td class="py-1 text-right">
                                {{ number_format($item->total ?? ($item->quantity * $item->unit_price), 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-800 pt-3 space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span>₹{{ number_format($order->subtotal ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Tax (GST)</span>
                <span>₹{{ number_format($order->tax_total ?? 0, 2) }}</span>
            </div>
            @if(!empty($order->discount_total))
                <div class="flex justify-between">
                    <span>Discount</span>
                    <span>- ₹{{ number_format($order->discount_total, 2) }}</span>
                </div>
            @endif
            <div class="flex justify-between font-semibold text-gray-900 dark:text-gray-50">
                <span>Grand total</span>
                <span>₹{{ number_format($order->grand_total ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection

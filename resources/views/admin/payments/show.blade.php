@extends('layouts.company')

@section('title', 'Payment #' . $payment->id)

@section('content')
@php
    $order   = $payment->order;
    $invoice = $order?->invoice;
@endphp

<div class="max-w-5xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Payment #{{ $payment->id }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Order #{{ $order->order_number ?? '—' }}
                @if($order && $order->user)
                    · Customer: {{ $order->user->name }} ({{ $order->user->email }})
                @endif
            </p>
        </div>
        <a href="{{ route('admin.payments.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 underline">
            Back to payments
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr,1.4fr]">
        {{-- Left: payment data --}}
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Gateway details
                </h2>

                <dl class="space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                    <div class="flex justify-between">
                        <dt>Method</dt>
                        <dd>{{ ucfirst($payment->method) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Status</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                @if($payment->status === 'captured')
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @elseif($payment->status === 'failed')
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                @elseif($payment->status === 'refunded')
                                    bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                                @elseif($payment->status === 'authorized')
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @else
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                @endif
                            ">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Transaction ID</dt>
                        <dd>{{ $payment->transaction_id ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Order amount</dt>
                        <dd>₹{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Created at</dt>
                        <dd>{{ $payment->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt>Paid at</dt>
                        <dd>{{ $payment->paid_at ? $payment->paid_at->format('d M Y H:i') : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Raw payment data (debug)
                </h2>

                @php
                    $data = $payment->payment_data ?? [];
                @endphp

                @if(empty($data))
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        No additional payment data stored.
                    </p>
                @else
                    <pre class="text-[10px] text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/60 rounded p-2 overflow-x-auto">
{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                    </pre>
                @endif
            </div>
        </div>

        {{-- Right: linked order + invoice --}}
        <div class="space-y-4">
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Related order
                </h2>

                @if($order)
                    <dl class="space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between">
                            <dt>Order #</dt>
                            <dd>{{ $order->order_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Status</dt>
                            <dd>{{ ucfirst($order->status) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Payment status</dt>
                            <dd>{{ ucfirst($order->payment_status) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Grand total</dt>
                            <dd>₹{{ number_format($order->grand_total, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Placed at</dt>
                            <dd>{{ optional($order->placed_at)->format('d M Y H:i') ?? $order->created_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Order not found (it may have been deleted).
                    </p>
                @endif
            </div>

            <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 px-4 py-4 space-y-3">
                <h2 class="text-[11px] font-semibold text-gray-800 dark:text-gray-100">
                    Related invoice
                </h2>

                @if($invoice)
                    <dl class="space-y-1 text-[11px] text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between">
                            <dt>Invoice #</dt>
                            <dd>{{ $invoice->invoice_number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Status</dt>
                            <dd>{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Invoice date</dt>
                            <dd>{{ optional($invoice->invoice_date)->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Grand total</dt>
                            <dd>₹{{ number_format($invoice->grand_total, 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>PDF</dt>
                            <dd>
                                @if($invoice->pdf_path)
                                    <a href="{{ asset('storage/' . $invoice->pdf_path) }}"
                                       class="text-[11px] text-gray-700 dark:text-gray-200 underline"
                                       target="_blank">
                                        Download PDF
                                    </a>
                                @else
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">Not generated</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        No invoice associated with this order.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

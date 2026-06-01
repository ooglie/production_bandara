@extends('layouts.company')

@section('title', 'Payments')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Payments
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                View all payment attempts and captured transactions (Razorpay and other methods).
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 text-xs">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Order # or Txn ID"
                class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            <select
                name="method"
                class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
                <option value="">Method</option>
                @foreach(['razorpay'] as $method)
                    <option value="{{ $method }}" @selected(request('method') === $method)>
                        {{ ucfirst($method) }}
                    </option>
                @endforeach
            </select>
            <select
                name="status"
                class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
                <option value="">Status</option>
                @foreach(['created', 'authorized', 'captured', 'failed', 'refunded'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            <button
                class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5">
                Filter
            </button>
        </form>
    </div>

    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-[11px] text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2.5">ID</th>
                    <th class="px-3 py-2.5">Order #</th>
                    <th class="px-3 py-2.5">Customer</th>
                    <th class="px-3 py-2.5">Method</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">Txn ID</th>
                    <th class="px-3 py-2.5">Created</th>
                    <th class="px-3 py-2.5 text-right">Amount</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            #{{ $payment->id }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $payment->order->order_number ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $payment->order->user->name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ ucfirst($payment->method) }}
                        </td>
                        <td class="px-3 py-2 align-top">
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
                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="text-[11px] text-gray-700 dark:text-gray-200">
                                {{ $payment->transaction_id ?: '—' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">
                            {{ $payment->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            ₹{{ number_format($payment->amount, 2) }}
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}"
                               class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No payments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            {{ $payments->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection

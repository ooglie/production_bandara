@extends('layouts.company')

@section('title', 'Accountant dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Finance dashboard
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Revenue, customer invoices and vendor balances.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoices.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Customer invoices
            </a>
            <a href="{{ route('admin.vendor-invoices.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Vendor invoices
            </a>
            <a href="{{ route('admin.payments.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Customer payments
            </a>
            <a href="{{ route('admin.vendor-payments.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Vendor payments
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Revenue today
            </p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                ₹{{ number_format($customerRevenueToday, 2) }}
            </p>
            <p class="mt-1 text-[10px] text-gray-400">
                Paid orders placed today.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Revenue this month
            </p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                ₹{{ number_format($customerRevenueThisMonth, 2) }}
            </p>
            <p class="mt-1 text-[10px] text-gray-400">
                Paid orders since the 1st of this month.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Customer outstanding
            </p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                ₹{{ number_format($customerOutstanding, 2) }}
            </p>
            <p class="mt-1 text-[10px] text-gray-400">
                Pending / due / past due invoices.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Vendor outstanding
            </p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-50">
                ₹{{ number_format($vendorOutstanding, 2) }}
            </p>
            <p class="mt-1 text-[10px] text-gray-400">
                Unpaid vendor invoices.
            </p>
        </div>
    </div>

    {{-- Main grid --}}
    <div class="grid gap-3 lg:grid-cols-[2fr,1.3fr]">
        {{-- LEFT: invoices --}}
        <div class="space-y-3">
            {{-- Recent customer invoices --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent customer invoices
                    </p>
                    <a href="{{ route('admin.invoices.index') }}"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recentInvoices as $inv)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                    <td class="px-3 py-2">
                                        <a href="{{ route('admin.invoices.show', $inv) }}"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            {{ $inv->invoice_number ?? ('INV-'.$inv->id) }}
                                        </a>
                                        <div class="text-[10px] text-gray-400">
                                            Order: 
                                            @if($inv->order)
                                                <a href="{{ route('admin.orders.show', $inv->order) }}"
                                                   class="hover:underline">
                                                    {{ $inv->order->order_number }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        {{ $inv->order?->user?->name ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        ₹{{ number_format($inv->grand_total, 2) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                            @if($inv->status === 'paid') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                            @elseif($inv->status === 'past_due') border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200
                                            @elseif($inv->status === 'due') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                            @else border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $inv->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        {{ $inv->due_date ? $inv->due_date->format('d M Y') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No invoices yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT: payments --}}
        <div class="space-y-3">
            {{-- Customer payments --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent customer payments
                    </p>
                    <a href="{{ route('admin.payments.index') }}"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                    @forelse($recentCustomerPayments as $p)
                        <div class="flex items-center justify-between">
                            <div>
                                <p>
                                    ₹{{ number_format($p->amount, 2) }}
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                        {{ strtoupper($p->method) }} · {{ ucfirst($p->status) }}
                                    </span>
                                </p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $p->paid_at ? $p->paid_at->format('d M Y, H:i') : 'Not captured yet' }}
                                </p>
                            </div>
                            <a href="{{ route('admin.payments.show', $p) }}"
                               class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                                Details
                            </a>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No recent customer payments.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Vendor payments --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent vendor payments
                    </p>
                    <a href="{{ route('admin.vendor-payments.index') }}"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                    @forelse($recentVendorPayments as $p)
                        <div class="flex items-center justify-between">
                            <div>
                                <p>
                                    ₹{{ number_format($p->amount, 2) }}
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                        {{ $p->vendor?->name ?? 'Vendor #'.$p->vendor_id }}
                                    </span>
                                </p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $p->payment_date ? $p->payment_date->format('d M Y') : '—' }}
                                    @if($p->payment_method)
                                        · {{ $p->payment_method }}
                                    @endif
                                    @if($p->invoice)
                                        · Invoice {{ $p->invoice->invoice_number }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            No recent vendor payments.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Overdue summary --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Overdue snapshot
                </p>
                <div class="mt-2 space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between">
                        <span>Overdue customer invoices</span>
                        <span class="font-semibold">{{ $customerOverdueCount }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                        <span>Customer outstanding</span>
                        <span>₹{{ number_format($customerOutstanding, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                        <span>Vendor outstanding</span>
                        <span>₹{{ number_format($vendorOutstanding, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

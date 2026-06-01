{{-- resources/views/admin/vendors/show.blade.php --}}
@extends('layouts.company')

@section('title', 'Vendor ' . $vendor->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 text-xs space-y-4">
    <div class="flex items-center justify-between gap-3 mb-2">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Vendor: {{ $vendor->name }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                @if($vendor->code)
                    Code: <span class="font-mono">{{ $vendor->code }}</span>
                    ·
                @endif
                Created {{ optional($vendor->created_at)->format('d M Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.vendors.index') }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back to vendors
            </a>
            <a href="{{ route('admin.vendors.edit', $vendor) }}"
               class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Edit vendor
            </a>
            <a href="{{ route('admin.vendor-invoices.create', ['vendor_id' => $vendor->id]) }}"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                New vendor invoice
            </a>
            <a href="{{ route('admin.vendor-payments.create', ['vendor_id' => $vendor->id]) }}"
               class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Record payment
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @php
        $invoices = $vendor->invoices ?? collect();
        $payments = $vendor->payments ?? collect();

        $totalInvoiced = (float) $invoices->sum('total_amount');
        $totalPaid     = (float) $payments->sum('amount');
        $balance       = max(0, $totalInvoiced - $totalPaid);
    @endphp

    <div class="grid gap-3 lg:grid-cols-[2fr,1.3fr]">
        {{-- LEFT COLUMN: details + invoices --}}
        <div class="space-y-3">
            {{-- Vendor details --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Vendor details
                    </p>
                    <span class="inline-flex items-center rounded-full border px-3 py-0.5 text-[10px]
                        @if($vendor->is_active ?? true)
                            border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                        @else
                            border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                        @endif">
                        {{ ($vendor->is_active ?? true) ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="grid gap-3 md:grid-cols-2 text-[11px] text-gray-700 dark:text-gray-200 pt-1">
                    <div class="space-y-1">
                        @if($vendor->contact_name)
                            <p>
                                Contact:
                                <span class="font-medium">{{ $vendor->contact_name }}</span>
                            </p>
                        @endif
                        @if($vendor->email)
                            <p>
                                Email:
                                <span class="font-medium">{{ $vendor->email }}</span>
                            </p>
                        @endif
                        @if($vendor->phone)
                            <p>
                                Phone:
                                <span class="font-medium">{{ $vendor->phone }}</span>
                            </p>
                        @endif
                        @if($vendor->gst_number)
                            <p>
                                GSTIN:
                                <span class="font-mono">{{ $vendor->gst_number }}</span>
                            </p>
                        @endif
                        @if($vendor->fssai_number)
                            <p>
                                FSSAI:
                                <span class="font-mono">{{ $vendor->fssai_number }}</span>
                            </p>
                        @endif
                    </div>

                    <div class="space-y-1 text-[10px] text-gray-600 dark:text-gray-300">
                        <p class="font-medium text-[11px] text-gray-700 dark:text-gray-200">
                            Address
                        </p>
                        @if($vendor->address_line1 || $vendor->address_line2 || $vendor->city || $vendor->state || $vendor->pincode)
                            <p>
                                @if($vendor->address_line1)
                                    {{ $vendor->address_line1 }}<br>
                                @endif
                                @if($vendor->address_line2)
                                    {{ $vendor->address_line2 }}<br>
                                @endif
                                @if($vendor->city || $vendor->state || $vendor->pincode)
                                    {{ $vendor->city }} @if($vendor->city && ($vendor->state || $vendor->pincode)), @endif
                                    {{ $vendor->state }} {{ $vendor->pincode }}<br>
                                @endif
                                {{ $vendor->country ?? 'India' }}
                            </p>
                        @else
                            <p class="text-gray-400">
                                No address saved yet.
                            </p>
                        @endif
                    </div>
                </div>

                @if(!empty($vendor->notes))
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                        <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50 mb-0.5">
                            Internal notes
                        </p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 whitespace-pre-line">
                            {{ $vendor->notes }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Recent invoices --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent invoices
                    </p>
                    <a href="{{ route('admin.vendor-invoices.index', ['vendor_id' => $vendor->id]) }}"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse(($invoices)->sortByDesc('invoice_date')->take(5) as $inv)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('admin.vendor-invoices.show', $inv) }}"
                                           class="text-gray-900 dark:text-gray-50 hover:underline">
                                            {{ $inv->invoice_number }}
                                        </a>
                                        <div class="text-[10px] text-gray-400">
                                            #{{ $inv->id }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        ₹{{ number_format($inv->total_amount, 2) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                            @if($inv->status === 'pending') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                            @elseif($inv->status === 'partially_paid') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                            @elseif($inv->status === 'paid') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                            @elseif($inv->status === 'cancelled') border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                            @endif">
                                            {{ ucfirst(str_replace('_',' ',$inv->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        {{ optional($inv->invoice_date)->format('d M Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No invoices for this vendor yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: summary + payments + products supplied --}}
        <div class="space-y-3">
            {{-- Financial summary --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-1">
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Financial summary
                </p>

                <div class="space-y-1 text-[11px] text-gray-700 dark:text-gray-200">
                    <div class="flex items-center justify-between">
                        <span>Total invoiced</span>
                        <span>₹{{ number_format($totalInvoiced, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total paid</span>
                        <span>₹{{ number_format($totalPaid, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between font-semibold border-t border-dashed border-gray-200 dark:border-gray-700 pt-1 mt-1">
                        <span>Outstanding balance</span>
                        <span>₹{{ number_format($balance, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400 pt-1">
                        <span>Invoices count</span>
                        <span>{{ $invoices->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Recent payments --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Recent payments
                    </p>
                    <a href="{{ route('admin.vendor-payments.index', ['vendor_id' => $vendor->id]) }}"
                       class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                        View all
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-[11px]">
                        <thead class="bg-gray-50 dark:bg-gray-950/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Method</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Ref</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse(($payments)->sortByDesc('payment_date')->take(5) as $pay)
                                <tr>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        {{ $pay->payment_date?->format('d M Y') }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                        @if($pay->invoice)
                                            {{ $pay->invoice->invoice_number }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        {{ $pay->payment_method ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                        {{ $pay->reference_number ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                        ₹{{ number_format($pay->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                        No payments recorded for this vendor yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Products supplied --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        Products supplied
                    </p>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                        Based on vendor invoices
                    </span>
                </div>

                @if(isset($suppliedProducts) && $suppliedProducts->isNotEmpty())
                    <ul class="space-y-1">
                        @foreach($suppliedProducts->take(10) as $product)
                            <li class="flex items-center justify-between text-[11px] text-gray-700 dark:text-gray-200">
                                <div>
                                    <span class="font-medium">{{ $product->name }}</span>
                                    @if($product->sku ?? false)
                                        <span class="text-[10px] text-gray-400">
                                            ({{ $product->sku }})
                                        </span>
                                    @endif
                                </div>
                                <a href="{{ route('admin.products.edit', $product) }}"
                                   class="text-[10px] text-gray-500 dark:text-gray-400 hover:underline">
                                    View product
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if($suppliedProducts->count() > 10)
                        <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                            + {{ $suppliedProducts->count() - 10 }} more product(s) supplied by this vendor.
                        </p>
                    @endif
                @else
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        No products recorded yet for this vendor in purchase invoices.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

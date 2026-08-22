@extends('layouts.company')

@section('title', 'Vendor outstanding summary')

@section('content')
@php
    $has = fn(string $r) => \Illuminate\Support\Facades\Route::has($r);

    $backUrl = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Outstanding by vendor</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Total outstanding across vendors and breakdown per vendor.
            </p>
        </div>

        <a href="{{ $backUrl }}"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="text-[11px] text-gray-500 dark:text-gray-400">Total outstanding (all vendors)</div>
        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-50">
            ₹{{ number_format((float)$totalOutstandingAllVendors, 2) }}
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Vendor</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Invoices</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Outstanding</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($rows as $r)
                @php
                    $vendorInvoicesUrl = \Illuminate\Support\Facades\Route::has('admin.vendor-invoices.index')
                        ? route('admin.vendor-invoices.index', ['vendor_id' => $r->vendor_id])
                        : '#';
                @endphp
                <tr>
                    <td class="px-3 py-2 text-gray-900 dark:text-gray-50 font-medium">
                        {{ $r->vendor_name }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                        {{ (int)$r->inv_count }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                        ₹{{ number_format((float)$r->inv_total, 2) }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                        ₹{{ number_format((float)$r->paid_total, 2) }}
                    </td>
                    <td class="px-3 py-2 text-right">
                        <span class="font-semibold text-gray-900 dark:text-gray-50">
                            ₹{{ number_format((float)$r->outstanding_total, 2) }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <a href="{{ $vendorInvoicesUrl }}"
                           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                            View invoices
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No outstanding found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
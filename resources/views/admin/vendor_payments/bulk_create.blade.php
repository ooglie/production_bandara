@extends('layouts.company')

@section('title', 'Bulk vendor payment')

@section('content')
@php
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);

    $storeUrl = $has('admin.vendor-payments.store') ? route('admin.vendor-payments.store') : '#';
    $backUrl  = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();

    $vendorName = $selectedVendor?->name ?? 'Vendor';
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Bulk payment</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Paying invoices for <span class="font-semibold">{{ $vendorName }}</span>. Default payment = outstanding (editable).
            </p>
        </div>

        <a href="{{ $backUrl }}"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
        @csrf

        <input type="hidden" name="vendor_id" value="{{ $selectedVendor?->id }}">
        @foreach($invoiceIds as $id)
            <input type="hidden" name="invoice_ids[]" value="{{ $id }}">
        @endforeach

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Payment date</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Payment method</label>
                    <input type="text" name="payment_method" value="{{ old('payment_method') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs"
                           placeholder="Bank / UPI / Cash">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Reference</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs"
                           placeholder="UTR / Cheque #">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs">
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Outstanding</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Pay now</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @php $sum = 0.0; @endphp
                @foreach($rows as $r)
                    @php
                        $inv = $r['invoice'];
                        $out = (float)$r['outstanding'];
                        $defaultPay = old('amounts.' . $inv->id, $r['default_pay']);
                        $sum += (float)$defaultPay;
                    @endphp
                    <tr>
                        <td class="px-3 py-2">
                            <div class="text-gray-900 dark:text-gray-50 font-medium">
                                {{ $inv->invoice_number ?? ('#'.$inv->id) }}
                            </div>
                            <div class="text-[10px] text-gray-400">#{{ $inv->id }}</div>
                        </td>
                        <td class="px-3 py-2 text-right">₹{{ number_format((float)$r['total'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format((float)$r['paid'], 2) }}</td>
                        <td class="px-3 py-2 text-right">₹{{ number_format($out, 2) }}</td>
                        <td class="px-3 py-2 text-right">
                            <input type="number" step="0.01" min="0" max="{{ $out }}"
                                   name="amounts[{{ $inv->id }}]"
                                   value="{{ $defaultPay }}"
                                   class="w-28 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] text-right">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                Leave “Pay now” as 0 to skip an invoice.
            </div>

            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Record bulk payment
            </button>
        </div>
    </form>
</div>
@endsection
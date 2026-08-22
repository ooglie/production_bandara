@extends('layouts.company')

@section('title', 'New vendor payment')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50 mb-2">
        New vendor payment
    </h1>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800 mb-2">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.vendor-payments.store') }}" class="space-y-3">
        @csrf

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-2">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Vendor
                    </label>
                    <select name="vendor_id"
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        <option value="">Select vendor…</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}"
                                @selected(old('vendor_id', $selectedVendor?->id ?? null) == $v->id)>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Invoice (optional)
                    </label>
                    <select name="vendor_invoice_id"
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        <option value="">No specific invoice</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}"
                                @selected(old('vendor_invoice_id', $selectedInvoice?->id ?? null) == $inv->id)>
                                {{ $inv->invoice_number }} – {{ $inv->vendor?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Amount
                    </label>
                    <input type="number" step="0.01" min="0.01" name="amount"
                           value="{{ old('amount') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Payment date
                    </label>
                    <input type="date" name="payment_date"
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Method
                    </label>
                    <input type="text" name="payment_method"
                           value="{{ old('payment_method') }}"
                           placeholder="NEFT, RTGS, UPI…"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Reference number
                    </label>
                    <input type="text" name="reference_number"
                           value="{{ old('reference_number') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Notes
                    </label>
                    <input type="text" name="notes"
                           value="{{ old('notes') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('admin.vendor-payments.index') }}"
               class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save payment
            </button>
        </div>
    </form>
</div>
@endsection

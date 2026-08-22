@extends('layouts.company')

@section('title', 'New vendor payment')

@section('content')
@php
    $selectedOutstanding = $selectedInvoice ? (float)$selectedInvoice->balance_amount : null;
@endphp
<div class="max-w-4xl mx-auto px-4 py-6 text-xs space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">New vendor payment</h1>
        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
            Payments are checked against the invoice's adjusted payable after posted supplier credits and debits.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.vendor-payments.store') }}" class="space-y-3">
        @csrf

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3 space-y-3">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor</label>
                    <select name="vendor_id" required
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        <option value="">Select vendor…</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected(old('vendor_id', $selectedVendor?->id ?? null) == $v->id)>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice (optional)</label>
                    <select name="vendor_invoice_id" id="vendor-invoice-select"
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                        <option value="" data-outstanding="">No specific invoice</option>
                        @foreach($invoices as $inv)
                            @php
                                $outstanding = (float)$inv->balance_amount;
                                $adjusted = (float)$inv->adjusted_total_amount;
                                $paid = (float)$inv->paid_amount;
                            @endphp
                            <option value="{{ $inv->id }}"
                                    data-outstanding="{{ number_format($outstanding, 2, '.', '') }}"
                                    @selected(old('vendor_invoice_id', $selectedInvoice?->id ?? null) == $inv->id)>
                                {{ $inv->invoice_number }} · {{ $inv->vendor?->name }} · Outstanding ₹{{ number_format($outstanding, 2) }} (Adjusted ₹{{ number_format($adjusted, 2) }}, Paid ₹{{ number_format($paid, 2) }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[10px] text-gray-400">Only non-cancelled invoices are listed. The server prevents overpayment.</p>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="vendor-payment-amount" required
                           value="{{ old('amount', $selectedOutstanding !== null && $selectedOutstanding > 0 ? number_format($selectedOutstanding, 2, '.', '') : '') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <p id="vendor-payment-limit" class="mt-1 text-[10px] text-gray-400"></p>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Payment date</label>
                    <input type="date" name="payment_date" required
                           value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Method</label>
                    <input type="text" name="payment_method" value="{{ old('payment_method') }}"
                           placeholder="NEFT, RTGS, IMPS, cheque…"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Reference number</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('admin.vendor-payments.index') }}" class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save payment
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const select = document.getElementById('vendor-invoice-select');
    const amount = document.getElementById('vendor-payment-amount');
    const limit = document.getElementById('vendor-payment-limit');
    if (!select || !amount || !limit) return;

    function refreshLimit(fillAmount) {
        const option = select.options[select.selectedIndex];
        const raw = option?.dataset?.outstanding || '';
        const outstanding = raw === '' ? null : Number(raw);

        if (outstanding === null || Number.isNaN(outstanding)) {
            amount.removeAttribute('max');
            limit.textContent = 'A payment without an invoice is recorded against the vendor account only.';
            return;
        }

        amount.max = outstanding.toFixed(2);
        limit.textContent = 'Maximum adjusted outstanding: ₹' + outstanding.toFixed(2);

        if (fillAmount && (!amount.value || Number(amount.value) <= 0)) {
            amount.value = outstanding.toFixed(2);
        }
    }

    select.addEventListener('change', function () { refreshLimit(true); });
    refreshLimit(false);
})();
</script>
@endsection

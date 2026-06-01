@extends('layouts.company')

@section('title', 'Invoices')

@php
    $availableMonths = $availableMonths ?? collect();
@endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Invoices
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Filter by customer, month and status. Use bulk status to override, or choose "Paid" to record a payment.
            </p>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters row (GET) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
        <form method="GET" action="{{ route('admin.invoices.index') }}"
              class="flex flex-wrap items-center gap-2 text-[11px]">
            <span class="text-gray-600 dark:text-gray-300">
                Filter:
            </span>

            {{-- Customer filter --}}
            <select name="customer_id"
                    class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">All customers</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>
                        {{ $customer->name }} @if($customer->email) ({{ $customer->email }}) @endif
                    </option>
                @endforeach
            </select>

            {{-- Month filter (only months that have invoices) --}}
            <select name="month"
                    class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">All months</option>
                @foreach($availableMonths as $ym)
                    @php
                        try {
                            $label = \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M Y');
                        } catch (\Exception $e) {
                            $label = $ym;
                        }
                    @endphp
                    <option value="{{ $ym }}" @selected(request('month') === $ym)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            {{-- Status filter --}}
            <select name="status"
                    class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                <option value="">All statuses</option>
                <option value="pending"      @selected(request('status') === 'pending')>Pending</option>
                <option value="due"          @selected(request('status') === 'due')>Due</option>
                <option value="part_payment" @selected(request('status') === 'part_payment')>Part payment</option>
                <option value="past_due"     @selected(request('status') === 'past_due')>Past due</option>
                <option value="paid"         @selected(request('status') === 'paid')>Paid</option>
            </select>

            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                Apply
            </button>

            @if(request()->hasAny(['customer_id', 'month', 'status']))
                <a href="{{ route('admin.invoices.index') }}"
                   class="text-[10px] text-gray-400 hover:underline">
                    Reset
                </a>
            @endif
        </form>
    </div>

    
    {{-- Bulk form (POST) --}}
    <form method="POST" id="invoice-bulk-form" action="{{ route('admin.invoices.bulk-status') }}">
        @csrf

        @can('manage sales')
        {{-- Bulk status control --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                <span class="text-gray-600 dark:text-gray-300">
                    Bulk status:
                </span>

                <select name="status"
                        id="bulk-status"
                        class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <option value="pending">Pending</option>
                    <option value="due">Due</option>
                    <option value="part_payment">Part payment</option>
                    <option value="past_due">Past due</option>
                    <option value="paid">Paid (will open payment form)</option>
                </select>

                <button type="submit"
                        id="bulk-submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Update selected
                </button>
            </div>

            <p class="text-[10px] text-gray-400">
                To record a payment (cash / POS / cheque / etc.), select invoices, choose <strong>Paid</strong> and click "Update selected".
            </p>
        </div>
        @endcan
        {{-- Table --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-3 py-2 text-left">
                            <input type="checkbox" id="select-all-invoices"
                                   class="h-3 w-3 rounded border-gray-300 dark:border-gray-600">
                        </th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Paid</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Balance</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($invoices as $invoice)
                        @php
                            $paid    = $invoice->amount_paid ?? 0;
                            $balance = $invoice->balance_amount ?? ($invoice->grand_total - $paid);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-3 py-2">
                                <input type="checkbox"
                                       name="invoice_ids[]"
                                       value="{{ $invoice->id }}"
                                       class="invoice-checkbox h-3 w-3 rounded border-gray-300 dark:border-gray-600">
                            </td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.invoices.show', $invoice) }}"
                                   class="text-gray-900 dark:text-gray-50 hover:underline">
                                    {{ $invoice->invoice_number ?? ('INV-'.$invoice->id) }}
                                </a>
                                <div class="text-[10px] text-gray-400">
                                    #{{ $invoice->id }}
                                </div>
                            </td>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                {{ $invoice->order?->user?->name ?? '—' }}
                                @if($invoice->order?->user?->email)
                                    <div class="text-[10px] text-gray-400">
                                        {{ $invoice->order->user->email }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px]
                                    @if($invoice->status === 'paid') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                                    @elseif($invoice->status === 'part_payment') border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/30 dark:text-sky-200
                                    @elseif($invoice->status === 'past_due') border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200
                                    @elseif($invoice->status === 'due') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                                    @else border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                ₹{{ number_format($invoice->grand_total, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                                ₹{{ number_format($paid, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                                ₹{{ number_format($balance, 2) }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                                @if($invoice->order)
                                    <a href="{{ route('admin.orders.show', $invoice->order) }}"
                                       class="text-gray-700 dark:text-gray-200 hover:underline">
                                        {{ $invoice->order->order_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $invoices->links() }}
        </div>
    </form>
</div>

<script>
    (function () {
        const selectAll = document.getElementById('select-all-invoices');
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        const bulkForm = document.getElementById('invoice-bulk-form');
        const bulkStatus = document.getElementById('bulk-status');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        if (bulkForm && bulkStatus) {
            bulkForm.addEventListener('submit', function (e) {
                // Ensure at least one invoice selected
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                if (!anyChecked) {
                    e.preventDefault();
                    alert('Please select at least one invoice.');
                    return;
                }

                const status = bulkStatus.value;

                // If status is paid, go to payment form route instead
                if (status === 'paid') {
                    bulkForm.action = "{{ route('admin.invoices.payment-form') }}";
                } else {
                    bulkForm.action = "{{ route('admin.invoices.bulk-status') }}";
                }
            });
        }
    })();
</script>
@endsection

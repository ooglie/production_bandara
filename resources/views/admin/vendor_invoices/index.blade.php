@extends('layouts.company')

@section('title', 'Vendor invoices')

@section('content')
@php
    $has = fn(string $r) => \Illuminate\Support\Facades\Route::has($r);

    $invoices = $invoices ?? $vendorInvoices ?? $vendor_invoices ?? collect();
    $vendors  = $vendors ?? \App\Models\Vendor::orderBy('name')->get();

    $createUrl = $has('admin.vendor-invoices.create') ? route('admin.vendor-invoices.create') : '#';

    // Bulk pay -> goes to VendorPaymentController@create (GET)
    $bulkPayUrl = $has('admin.vendor-payments.create') ? route('admin.vendor-payments.create') : null;

    // Build paid map: vendor_invoice_id => sum(amount)
    $invoiceIds = collect($invoices)->pluck('id')->filter()->values()->all();

    $paidMap = [];
    if (!empty($invoiceIds)) {
        $paidMap = \App\Models\VendorPayment::query()
            ->selectRaw('vendor_invoice_id, SUM(amount) as paid')
            ->whereIn('vendor_invoice_id', $invoiceIds)
            ->groupBy('vendor_invoice_id')
            ->pluck('paid', 'vendor_invoice_id')
            ->toArray();
    }

    $outstandingUrl = \Illuminate\Support\Facades\Route::has('admin.vendor-invoices.outstanding')
        ? route('admin.vendor-invoices.outstanding')
        : null;
@endphp

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">Vendor invoices</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Track inward stock, costs, and batch/expiry data per invoice.
            </p>
        </div>

        @can('manage invoices')
        <div class="flex items-center gap-2">
            <a href="{{ $createUrl }}"
               class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                New vendor invoice
            </a>
        </div>
        @endcan
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="grid gap-3 md:grid-cols-6 items-end">
            <div class="md:col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor</label>
                <select name="vendor_id"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    <option value="">All vendors</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" @selected((int)request('vendor_id') === (int)$v->id)>
                            {{ $v->code }} — {{ $v->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    <option value="">All statuses</option>
                    @foreach(['pending','paid','partially_paid','cancelled'] as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>
                            {{ ucfirst(str_replace('_',' ',$st)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
            </div>

            <div class="flex items-center gap-2">
                <button class="w-full text-[12px] px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Apply
                </button>

                @if(request()->query())
                    <a href="{{ url()->current() }}"
                       class="w-full text-center text-[12px] px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Clear
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if($outstandingUrl)
        <a href="{{ $outstandingUrl }}"
        class="block rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 hover:bg-gray-50 dark:hover:bg-gray-900/40">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">Total outstanding (all vendors)</div>
            <div class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Click to view breakdown →
            </div>
        </a>
    @endif

    {{-- Bulk actions --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Bulk payment</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    Select multiple invoices (same vendor). Paid/cancelled invoices cannot be selected.
                </div>
            </div>

            @if($bulkPayUrl)
                <form method="GET" action="{{ $bulkPayUrl }}" id="bulk-pay-form" class="flex items-center gap-2">
                    <input type="hidden" name="vendor_id" id="bulk-vendor-id" value="{{ request('vendor_id') ?? '' }}">

                    <button type="submit"
                            id="bulk-pay-btn"
                            disabled
                            class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900
                                   text-white dark:bg-gray-100 dark:text-gray-900 px-5 py-2 text-[12px] font-semibold
                                   disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-800 dark:hover:bg-gray-200">
                        Bulk pay selected (<span id="bulk-count">0</span>)
                    </button>

                    <div id="bulk-warning" class="hidden text-[11px] text-red-600"></div>
                </form>
            @else
                <div class="rounded-xl border border-yellow-300 bg-yellow-50 px-4 py-3 text-[12px] text-yellow-900">
                    Route <code>admin.vendor-payments.create</code> not found.
                </div>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[12px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-400 w-10">
                    <input type="checkbox" id="check-all"
                           class="rounded border-gray-300 dark:border-gray-700">
                </th>
                <th class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Invoice</th>
                <th class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Vendor</th>
                <th class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Date</th>
                <th class="px-3 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                <th class="px-3 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                <th class="px-3 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Outstanding</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($invoices as $inv)
                @php
                    $showUrl = \Illuminate\Support\Facades\Route::has('admin.vendor-invoices.show')
                        ? route('admin.vendor-invoices.show', $inv)
                        : '#';

                    $status = (string)($inv->status ?? 'pending');
                    $vendorId = (int)($inv->vendor_id ?? ($inv->vendor?->id ?? 0));

                    $total = (float)($inv->total_amount ?? (($inv->subtotal ?? 0) + ($inv->tax_amount ?? 0)) ?? 0);
                    $paid  = (float)($paidMap[$inv->id] ?? 0);
                    $outstanding = max($total - $paid, 0);

                    $disabled = in_array($status, ['paid','cancelled'], true);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-3 align-top">
                        <input type="checkbox"
                               class="inv-check rounded border-gray-300 dark:border-gray-700"
                               value="{{ $inv->id }}"
                               data-vendor-id="{{ $vendorId }}"
                               data-status="{{ $status }}"
                               @if($disabled) disabled @endif
                        >
                    </td>

                    <td class="px-3 py-3 align-top">
                        <a href="{{ $showUrl }}" class="text-gray-900 dark:text-gray-50 hover:underline">
                            {{ $inv->invoice_number ?? ('#'.$inv->id) }}
                        </a>
                        <div class="text-[11px] text-gray-400">#{{ $inv->id }}</div>
                    </td>

                    <td class="px-3 py-3 align-top text-gray-700 dark:text-gray-200">
                        {{ $inv->vendor?->name ?? '—' }}
                    </td>

                    <td class="px-3 py-3 align-top text-gray-600 dark:text-gray-300">
                        {{ optional($inv->invoice_date ?? $inv->created_at)->format('d M Y') }}
                    </td>

                    <td class="px-3 py-3 align-top">
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px]
                            @if($status === 'paid') border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200
                            @elseif($status === 'pending') border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200
                            @else border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300
                            @endif">
                            {{ ucfirst(str_replace('_',' ',$status)) }}
                        </span>

                        @if($disabled)
                            <div class="text-[10px] text-gray-400 mt-1">Not selectable</div>
                        @endif
                    </td>

                    <td class="px-3 py-3 align-top text-right text-gray-900 dark:text-gray-50">
                        ₹{{ number_format($total, 2) }}
                        @if($paid > 0)
                            <div class="text-[10px] text-gray-400">Paid: ₹{{ number_format($paid, 2) }}</div>
                        @endif
                    </td>

                    <td class="px-3 py-3 align-top text-right">
                        @if($outstanding <= 0.0001)
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px]
                                border-emerald-200 bg-emerald-50 text-emerald-700
                                dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                ₹0.00
                            </span>
                        @else
                            <span class="font-semibold text-gray-900 dark:text-gray-50">
                                ₹{{ number_format($outstanding, 2) }}
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                        No vendor invoices found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(is_object($invoices) && method_exists($invoices, 'links'))
        <div class="mt-3">
            {{ $invoices->links() }}
        </div>
    @endif
</div>

@if($bulkPayUrl)
<script>
(function () {
    const form = document.getElementById('bulk-pay-form');
    const btn = document.getElementById('bulk-pay-btn');
    const countEl = document.getElementById('bulk-count');
    const warningEl = document.getElementById('bulk-warning');
    const vendorHidden = document.getElementById('bulk-vendor-id');

    const checkAll = document.getElementById('check-all');
    const checks = Array.from(document.querySelectorAll('.inv-check'));

    function clearHiddenInvoiceIds() {
        form.querySelectorAll('input[name="invoice_ids[]"]').forEach(el => el.remove());
    }

    function selected() {
        return checks.filter(c => c.checked);
    }

    function setWarning(msg) {
        if (!warningEl) return;
        if (!msg) {
            warningEl.classList.add('hidden');
            warningEl.textContent = '';
        } else {
            warningEl.classList.remove('hidden');
            warningEl.textContent = msg;
        }
    }

    function refresh() {
        const sel = selected();
        countEl.textContent = String(sel.length);

        const vendorIds = Array.from(new Set(sel.map(x => String(x.dataset.vendorId || '0')))).filter(Boolean);
        const validVendor = vendorIds.length <= 1;

        btn.disabled = sel.length === 0 || !validVendor;

        if (vendorIds.length === 1) vendorHidden.value = vendorIds[0];

        setWarning(!validVendor ? 'Please select invoices from only ONE vendor.' : '');

        clearHiddenInvoiceIds();
        sel.forEach(c => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'invoice_ids[]';
            inp.value = c.value;
            form.appendChild(inp);
        });

        const enabled = checks.filter(c => !c.disabled);
        const enabledChecked = enabled.filter(c => c.checked);
        checkAll.checked = enabled.length > 0 && enabledChecked.length === enabled.length;
        checkAll.indeterminate = enabledChecked.length > 0 && enabledChecked.length < enabled.length;
    }

    checks.forEach(c => c.addEventListener('change', refresh));

    checkAll?.addEventListener('change', () => {
        const enabled = checks.filter(c => !c.disabled);
        enabled.forEach(c => c.checked = checkAll.checked);
        refresh();
    });

    form?.addEventListener('submit', (e) => {
        refresh();
        if (btn.disabled) e.preventDefault();
    });

    refresh();
})();
</script>
@endif
@endsection
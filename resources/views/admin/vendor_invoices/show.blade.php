@extends('layouts.company')

@section('title', 'Vendor Invoice ' . ($invoice->invoice_number ?? ''))
@section('breadcrumb', 'Admin · Vendor Invoices · ' . ($invoice->invoice_number ?? ''))

@section('content')
@php
    $items = $invoice->items ?? collect();
    $payments = $invoice->payments ?? collect();
    $adjustments = ($invoice->adjustments ?? collect())->sortByDesc(fn($row) => $row->posted_at ?? $row->created_at);
    $returns = ($invoice->vendorReturns ?? collect())->sortByDesc(fn($row) => $row->posted_at ?? $row->created_at);
    $balance = $balanceSummary ?? [
        'original_subtotal' => (float)$invoice->subtotal,
        'original_tax' => (float)$invoice->tax_amount,
        'original_total' => (float)$invoice->total_amount,
        'adjustment_subtotal' => 0,
        'adjustment_tax' => 0,
        'adjustment_total' => 0,
        'adjusted_subtotal' => (float)$invoice->subtotal,
        'adjusted_tax' => (float)$invoice->tax_amount,
        'adjusted_total' => (float)$invoice->total_amount,
        'paid' => (float)$payments->sum('amount'),
        'outstanding' => max((float)$invoice->total_amount - (float)$payments->sum('amount'), 0),
        'vendor_credit_due' => 0,
    ];
    $status = (string)($invoice->status ?? 'pending');
    $canAdjust = auth()->user()?->can('adjust vendor invoices') ?? false;
    $itemLotMap = $itemLotMap ?? [];

    $statusClasses = match ($status) {
        'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
        'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
    };

    $receiptLabel = fn (?string $type): string => match ((string)$type) {
        'pieces_weight', 'bulk_weight' => 'Pieces with weight',
        'quantity', 'loose_pieces', 'finished_pack' => 'Quantity',
        default => 'Received stock',
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Vendor Invoice {{ $invoice->invoice_number ?? '—' }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] {{ $statusClasses }}">{{ str_replace('_', ' ', ucfirst($status)) }}</span>
                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $invoice->vendor?->name ?? '—' }}</span>
                @if($invoice->invoice_date)<span class="text-[11px] text-gray-500 dark:text-gray-400">· {{ $invoice->invoice_date->format('d M Y') }}</span>@endif
                @if($adjustments->where('status', 'posted')->isNotEmpty())<span class="rounded-full border border-gray-200 dark:border-gray-700 px-2 py-1 text-[10px]">Adjusted</span>@endif
                @if($returns->where('status', '!=', 'draft')->isNotEmpty())<span class="rounded-full border border-gray-200 dark:border-gray-700 px-2 py-1 text-[10px]">Return recorded</span>@endif
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.vendor-invoices.index') }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Back</a>
            @if($status !== 'cancelled' && $balance['outstanding'] > 0.005 && \Illuminate\Support\Facades\Route::has('admin.vendor-payments.create'))
                <a href="{{ route('admin.vendor-payments.create', ['vendor_id' => $invoice->vendor_id, 'vendor_invoice_id' => $invoice->id]) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Record payment</a>
            @endif
            @if($canAdjust && $status !== 'cancelled')
                <a href="{{ route('admin.vendor-invoices.edit-details', $invoice) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Edit details</a>
                <a href="{{ route('admin.vendor-invoices.returns.create', $invoice) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Record return</a>
                <a href="{{ route('admin.vendor-invoices.adjustments.create', [$invoice, 'credit']) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Credit note</a>
                <a href="{{ route('admin.vendor-invoices.adjustments.create', [$invoice, 'debit']) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] hover:bg-gray-50 dark:hover:bg-gray-800">Debit note</a>
                <a href="{{ route('admin.vendor-invoices.reverse.confirm', $invoice) }}" class="rounded-lg border border-red-300 dark:border-red-800 px-3 py-2 text-[11px] text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/20">Reverse invoice</a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"><ul class="list-disc pl-4 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 xl:col-span-2">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Vendor</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $invoice->vendor?->name ?? '—' }}</div>
            @if($invoice->vendor?->code)<div class="mt-1 text-[10px] text-gray-400">{{ $invoice->vendor->code }}</div>@endif
            <div class="mt-3 grid grid-cols-2 gap-2 text-[11px]"><div><span class="text-gray-400">Due</span><div>{{ $invoice->due_date?->format('d M Y') ?: '—' }}</div></div><div><span class="text-gray-400">Tally ref</span><div>{{ $invoice->tally_reference ?: '—' }}</div></div></div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Original total</div>
            <div class="mt-2 text-lg font-semibold">₹{{ number_format($balance['original_total'], 2) }}</div>
            <div class="mt-1 text-[10px] text-gray-400">Subtotal ₹{{ number_format($balance['original_subtotal'], 2) }} · Tax ₹{{ number_format($balance['original_tax'], 2) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Posted adjustments</div>
            <div class="mt-2 text-lg font-semibold">₹{{ number_format($balance['adjustment_total'], 2) }}</div>
            <div class="mt-1 text-[10px] text-gray-400">Credits negative · debits positive</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Adjusted payable</div>
            <div class="mt-2 text-lg font-semibold">₹{{ number_format($balance['adjusted_total'], 2) }}</div>
            <div class="mt-1 text-[10px] text-gray-400">Paid ₹{{ number_format($balance['paid'], 2) }}</div>
        </div>
        <div class="rounded-2xl border {{ $balance['vendor_credit_due'] > 0 ? 'border-amber-300 dark:border-amber-800' : 'border-gray-200 dark:border-gray-800' }} bg-white dark:bg-gray-900 p-4">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $balance['vendor_credit_due'] > 0 ? 'Vendor credit / refund due' : 'Outstanding' }}</div>
            <div class="mt-2 text-lg font-semibold">₹{{ number_format($balance['vendor_credit_due'] > 0 ? $balance['vendor_credit_due'] : $balance['outstanding'], 2) }}</div>
            <div class="mt-1 text-[10px] text-gray-400">Based on adjusted payable</div>
        </div>
    </div>

    @if($invoice->notes)
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5"><div class="text-[10px] uppercase tracking-wide text-gray-400">Invoice notes</div><div class="mt-2 whitespace-pre-line text-[12px] text-gray-700 dark:text-gray-200">{{ $invoice->notes }}</div></section>
    @endif

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div class="text-[11px] uppercase tracking-wide text-gray-400">Original invoice items</div><div class="mt-1 text-sm font-semibold">Received stock, tax and source lots</div><p class="mt-1 text-[10px] text-gray-400">These original values are retained permanently. Returns and financial changes appear separately below.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40"><tr><th class="px-4 py-3 text-left font-medium text-gray-500">Product</th><th class="px-4 py-3 text-left font-medium text-gray-500">Receipt</th><th class="px-4 py-3 text-right font-medium text-gray-500">Quantity / weight</th><th class="px-4 py-3 text-right font-medium text-gray-500">Unit cost</th><th class="px-4 py-3 text-right font-medium text-gray-500">Tax</th><th class="px-4 py-3 text-left font-medium text-gray-500">Lot</th><th class="px-4 py-3 text-right font-medium text-gray-500">Line total</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($items as $item)
                        @php
                            $lot = $itemLotMap[$item->id] ?? $item->inventoryLot;
                            $pieceRows = $lot?->pieces ?? collect();
                            $variantLabel = $item->productVariant?->name ?: $item->productVariant?->sku;
                            $weighted = in_array((string)$item->receipt_type, ['pieces_weight','bulk_weight'], true);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top"><div class="font-medium text-gray-900 dark:text-gray-50">{{ $item->product?->name ?? '—' }}</div>@if($variantLabel)<div class="mt-1 text-[10px] text-gray-400">{{ $variantLabel }}</div>@endif</td>
                            <td class="px-4 py-3 align-top"><span class="rounded-full border border-gray-200 dark:border-gray-700 px-2 py-1 text-[10px]">{{ $receiptLabel($item->receipt_type) }}</span>@if($item->unit_cost_includes_gst)<div class="mt-2 text-[10px] text-gray-400">Cost includes GST</div>@endif</td>
                            <td class="px-4 py-3 align-top text-right">@if($weighted){{ number_format((float)($item->total_weight_kg ?? $lot?->total_weight_kg), 3) }} kg<div class="text-[10px] text-gray-400">{{ number_format((float)$item->quantity, 0) }} pieces</div>@else{{ number_format((float)$item->quantity, 3) }} units @if($lot?->total_weight_kg)<div class="text-[10px] text-gray-400">{{ number_format((float)$lot->total_weight_kg, 3) }} kg</div>@endif @endif @if($pieceRows->isNotEmpty())<div class="text-[10px] text-gray-400">{{ $pieceRows->count() }} tracked pieces</div>@endif</td>
                            <td class="px-4 py-3 align-top text-right">₹{{ number_format((float)$item->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 align-top text-right">₹{{ number_format((float)$item->tax_amount, 2) }}<div class="text-[10px] text-gray-400">{{ number_format((float)($item->gst_rate ?? 0), 2) }}%</div></td>
                            <td class="px-4 py-3 align-top">@if($lot)<div class="font-medium">{{ $lot->lot_code ?: ('Lot #'.$lot->id) }}</div><div class="mt-1 text-[10px] text-gray-400">Available: {{ number_format((float)($weighted ? $lot->available_weight_kg : $lot->available_quantity), 3) }} {{ $weighted ? 'kg' : 'units' }} · {{ ucfirst((string)$lot->lot_status) }}</div>@else—@endif</td>
                            <td class="px-4 py-3 align-top text-right font-semibold">₹{{ number_format((float)$item->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No invoice items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div><div class="text-[11px] uppercase tracking-wide text-gray-400">Financial adjustments</div><div class="mt-1 text-sm font-semibold">Credit notes, debit notes and corrections</div></div>@if($canAdjust && $status !== 'cancelled')<div class="flex gap-2"><a class="text-[10px] underline" href="{{ route('admin.vendor-invoices.adjustments.create', [$invoice,'credit']) }}">New credit</a><a class="text-[10px] underline" href="{{ route('admin.vendor-invoices.adjustments.create', [$invoice,'debit']) }}">New debit</a></div>@endif</div>
            @forelse($adjustments as $adjustment)
                <a href="{{ route('admin.vendor-invoices.adjustments.show', [$invoice, $adjustment]) }}" class="block border-b border-gray-100 dark:border-gray-800 px-5 py-4 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <div class="flex items-start justify-between gap-3"><div><div class="font-medium text-gray-900 dark:text-gray-50">{{ $adjustment->typeLabel() }} · {{ $adjustment->adjustment_number }}</div><div class="mt-1 text-[10px] text-gray-400">{{ $adjustment->supplier_document_number ?: $adjustment->reason }} · {{ ucfirst($adjustment->status) }}</div></div><div class="text-right font-semibold {{ (float)$adjustment->total_delta < 0 ? 'text-emerald-700 dark:text-emerald-300' : '' }}">₹{{ number_format((float)$adjustment->total_delta, 2) }}<div class="text-[10px] font-normal text-gray-400">{{ ($adjustment->posted_at ?? $adjustment->created_at)?->format('d M Y') }}</div></div></div>
                </a>
            @empty
                <div class="p-5 text-[11px] text-gray-500">No adjustments recorded.</div>
            @endforelse
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div><div class="text-[11px] uppercase tracking-wide text-gray-400">Purchase returns</div><div class="mt-1 text-sm font-semibold">Physical stock returned to supplier</div></div>@if($canAdjust && $status !== 'cancelled')<a class="text-[10px] underline" href="{{ route('admin.vendor-invoices.returns.create', $invoice) }}">New return</a>@endif</div>
            @forelse($returns as $return)
                <a href="{{ route('admin.vendor-invoices.returns.show', [$invoice, $return]) }}" class="block border-b border-gray-100 dark:border-gray-800 px-5 py-4 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <div class="flex items-start justify-between gap-3"><div><div class="font-medium text-gray-900 dark:text-gray-50">{{ $return->return_number }}</div><div class="mt-1 text-[10px] text-gray-400">{{ $return->reason }} · {{ str_replace('_',' ',ucfirst($return->status)) }}</div></div><div class="text-right font-semibold">₹{{ number_format((float)$return->expected_total, 2) }}<div class="text-[10px] font-normal text-gray-400">{{ $return->return_date?->format('d M Y') }}</div></div></div>
                </a>
            @empty
                <div class="p-5 text-[11px] text-gray-500">No purchase returns recorded.</div>
            @endforelse
        </section>
    </div>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div class="text-[11px] uppercase tracking-wide text-gray-400">Payments</div><div class="mt-1 text-sm font-semibold">Recorded vendor payments</div></div>
        @if($payments->isEmpty())
            <div class="p-5 text-[11px] text-gray-500">No payments recorded.</div>
        @else
            <div class="overflow-x-auto"><table class="min-w-full text-[11px]"><thead class="bg-gray-50 dark:bg-gray-950/40"><tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Method</th><th class="px-4 py-3 text-left">Reference</th><th class="px-4 py-3 text-left">Notes</th><th class="px-4 py-3 text-right">Amount</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@foreach($payments as $payment)<tr><td class="px-4 py-3">{{ $payment->payment_date?->format('d M Y') ?: $payment->created_at?->format('d M Y') }}</td><td class="px-4 py-3">{{ $payment->payment_method ?: '—' }}</td><td class="px-4 py-3">{{ $payment->reference_number ?: '—' }}</td><td class="px-4 py-3">{{ $payment->notes ?: '—' }}</td><td class="px-4 py-3 text-right font-semibold">₹{{ number_format((float)$payment->amount, 2) }}</td></tr>@endforeach</tbody><tfoot class="bg-gray-50 dark:bg-gray-950/40"><tr><td colspan="4" class="px-4 py-3 text-right font-medium">Total paid</td><td class="px-4 py-3 text-right font-semibold">₹{{ number_format($balance['paid'], 2) }}</td></tr></tfoot></table></div>
        @endif
    </section>

    @if($canAdjust && $status !== 'cancelled' && isset($reversalAssessment) && !$reversalAssessment['can_reverse'])
        <details class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><summary class="cursor-pointer text-[11px] font-medium">Why full reversal is currently blocked</summary><ul class="mt-3 list-disc pl-5 text-[10px] text-gray-500 space-y-1">@foreach($reversalAssessment['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul></details>
    @endif
</div>
@endsection

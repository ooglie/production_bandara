@extends('layouts.company')

@section('title', 'Vendor Invoice ' . ($invoice->invoice_number ?? ''))

@section('breadcrumb', 'Admin · Vendor Invoices · ' . ($invoice->invoice_number ?? ''))

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $backUrl = Route::has('admin.vendor-invoices.index')
        ? route('admin.vendor-invoices.index')
        : url()->previous();

    $items = $invoice->items ?? collect();
    $payments = $invoice->payments ?? collect();
    $itemLotMap = $itemLotMap ?? [];

    $paidTotal = (float) $payments->sum(fn ($p) => (float) data_get($p, 'amount', 0));
    $invoiceTotal = (float) data_get($invoice, 'total_amount', 0);
    $outstandingTotal = max($invoiceTotal - $paidTotal, 0);

    $totalReceivedQty = $items->sum(fn ($item) => (float) ($item->quantity ?? 0));

    $totalReceivedWeight = $items->sum(function ($item) {
        $total = $item->total_weight_kg ?? null;

        if (($total === null || $total === '') && !empty($item->unit_weight_kg) && !empty($item->quantity)) {
            $total = (float) $item->unit_weight_kg * (float) $item->quantity;
        }

        return (float) ($total ?? 0);
    });

    $status = (string) data_get($invoice, 'status', 'pending');

    $statusClasses = match ($status) {
        'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
        'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Vendor Invoice {{ $invoice->invoice_number ?? '—' }}
            </h1>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] {{ $statusClasses }}">
                    {{ str_replace('_', ' ', ucfirst($status)) }}
                </span>

                @if(data_get($invoice, 'invoice_date'))
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                        Invoice date: {{ \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ $backUrl }}"
               class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Vendor</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">
                {{ data_get($invoice, 'vendor.name', '—') }}
            </div>
            @if(data_get($invoice, 'vendor.code'))
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Code: {{ data_get($invoice, 'vendor.code') }}
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Invoice details</div>
            <div class="mt-2 space-y-1 text-[12px] text-gray-700 dark:text-gray-200">
                <div><span class="text-gray-500 dark:text-gray-400">Number:</span> {{ $invoice->invoice_number ?? '—' }}</div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Invoice date:</span>
                    {{ data_get($invoice, 'invoice_date') ? \Illuminate\Support\Carbon::parse($invoice->invoice_date)->format('d M Y') : '—' }}
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Due date:</span>
                    {{ data_get($invoice, 'due_date') ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d M Y') : '—' }}
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Invoice total</div>
            <div class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-50">
                ₹{{ number_format((float) data_get($invoice, 'total_amount', 0), 2) }}
            </div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Subtotal ₹{{ number_format((float) data_get($invoice, 'subtotal', 0), 2) }}
                · Tax ₹{{ number_format((float) data_get($invoice, 'tax_amount', 0), 2) }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Payment status</div>
            <div class="mt-2 space-y-1">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                    Paid ₹{{ number_format($paidTotal, 2) }}
                </div>
                <div class="text-[12px] text-gray-700 dark:text-gray-200">
                    Outstanding ₹{{ number_format($outstandingTotal, 2) }}
                </div>
            </div>
        </div>
    </div>

    @if(!empty($invoice->notes))
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</div>
            </div>
            <div class="p-5 text-[12px] text-gray-700 dark:text-gray-200 whitespace-pre-line">
                {{ $invoice->notes }}
            </div>
        </section>
    @endif

    {{-- Invoice items --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Invoice items</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                        Received products and weights
                    </div>
                    <div class="text-[12px] text-gray-500 dark:text-gray-400">
                        Qty mode shows quantity + unit weight. Pieces mode shows total received weight.
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px]">
                        <div class="text-gray-500 dark:text-gray-400">Total received qty</div>
                        <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">
                            {{ number_format((float) $totalReceivedQty, 2) }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px]">
                        <div class="text-gray-500 dark:text-gray-400">Total received weight</div>
                        <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">
                            {{ number_format((float) $totalReceivedWeight, 3) }} kg
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Quantity</th>
                        <th class="px-4 py-3 font-medium">Unit wt. (kg)</th>
                        <th class="px-4 py-3 font-medium">Total wt. (kg)</th>
                        <th class="px-4 py-3 font-medium">Unit cost</th>
                        <th class="px-4 py-3 font-medium">Tax</th>
                        <th class="px-4 py-3 font-medium text-right">Line total</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($items as $item)
                        @php
                            $displayTotalWeight = $item->total_weight_kg ?? null;

                            if (($displayTotalWeight === null || $displayTotalWeight === '') && !empty($item->unit_weight_kg) && !empty($item->quantity)) {
                                $displayTotalWeight = round((float) $item->unit_weight_kg * (float) $item->quantity, 3);
                            }

                            $productName = $item->product?->name ?? '—';
                            $variantText = $item->productVariant?->sku ? 'Variant: ' . $item->productVariant->sku : null;

                            $lot = $itemLotMap[$item->id] ?? null;

                            $lotModeLabel = null;
                            if ($lot && !empty($lot->inward_mode)) {
                                $lotModeLabel = $lot->inward_mode === 'pieces' ? 'Pieces' : 'Qty';
                            }

                            $pieceCount = (int) data_get($lot, 'piece_count', 0);
                        @endphp

                        <tr>
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    {{ $productName }}
                                </div>

                                @if($variantText)
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $variantText }}
                                    </div>
                                @endif

                                @if($lot)
                                    <div class="mt-2 flex flex-wrap gap-2 text-[10px]">
                                        @if(!empty($lot->batch_code))
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                Batch: {{ $lot->batch_code }}
                                            </span>
                                        @endif

                                        @if($lotModeLabel)
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                Mode: {{ $lotModeLabel }}
                                            </span>
                                        @endif

                                        @if($lotModeLabel === 'Pieces' && $pieceCount > 0)
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                Pieces: {{ $pieceCount }}
                                            </span>
                                        @endif

                                        @if(!empty($lot->mfg_date))
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                Mfg: {{ \Illuminate\Support\Carbon::parse($lot->mfg_date)->format('d M Y') }}
                                            </span>
                                        @endif

                                        @if(!empty($lot->packed_date))
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                Packed: {{ \Illuminate\Support\Carbon::parse($lot->packed_date)->format('d M Y') }}
                                            </span>
                                        @endif

                                        @if(!empty($lot->expiry_date))
                                            <span class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-gray-600 dark:text-gray-300">
                                                Expiry: {{ \Illuminate\Support\Carbon::parse($lot->expiry_date)->format('d M Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                <div>{{ number_format((float) ($item->quantity ?? 0), 2) }}</div>

                                @if($lotModeLabel === 'Pieces' && $pieceCount > 0)
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ $pieceCount }} piece{{ $pieceCount === 1 ? '' : 's' }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                @if($item->unit_weight_kg !== null && $item->unit_weight_kg !== '')
                                    {{ number_format((float) $item->unit_weight_kg, 3) }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                @if($displayTotalWeight !== null && $displayTotalWeight !== '')
                                    {{ number_format((float) $displayTotalWeight, 3) }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                ₹{{ number_format((float) ($item->unit_cost ?? 0), 2) }}
                            </td>

                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                ₹{{ number_format((float) ($item->tax_amount ?? 0), 2) }}
                            </td>

                            <td class="px-4 py-3 align-top text-right font-semibold text-gray-900 dark:text-gray-50">
                                ₹{{ number_format((float) ($item->total ?? 0), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No invoice items found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Payments --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Payments</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Recorded vendor payments
            </div>
        </div>

        @if($payments->isEmpty())
            <div class="p-5 text-[12px] text-gray-500 dark:text-gray-400">
                No payments recorded yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-[12px]">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Method</th>
                            <th class="px-4 py-3 font-medium">Reference</th>
                            <th class="px-4 py-3 font-medium">Notes</th>
                            <th class="px-4 py-3 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($payments as $payment)
                            @php
                                $paymentDate = data_get($payment, 'payment_date') ?: data_get($payment, 'created_at');
                                $paymentMethod = data_get($payment, 'payment_method') ?: data_get($payment, 'method') ?: '—';
                                $paymentReference = data_get($payment, 'reference_number') ?: data_get($payment, 'reference_no') ?: '—';
                                $paymentNotes = data_get($payment, 'notes') ?: '—';
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $paymentDate ? \Illuminate\Support\Carbon::parse($paymentDate)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $paymentMethod }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $paymentReference }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $paymentNotes }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-50">
                                    ₹{{ number_format((float) data_get($payment, 'amount', 0), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-950/40">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">
                                Total paid
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-50">
                                ₹{{ number_format($paidTotal, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
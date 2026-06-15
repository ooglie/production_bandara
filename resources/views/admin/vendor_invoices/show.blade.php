@extends('layouts.company')

@section('title', 'Vendor Invoice ' . ($invoice->invoice_number ?? ''))
@section('breadcrumb', 'Admin · Vendor Invoices · ' . ($invoice->invoice_number ?? ''))

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Carbon;

    $backUrl = Route::has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();
    $items = $invoice->items ?? collect();
    $payments = $invoice->payments ?? collect();
    $itemLotMap = $itemLotMap ?? [];

    $paidTotal = (float) $payments->sum(fn ($p) => (float) data_get($p, 'amount', 0));
    $invoiceTotal = (float) data_get($invoice, 'total_amount', 0);
    $outstandingTotal = max($invoiceTotal - $paidTotal, 0);
    $status = (string) data_get($invoice, 'status', 'pending');

    $statusClasses = match ($status) {
        'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
        'partially_paid' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200',
        default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300',
    };

    $receiptLabel = function (?string $type): string {
        return match ((string) $type) {
            'pieces_weight', 'bulk_weight' => 'Pieces with weight',
            'quantity', 'loose_pieces', 'finished_pack' => 'Quantity',
            default => 'Received stock',
        };
    };

    $normalReceiptType = fn (?string $type): string => match ((string) $type) {
        'bulk_weight' => 'pieces_weight',
        'loose_pieces', 'finished_pack' => 'quantity',
        default => (string) ($type ?: 'received'),
    };

    $stockSummary = $items->groupBy(fn ($item) => $normalReceiptType($item->receipt_type ?? 'received'));
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Vendor Invoice {{ $invoice->invoice_number ?? '—' }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] {{ $statusClasses }}">{{ str_replace('_', ' ', ucfirst($status)) }}</span>
                @if(data_get($invoice, 'invoice_date'))
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">Invoice date: {{ Carbon::parse($invoice->invoice_date)->format('d M Y') }}</span>
                @endif
            </div>
        </div>
        <a href="{{ $backUrl }}" class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">Back</a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Vendor</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ data_get($invoice, 'vendor.name', '—') }}</div>
            @if(data_get($invoice, 'vendor.code'))
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Code: {{ data_get($invoice, 'vendor.code') }}</div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Invoice dates</div>
            <div class="mt-2 space-y-1 text-[12px] text-gray-700 dark:text-gray-200">
                <div><span class="text-gray-500 dark:text-gray-400">Number:</span> {{ $invoice->invoice_number ?? '—' }}</div>
                <div><span class="text-gray-500 dark:text-gray-400">Invoice:</span> {{ data_get($invoice, 'invoice_date') ? Carbon::parse($invoice->invoice_date)->format('d M Y') : '—' }}</div>
                <div><span class="text-gray-500 dark:text-gray-400">Due:</span> {{ data_get($invoice, 'due_date') ? Carbon::parse($invoice->due_date)->format('d M Y') : '—' }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Invoice total</div>
            <div class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) data_get($invoice, 'total_amount', 0), 2) }}</div>
            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Subtotal ₹{{ number_format((float) data_get($invoice, 'subtotal', 0), 2) }} · Tax ₹{{ number_format((float) data_get($invoice, 'tax_amount', 0), 2) }}</div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Payment</div>
            <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-50">Paid ₹{{ number_format($paidTotal, 2) }}</div>
            <div class="mt-1 text-[12px] text-gray-700 dark:text-gray-200">Outstanding ₹{{ number_format($outstandingTotal, 2) }}</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach(['pieces_weight' => 'Pieces with weight', 'quantity' => 'Quantity inward'] as $type => $title)
            @php $group = $stockSummary->get($type, collect()); @endphp
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ $title }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    @if($type === 'pieces_weight')
                        {{ number_format((float) $group->sum(fn ($item) => (float) ($item->total_weight_kg ?? 0)), 3) }} kg
                    @else
                        {{ number_format((float) $group->sum(fn ($item) => (float) ($item->quantity ?? 0)), 3) }} units
                    @endif
                </div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    @if($type === 'pieces_weight')
                        Raw weighted stock with optional individual inventory pieces.
                    @else
                        Unit/pack stock received directly against the selected product.
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    @if(!empty($invoice->notes))
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</div>
            <div class="p-5 text-[12px] text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ $invoice->notes }}</div>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Invoice items</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Received stock, tax, and lots</div>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">Pieces-with-weight rows create weighted lots and inventory pieces. Quantity rows create unit/pack stock for the selected product.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px]">
                <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr class="text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Receipt</th>
                        <th class="px-4 py-3 font-medium">Qty</th>
                        <th class="px-4 py-3 font-medium">Unit cost</th>
                        <th class="px-4 py-3 font-medium">GST / Tax</th>
                        <th class="px-4 py-3 font-medium">Lot</th>
                        <th class="px-4 py-3 font-medium text-right">Line total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($items as $item)
                        @php
                            $lot = $itemLotMap[$item->id] ?? null;
                            $receiptType = (string) ($item->receipt_type ?? data_get($lot, 'inward_mode', 'received'));
                            $gstRate = $item->gst_rate !== null ? (float) $item->gst_rate : (float) data_get($item, 'hsnCode.gst_rate', 0);
                            $taxManual = (bool) ($item->tax_manual ?? false);
                            $costIncludesGst = (bool) ($item->unit_cost_includes_gst ?? false);
                            $normalizedReceipt = $normalReceiptType($receiptType);
                            $quantitySuffix = $normalizedReceipt === 'pieces_weight' ? 'pcs' : 'units';
                            $pieceRows = $lot?->pieces ?? collect();
                            $variantLabel = trim((string) ($item->productVariant?->name ?? '')) ?: trim((string) ($item->productVariant?->sku ?? ''));
                        @endphp
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">{{ $item->product?->name ?? '—' }}</div>
                                @if($variantLabel !== '')
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Pack variant: {{ $variantLabel }}</div>
                                @endif
                                @if($item->mrp_incl_gst)
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">MRP incl GST entered: ₹{{ number_format((float) $item->mrp_incl_gst, 2) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950/40 px-2 py-1 text-[11px] text-gray-600 dark:text-gray-300">{{ $receiptLabel($receiptType) }}</span>
                                @if($costIncludesGst)
                                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">Unit cost includes GST</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                {{ number_format((float) ($item->quantity ?? 0), 3) }} {{ $quantitySuffix }}
                                @if($lot && ($lot->total_weight_kg || $lot->available_weight_kg))
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Weight: {{ number_format((float) ($lot->total_weight_kg ?? $lot->available_weight_kg), 3) }} kg</div>
                                @endif
                                @if($pieceRows->isNotEmpty())
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $pieceRows->count() }} individual weight(s) recorded</div>
                                @endif
                                @if($lot && $lot->pieces_per_pack)
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ number_format((float) $lot->pieces_per_pack, 0) }} pcs/pack</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">₹{{ number_format((float) ($item->unit_cost ?? 0), 2) }}</td>
                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                <div>₹{{ number_format((float) ($item->tax_amount ?? 0), 2) }}</div>
                                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    @if($costIncludesGst)
                                        No extra GST added
                                    @else
                                        GST {{ number_format($gstRate, 2) }}% {{ $taxManual ? '· manual' : '· auto from HSN' }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">
                                @if($lot)
                                    <div class="font-medium text-gray-900 dark:text-gray-50">{{ $lot->lot_code ?? ('Lot #' . $lot->id) }}</div>
                                    <div class="mt-1 flex flex-wrap gap-1 text-[10px]">
                                        @if($lot->batch_code)
                                            <span class="rounded-sm border border-gray-200 dark:border-gray-700 px-2 py-1">Batch {{ $lot->batch_code }}</span>
                                        @endif
                                        @if($lot->expiry_date)
                                            <span class="rounded-sm border border-gray-200 dark:border-gray-700 px-2 py-1">Exp {{ Carbon::parse($lot->expiry_date)->format('d M Y') }}</span>
                                        @endif
                                        <span class="rounded-sm border border-gray-200 dark:border-gray-700 px-2 py-1">{{ $lot->is_saleable ? 'Saleable' : 'Repackable' }}</span>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) ($item->total ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoice items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Payments</div>
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recorded vendor payments</div>
        </div>

        @if($payments->isEmpty())
            <div class="p-5 text-[12px] text-gray-500 dark:text-gray-400">No payments recorded yet.</div>
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
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $paymentDate ? Carbon::parse($paymentDate)->format('d M Y') : '—' }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $paymentMethod }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $paymentReference }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $paymentNotes }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) data_get($payment, 'amount', 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-950/40">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Total paid</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($paidTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection

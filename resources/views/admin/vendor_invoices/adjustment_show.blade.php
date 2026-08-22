@extends('layouts.company')

@section('title', $adjustment->adjustment_number)
@section('breadcrumb', 'Admin · Vendor Invoices · Adjustment')

@section('content')
@php
    $isDraft = $adjustment->isDraft();
    $isCredit = (float)$adjustment->total_delta < 0;
    $canReverse = $adjustment->isPosted()
        && !$adjustment->affects_stock
        && !$adjustment->reversal
        && !in_array($adjustment->type, ['metadata_correction', 'adjustment_reversal'], true);
@endphp
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ $adjustment->typeLabel() }}</div>
            <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $adjustment->adjustment_number }}</h1>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="rounded-full border px-2.5 py-1 text-[10px] {{ $isDraft ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200' }}">{{ ucfirst($adjustment->status) }}</span>
                @if($adjustment->affects_stock)<span class="rounded-full border border-gray-200 dark:border-gray-700 px-2.5 py-1 text-[10px]">Stock linked</span>@endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.vendor-invoices.show', $invoice) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px]">Invoice</a>
            @if($isDraft)
                <form method="POST" action="{{ route('admin.vendor-invoices.adjustments.destroy', [$invoice, $adjustment]) }}" onsubmit="return confirm('Delete this draft adjustment?')">@csrf @method('DELETE')<button class="rounded-lg border border-red-300 dark:border-red-800 px-3 py-2 text-[11px] text-red-700 dark:text-red-300">Delete draft</button></form>
                <form method="POST" action="{{ route('admin.vendor-invoices.adjustments.post', [$invoice, $adjustment]) }}" onsubmit="return confirm('Post this adjustment? Posted values affect the supplier payable.')">@csrf<button class="rounded-lg bg-gray-900 dark:bg-gray-100 px-4 py-2 text-[11px] font-semibold text-white dark:text-gray-900">Post adjustment</button></form>
            @endif
        </div>
    </div>

    @if(session('status'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Supplier document</div><div class="mt-1 font-semibold">{{ $adjustment->supplier_document_number ?: 'Internal audit entry' }}</div><div class="text-[10px] text-gray-400">{{ $adjustment->supplier_document_date?->format('d M Y') ?: '—' }}</div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Taxable-value delta</div><div class="mt-1 text-base font-semibold {{ $isCredit ? 'text-emerald-700 dark:text-emerald-300' : '' }}">₹{{ number_format((float)$adjustment->subtotal_delta, 2) }}</div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Tax delta</div><div class="mt-1 text-base font-semibold">₹{{ number_format((float)$adjustment->tax_delta, 2) }}</div></div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"><div class="text-[10px] uppercase tracking-wide text-gray-400">Total delta</div><div class="mt-1 text-base font-semibold">₹{{ number_format((float)$adjustment->total_delta, 2) }}</div></div>
    </div>

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
        <div class="grid gap-4 md:grid-cols-2">
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400">Reason</div><div class="mt-1 text-[12px] text-gray-800 dark:text-gray-200">{{ $adjustment->reason }}</div></div>
            <div><div class="text-[10px] uppercase tracking-wide text-gray-400">Audit</div><div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Created by {{ $adjustment->creator?->name ?? 'System' }} · {{ $adjustment->created_at?->format('d M Y H:i') }}</div>@if($adjustment->posted_at)<div class="text-[11px] text-gray-500 dark:text-gray-400">Posted by {{ $adjustment->postedBy?->name ?? 'System' }} · {{ $adjustment->posted_at->format('d M Y H:i') }}</div>@endif</div>
        </div>
        @if($adjustment->notes)<div class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-4 whitespace-pre-line text-[12px] text-gray-600 dark:text-gray-300">{{ $adjustment->notes }}</div>@endif
        @if($adjustment->reversesAdjustment)<div class="mt-4 text-[11px]">Reverses <a class="font-medium underline" href="{{ route('admin.vendor-invoices.adjustments.show', [$invoice, $adjustment->reversesAdjustment]) }}">{{ $adjustment->reversesAdjustment->adjustment_number }}</a></div>@endif
        @if($adjustment->reversal)<div class="mt-4 text-[11px]">Reversed by <a class="font-medium underline" href="{{ route('admin.vendor-invoices.adjustments.show', [$invoice, $adjustment->reversal]) }}">{{ $adjustment->reversal->adjustment_number }}</a></div>@endif
    </section>

    @if($adjustment->type === 'metadata_correction')
        <section class="grid gap-4 md:grid-cols-2">
            @foreach(['before' => 'Before', 'after' => 'After'] as $key => $label)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
                    <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ $label }}</div>
                    <dl class="mt-3 space-y-2 text-[11px]">
                        @foreach((array)data_get($adjustment->meta, $key, []) as $field => $value)
                            <div class="grid grid-cols-3 gap-2"><dt class="text-gray-400">{{ str_replace('_', ' ', ucfirst($field)) }}</dt><dd class="col-span-2 break-words text-gray-800 dark:text-gray-200">{{ $value ?: '—' }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </section>
    @elseif($adjustment->items->isNotEmpty())
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800"><div class="text-sm font-semibold">Adjustment lines</div></div>
            <div class="overflow-x-auto"><table class="min-w-full text-[11px]"><thead class="bg-gray-50 dark:bg-gray-950/40"><tr><th class="px-4 py-3 text-left">Product / allocation</th><th class="px-4 py-3 text-right">Taxable value</th><th class="px-4 py-3 text-right">Tax</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($adjustment->items as $item)<tr><td class="px-4 py-3"><div class="font-medium">{{ $item->invoiceItem?->product?->name ?? (data_get($item->meta, 'general_adjustment') ? 'General adjustment' : 'Unallocated') }}</div>@if($item->invoiceItem?->productVariant)<div class="text-[10px] text-gray-400">{{ $item->invoiceItem->productVariant->name }}</div>@endif</td><td class="px-4 py-3 text-right">₹{{ number_format((float)$item->subtotal_delta, 2) }}</td><td class="px-4 py-3 text-right">₹{{ number_format((float)$item->tax_delta, 2) }}</td><td class="px-4 py-3 text-right font-semibold">₹{{ number_format((float)$item->total_delta, 2) }}</td></tr>@endforeach
            </tbody></table></div>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
        <div class="text-[11px] uppercase tracking-wide text-gray-400">Current invoice balance</div>
        <div class="mt-3 grid gap-3 sm:grid-cols-3"><div><div class="text-[10px] text-gray-400">Adjusted payable</div><div class="font-semibold">₹{{ number_format($balance['adjusted_total'], 2) }}</div></div><div><div class="text-[10px] text-gray-400">Paid</div><div class="font-semibold">₹{{ number_format($balance['paid'], 2) }}</div></div><div><div class="text-[10px] text-gray-400">Outstanding / vendor credit</div><div class="font-semibold">@if($balance['vendor_credit_due'] > 0) Vendor credit ₹{{ number_format($balance['vendor_credit_due'], 2) }} @else ₹{{ number_format($balance['outstanding'], 2) }} @endif</div></div></div>
    </section>

    @if($canReverse)
        <form method="POST" action="{{ route('admin.vendor-invoices.adjustments.reverse', [$invoice, $adjustment]) }}" class="rounded-2xl border border-red-200 dark:border-red-900 bg-red-50/60 dark:bg-red-950/20 p-5" onsubmit="return confirm('Create an opposite adjustment to reverse this posted entry?')">
            @csrf
            <div class="text-sm font-semibold text-red-900 dark:text-red-200">Reverse this financial adjustment</div>
            <p class="mt-1 text-[11px] text-red-700 dark:text-red-300">The original adjustment remains in the audit trail. A new opposite entry will be posted.</p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row"><input name="reversal_reason" required maxlength="500" placeholder="Reason for reversal" class="flex-1 rounded-lg border border-red-300 dark:border-red-800 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]"><button class="rounded-lg border border-red-600 px-4 py-2 text-[11px] font-semibold text-red-700 dark:text-red-300">Reverse adjustment</button></div>
        </form>
    @endif
</div>
@endsection

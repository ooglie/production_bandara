@extends('layouts.company')

@section('title', 'Record Purchase Return')
@section('breadcrumb', 'Admin · Vendor Invoices · Purchase return')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400">{{ $invoice->vendor?->name }} · {{ $invoice->invoice_number }}</div>
            <h1 class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">Record purchase return</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">Select only stock that is still available in the original inward lot. Sold, transformed, held or reserved stock is not returnable.</p>
        </div>
        <a href="{{ route('admin.vendor-invoices.show', $invoice) }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px]">Back</a>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200"><ul class="list-disc pl-4 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.vendor-invoices.returns.store', $invoice) }}" class="space-y-4">
        @csrf
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div><label class="block mb-1 text-[11px] font-medium">Return date</label><input type="date" name="return_date" value="{{ old('return_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                <div><label class="block mb-1 text-[11px] font-medium">Return / challan reference</label><input name="reference_number" maxlength="120" value="{{ old('reference_number') }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                <div><label class="block mb-1 text-[11px] font-medium">Reason</label><input name="reason" maxlength="500" required value="{{ old('reason') }}" placeholder="Damaged goods, quality issue, shortage…" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
            </div>
            <div><label class="block mb-1 text-[11px] font-medium">Notes</label><textarea name="notes" rows="2" maxlength="10000" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2">{{ old('notes') }}</textarea></div>
        </section>

        <section class="space-y-3">
            @foreach($options as $invoiceItemId => $option)
                @php
                    $item = $option['item'];
                    $lot = $option['lot'];
                    $mode = $option['mode'];
                    $blocked = !empty($option['blockers']);
                    $variant = $item->productVariant?->name;
                @endphp
                <article class="rounded-2xl border {{ $blocked ? 'border-gray-200 dark:border-gray-800 opacity-75' : 'border-gray-200 dark:border-gray-800' }} bg-white dark:bg-gray-900 overflow-hidden">
                    <div class="flex flex-col gap-2 border-b border-gray-200 dark:border-gray-800 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $item->product?->name ?? 'Product' }} @if($variant)<span class="font-normal text-gray-500">· {{ $variant }}</span>@endif</div>
                            <div class="mt-1 text-[10px] text-gray-400">Invoice line #{{ $item->id }} · {{ $lot?->lot_code ?? 'No linked lot' }} · {{ ucfirst($mode) }} return</div>
                        </div>
                        <div class="text-right text-[11px] text-gray-500">
                            @if($mode === 'whole_piece')
                                Whole piece {{ number_format((float)$option['max_weight_kg'], 3) }} kg
                            @elseif($mode === 'weight' || $mode === 'pieces')
                                Maximum {{ number_format((float)$option['max_weight_kg'], 3) }} kg
                            @else
                                Maximum {{ number_format((float)$option['max_quantity'], 3) }} units
                            @endif
                        </div>
                    </div>

                    @if($blocked)
                        <div class="px-5 py-4"><div class="text-[11px] font-medium text-gray-600 dark:text-gray-300">This line is not currently returnable:</div><ul class="mt-2 list-disc pl-5 text-[11px] text-gray-500 dark:text-gray-400">@foreach($option['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul></div>
                    @elseif($mode === 'pieces')
                        <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($option['returnable_pieces'] as $piece)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <input type="checkbox" name="items[{{ $invoiceItemId }}][piece_ids][]" value="{{ $piece->id }}" @checked(in_array($piece->id, (array)old('items.'.$invoiceItemId.'.piece_ids', []))) class="mt-0.5 rounded border-gray-300">
                                    <span><span class="block font-medium">{{ $piece->label ?: ('Piece '.$piece->piece_no) }}</span><span class="text-[10px] text-gray-400">{{ number_format((float)($piece->available_weight_kg ?? $piece->weight_kg), 3) }} kg</span></span>
                                </label>
                            @endforeach
                        </div>
                    @elseif($mode === 'packs')
                        <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($option['returnable_packs'] as $pack)
                                <label class="flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <input type="checkbox" name="items[{{ $invoiceItemId }}][pack_ids][]" value="{{ $pack->id }}" @checked(in_array($pack->id, (array)old('items.'.$invoiceItemId.'.pack_ids', []))) class="mt-0.5 rounded border-gray-300">
                                    <span><span class="block font-medium">{{ $pack->pack_code ?: ('Pack #'.$pack->id) }}</span><span class="text-[10px] text-gray-400">{{ number_format((float)($pack->available_pack_quantity ?? 1), 3) }} pack · {{ number_format((float)($pack->actual_weight_kg ?? $pack->total_weight_kg ?? 0), 3) }} kg</span></span>
                                </label>
                            @endforeach
                        </div>
                    @elseif($mode === 'whole_piece')
                        <div class="p-5">
                            <input type="hidden" name="items[{{ $invoiceItemId }}][whole_piece]" value="0">
                            <label class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50">
                                <input type="checkbox"
                                       name="items[{{ $invoiceItemId }}][whole_piece]"
                                       value="1"
                                       @checked(old('items.'.$invoiceItemId.'.whole_piece'))
                                       class="mt-0.5 h-5 w-5 rounded border-gray-300">
                                <span>
                                    <span class="block font-medium text-gray-900 dark:text-gray-50">Return the entire piece</span>
                                    <span class="mt-1 block text-[10px] text-gray-400">{{ number_format((float)$option['max_weight_kg'], 3) }} kg · partial-weight return is not permitted for this item.</span>
                                </span>
                            </label>
                        </div>
                    @elseif($mode === 'weight')
                        <div class="p-5 max-w-sm"><label class="block mb-1 text-[11px] font-medium">Weight to return (kg)</label><input type="number" name="items[{{ $invoiceItemId }}][weight_kg]" min="0" max="{{ $option['max_weight_kg'] }}" step="0.001" value="{{ old('items.'.$invoiceItemId.'.weight_kg') }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"><input type="hidden" name="items[{{ $invoiceItemId }}][piece_count]" value="0"></div>
                    @else
                        <div class="p-5 max-w-sm"><label class="block mb-1 text-[11px] font-medium">Quantity to return</label><input type="number" name="items[{{ $invoiceItemId }}][quantity]" min="0" max="{{ $option['max_quantity'] }}" step="0.001" value="{{ old('items.'.$invoiceItemId.'.quantity') }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5" x-data="{ received: {{ old('credit_note_received') ? 'true' : 'false' }} }">
            <label class="flex items-start gap-3"><input type="hidden" name="credit_note_received" value="0"><input type="checkbox" name="credit_note_received" value="1" x-model="received" class="mt-0.5 rounded border-gray-300"><span><span class="block text-[12px] font-medium">Supplier credit note already received</span><span class="text-[10px] text-gray-400">When selected, posting the physical return will also reduce the supplier payable.</span></span></label>
            <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="received" x-cloak>
                <div><label class="block mb-1 text-[11px] font-medium">Supplier credit-note number</label><input name="supplier_credit_note_number" maxlength="120" value="{{ old('supplier_credit_note_number') }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
                <div><label class="block mb-1 text-[11px] font-medium">Credit-note date</label><input type="date" name="supplier_credit_note_date" value="{{ old('supplier_credit_note_date') }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2"></div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3 rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50/60 dark:bg-amber-950/20 p-4">
            <div class="text-[11px] text-amber-800 dark:text-amber-300"><strong>Nothing is changed yet.</strong> This creates a draft. Stock is reduced only after you review and post it.</div>
            <button class="shrink-0 rounded-xl bg-gray-900 dark:bg-gray-100 px-5 py-2.5 text-[12px] font-semibold text-white dark:text-gray-900">Create return draft</button>
        </div>
    </form>
</div>
@endsection

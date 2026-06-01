@extends('layouts.company')

@section('title', 'Create Inventory Packs')

@section('content')
@php
    $selectedLotId = $selectedLotId ?? old('source_inventory_lot_id');
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Create inventory packs</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Example: receive 1 box of 100 pieces, then repack it into 5 × 20pc packs or 10 × 10pc packs.
            </p>
        </div>
        <a href="{{ route('admin.inventory.packs.index') }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Back</a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
            <div class="font-semibold">Please fix the following:</div>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory.packs.store') }}" class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Source lot</label>
                <select id="source_inventory_lot_id" name="source_inventory_lot_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Select source lot…</option>
                    @foreach($lots as $lot)
                        <option value="{{ $lot->id }}"
                                data-product-id="{{ $lot->product_id }}"
                                data-product-name="{{ $lot->product?->name }}"
                                data-available-qty="{{ (float) ($lot->available_quantity ?? 0) }}"
                                data-available-weight="{{ (float) ($lot->available_weight_kg ?? 0) }}"
                                data-batch="{{ $lot->batch_code }}"
                                data-expiry="{{ optional($lot->expiry_date)->format('Y-m-d') }}"
                                @selected((string) $selectedLotId === (string) $lot->id)>
                            {{ $lot->product?->name ?? 'Product #' . $lot->product_id }} · {{ $lot->lot_code ?: ('Lot #' . $lot->id) }} · Qty {{ rtrim(rtrim(number_format((float) ($lot->available_quantity ?? 0), 3), '0'), '.') }} · {{ rtrim(rtrim(number_format((float) ($lot->available_weight_kg ?? 0), 3), '0'), '.') }} kg
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Source lot must be marked as repackable on the product/inventory settings.
                </p>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Target sellable unit</label>
                <select id="product_sell_unit_id" name="product_sell_unit_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Select sellable unit…</option>
                    @foreach($sellUnits as $unit)
                        <option value="{{ $unit->id }}"
                                data-product-id="{{ $unit->product_id }}"
                                data-pieces="{{ (float) ($unit->pieces_per_unit ?? 0) }}"
                                data-variant-count="{{ $unit->variants->count() }}"
                                @selected((string) old('product_sell_unit_id') === (string) $unit->id)>
                            {{ $unit->product?->name }} · {{ $unit->display_label ?? $unit->name }}
                            @if($unit->variants->count() !== 1)
                                · link variant first
                            @endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Target sellable unit must be linked to exactly one variant so stock can increase safely.
                </p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pack count to create</label>
                <input id="pack_count" name="pack_count" type="number" min="1" step="1" value="{{ old('pack_count', 1) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Example: 5 packs.</p>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pieces per target pack</label>
                <input id="pieces_per_pack" name="pieces_per_pack" type="number" min="0.001" step="0.001" value="{{ old('pieces_per_pack') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Auto-filled from sellable unit when defined.</p>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Source pieces per source unit</label>
                <input id="source_pieces_per_unit" name="source_pieces_per_unit" type="number" min="0.001" step="0.001" value="{{ old('source_pieces_per_unit', 1) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">For 1 source box = 100 pieces, enter 100.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Packed date</label>
                <input name="packed_date" type="date" value="{{ old('packed_date', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Expiry date</label>
                <input id="expiry_date" name="expiry_date" type="date" value="{{ old('expiry_date') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Batch code</label>
                <input id="batch_code" name="batch_code" type="text" value="{{ old('batch_code') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>

        <div>
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">{{ old('notes') }}</textarea>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="font-semibold text-gray-900 dark:text-gray-50">Preview</div>
            <div class="mt-1" id="source_qty_preview">Select a source lot and sellable unit.</div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.inventory.packs.index') }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Cancel</a>
            <button class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Create packs</button>
        </div>
    </form>

    @if($recentPacks->isNotEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recent packs</h2>
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach($recentPacks as $pack)
                    <div class="rounded-lg border border-gray-200 p-3 text-[11px] dark:border-gray-800">
                        <div class="font-medium text-gray-900 dark:text-gray-50">{{ $pack->product?->name ?? 'Product #' . $pack->product_id }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $pack->sellUnit?->display_label ?? '—' }} · {{ $pack->status }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    const source = document.getElementById('source_inventory_lot_id');
    const unit = document.getElementById('product_sell_unit_id');
    const packCount = document.getElementById('pack_count');
    const pieces = document.getElementById('pieces_per_pack');
    const sourcePieces = document.getElementById('source_pieces_per_unit');
    const preview = document.getElementById('source_qty_preview');
    const batch = document.getElementById('batch_code');
    const expiry = document.getElementById('expiry_date');

    function selectedOption(select) {
        return select && select.options[select.selectedIndex] ? select.options[select.selectedIndex] : null;
    }

    function updateUnitOptions() {
        const src = selectedOption(source);
        const productId = src ? src.dataset.productId : '';

        Array.from(unit.options).forEach((opt) => {
            if (!opt.value) return;
            opt.hidden = productId && opt.dataset.productId !== productId;
        });

        const selected = selectedOption(unit);
        if (selected && selected.value && selected.hidden) {
            unit.value = '';
        }

        if (src) {
            if (!batch.value && src.dataset.batch) batch.value = src.dataset.batch;
            if (!expiry.value && src.dataset.expiry) expiry.value = src.dataset.expiry;
        }

        updatePiecesFromUnit();
        updatePreview();
    }

    function updatePiecesFromUnit() {
        const selected = selectedOption(unit);
        if (!selected || !selected.value) return;

        const unitPieces = parseFloat(selected.dataset.pieces || '0');
        if (unitPieces > 0 && !pieces.value) {
            pieces.value = unitPieces.toString();
        }
    }

    function updatePreview() {
        const count = parseFloat(packCount.value || '0');
        const pp = parseFloat(pieces.value || '0');
        const sourcePpu = parseFloat(sourcePieces.value || '1') || 1;
        const sourceUnitsNeeded = (count * pp) / sourcePpu;
        const sourcePiecesNeeded = count * pp;
        const src = selectedOption(source);
        const available = src ? parseFloat(src.dataset.availableQty || '0') : 0;
        const selectedUnit = selectedOption(unit);
        const variantCount = selectedUnit ? parseInt(selectedUnit.dataset.variantCount || '0', 10) : 0;

        if (sourceUnitsNeeded > 0) {
            const enough = available + 0.0005 >= sourceUnitsNeeded;
            preview.innerHTML =
                '<div>Creates <strong>' + count + '</strong> pack(s).</div>' +
                '<div>Consumes <strong>' + sourceUnitsNeeded.toLocaleString(undefined, { maximumFractionDigits: 3 }) + '</strong> source unit(s), equal to <strong>' + sourcePiecesNeeded.toLocaleString(undefined, { maximumFractionDigits: 3 }) + '</strong> piece(s).</div>' +
                '<div>Available source quantity: <strong>' + available.toLocaleString(undefined, { maximumFractionDigits: 3 }) + '</strong>.</div>' +
                (variantCount === 1 ? '<div>Linked variant stock will increase by <strong>' + count + '</strong>.</div>' : '<div class="mt-1 text-red-700">Target sellable unit must be linked to exactly one variant.</div>') +
                (!enough ? '<div class="mt-1 text-red-700">Not enough source quantity available.</div>' : '');
        } else {
            preview.textContent = 'Select a source lot and sellable unit.';
        }
    }

    source?.addEventListener('change', updateUnitOptions);
    unit?.addEventListener('change', function () { updatePiecesFromUnit(); updatePreview(); });
    packCount?.addEventListener('input', updatePreview);
    pieces?.addEventListener('input', updatePreview);
    sourcePieces?.addEventListener('input', updatePreview);

    updateUnitOptions();
})();
</script>
@endsection

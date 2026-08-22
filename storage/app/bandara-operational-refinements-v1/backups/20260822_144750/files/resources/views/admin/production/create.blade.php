@extends('layouts.company')

@section('title', 'New Production Run')

@section('content')
@php
    $lotMeta = $inputLots->mapWithKeys(function ($lot) {
        return [$lot->id => [
            'id' => (int) $lot->id,
            'product_id' => (int) $lot->product_id,
            'product_name' => (string) ($lot->product->name ?? '—'),
            'lot_code' => (string) ($lot->lot_code ?: ('LOT-' . $lot->id)),
            'lot_stage' => (string) ($lot->lot_stage ?? 'raw'),
            'inward_mode' => (string) ($lot->inward_mode ?? 'qty'),
            'available_weight_kg' => (float) ($lot->available_weight_kg ?? 0),
            'available_quantity' => (float) ($lot->available_quantity ?? 0),
            'available_piece_count' => (int) ($lot->available_piece_count ?? 0),
            'batch_code' => (string) ($lot->batch_code ?? ''),
            'expiry_date' => $lot->expiry_date ? $lot->expiry_date->format('Y-m-d') : '',
            'sell_unit' => (string) ($lot->product->sell_unit ?? 'piece'),
            'pieces' => ($lot->pieces ?? collect())->map(function ($piece) {
                return [
                    'id' => (int) $piece->id,
                    'piece_no' => (int) $piece->piece_no,
                    'weight_kg' => (float) $piece->weight_kg,
                ];
            })->values()->all(),
        ]];
    })->all();

    $outputProductMeta = $outputProducts->mapWithKeys(function ($p) {
        return [$p->id => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'lot_stage_default' => (string) ($p->lot_stage_default ?? ''),
            'sell_unit' => (string) ($p->sell_unit ?? 'piece'),
            'inventory_is_saleable' => (bool) ($p->inventory_is_saleable ?? true),
            'inventory_can_repack' => (bool) ($p->inventory_can_repack ?? false),
        ]];
    })->all();

    $trimWasteMeta = $trimWasteProducts->mapWithKeys(function ($p) {
        return [$p->id => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'lot_stage_default' => (string) ($p->lot_stage_default ?? ''),
            'sell_unit' => (string) ($p->sell_unit ?? 'piece'),
        ]];
    })->all();

    $oldOutputs = old('outputs', []);
    $oldInputProductId = old('input_product_id');
    $oldInputLotId = old('input_lot_id');
    $oldSelectedPieceIds = collect(old('selected_piece_ids', []))->map(fn($id) => (int) $id)->values()->all();

    $trimProducts = ($trimWasteProducts ?? collect())
        ->where('lot_stage_default', 'trim')
        ->values();

    $wasteProducts = ($trimWasteProducts ?? collect())
        ->where('lot_stage_default', 'waste')
        ->values();

    $showByproductLots = $trimProducts->isNotEmpty() || $wasteProducts->isNotEmpty();
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">New production run</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Select a product first, then choose a lot, then choose pieces if the lot is piece-based.
            </p>
        </div>

        <a href="{{ route('admin.production.index') }}"
           class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-[12px] text-red-800">
            <div class="font-medium mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.production.store') }}" class="space-y-4">
        @csrf

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Step 1</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Run setup</div>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Run date</label>
                        <input type="date" name="run_date"
                               value="{{ old('run_date', now()->format('Y-m-d')) }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                               required>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Run type</label>
                        <select name="run_type" id="run_type"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                                required>
                            <option value="raw_to_slab" @selected(old('run_type') === 'raw_to_slab')>Raw → Slab</option>
                            <option value="slab_to_slice" @selected(old('run_type') === 'slab_to_slice')>Slab → Slice</option>
                            <option value="raw_to_slice_direct" @selected(old('run_type') === 'raw_to_slice_direct')>Raw → Slice Direct</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Input product</label>
                        <select name="input_product_id" id="input_product_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                            <option value="">Select product…</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Input lot</label>
                        <select name="input_lot_id" id="input_lot_id"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                                required>
                            <option value="">Select lot…</option>
                        </select>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Lots shown here are filtered by product and run type.
                        </div>
                    </div>

                    <div id="input-lot-summary"
                         class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[12px] text-gray-600 dark:text-gray-300">
                        Select a run type, input product, and input lot to view available balance.
                    </div>
                </div>

                <div id="piece-picker-wrap" class="hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Select pieces from this lot</div>
                            <div class="text-[11px] text-gray-500 dark:text-gray-400">Only selected pieces will be consumed in this run.</div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" id="select-all-pieces"
                                    class="text-[11px] px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-900">
                                Select all
                            </button>
                            <button type="button" id="clear-all-pieces"
                                    class="text-[11px] px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-900">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div id="pieces-list" class="grid gap-2 md:grid-cols-2"></div>

                    <div id="pieces-summary"
                         class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2 text-[12px] text-gray-700 dark:text-gray-200">
                        No pieces selected.
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Consumed weight (kg)</label>
                        <input type="number" step="0.001" min="0.001" name="consumed_weight_kg" id="consumed_weight_kg"
                               value="{{ old('consumed_weight_kg') }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Consumed quantity</label>
                        <input type="number" step="0.001" min="0" name="consumed_quantity" id="consumed_quantity"
                               value="{{ old('consumed_quantity') }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Auto-calculated for piece-based lots.
                        </div>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim weight (kg)</label>
                        <input type="number" step="0.001" min="0" name="trim_weight_kg"
                            value="{{ old('trim_weight_kg', 0) }}"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">

                        @if($trimProducts->isNotEmpty())
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                You can keep this as summary only, or choose a trim product below to create a trim lot.
                            </div>
                        @else
                            <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                No trim product configured yet. This will be recorded as summary only.
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste weight (kg)</label>
                        <input type="number" step="0.001" min="0" name="waste_weight_kg"
                            value="{{ old('waste_weight_kg', 0) }}"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">

                        @if($wasteProducts->isNotEmpty())
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                You can keep this as summary only, or choose a waste product below to create a waste lot.
                            </div>
                        @else
                            <div class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                No waste product configured yet. This will be recorded as summary only.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Optional trim / waste lots --}}
                                @if($showByproductLots)
                    {{-- Optional trim / waste lots --}}
                    <div class="grid gap-4 {{ $trimProducts->isNotEmpty() && $wasteProducts->isNotEmpty() ? 'lg:grid-cols-2' : 'lg:grid-cols-1' }}">
                        @if($trimProducts->isNotEmpty())
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                                <div>
                                    <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Optional trim lot</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        If left blank, trim remains summary only and no trim inventory lot is created.
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim product</label>
                                        <select name="trim_product_id"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                            <option value="">— None —</option>
                                            @foreach($trimProducts as $product)
                                                <option value="{{ $product->id }}" @selected((int) old('trim_product_id', 0) === (int) $product->id)>
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim quantity</label>
                                        <input type="number" step="0.001" min="0"
                                               name="trim_quantity_output"
                                               value="{{ old('trim_quantity_output') }}"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Trim notes</label>
                                        <input type="text"
                                               name="trim_notes"
                                               value="{{ old('trim_notes') }}"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($wasteProducts->isNotEmpty())
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4 space-y-3">
                                <div>
                                    <div class="text-[12px] font-medium text-gray-700 dark:text-gray-300">Optional waste lot</div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                        If left blank, waste remains summary only and no waste inventory lot is created.
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste product</label>
                                        <select name="waste_product_id"
                                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                            <option value="">— None —</option>
                                            @foreach($wasteProducts as $product)
                                                <option value="{{ $product->id }}" @selected((int) old('waste_product_id', 0) === (int) $product->id)>
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste quantity</label>
                                        <input type="number" step="0.001" min="0"
                                               name="waste_quantity_output"
                                               value="{{ old('waste_quantity_output') }}"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>

                                    <div>
                                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Waste notes</label>
                                        <input type="text"
                                               name="waste_notes"
                                               value="{{ old('waste_notes') }}"
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Step 2</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Output lots</div>
                </div>

                <button type="button" id="add-output-row"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">
                    <span class="text-lg leading-none">+</span> Add output
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div id="outputs-container" class="space-y-4"></div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3 text-[11px] text-gray-500 dark:text-gray-400">
                    <strong>Tip:</strong> Use <strong>Individual weights</strong> when slabs are variable-sized.
                    Quantity and total weight will be auto-calculated from the entered lines.
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('admin.production.index') }}"
               class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>

            <button type="submit"
                    class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-6 py-2 text-[13px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                Complete production run
            </button>
        </div>
    </form>
</div>

<template id="output-row-template">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/20 p-4 space-y-4" data-output-row="1">
        <div class="flex items-center justify-between">
            <div class="text-[13px] font-semibold text-gray-900 dark:text-gray-50">Output row</div>
            <button type="button"
                    class="remove-output-row text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Remove
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Output product</label>
                <select class="output-product w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                        name="outputs[__INDEX__][product_id]" required>
                    <option value="">Select output product…</option>
                </select>
                <div class="output-product-hint mt-1 text-[11px] text-gray-500 dark:text-gray-400"></div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Variant (optional)</label>
                <input type="number"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="outputs[__INDEX__][product_variant_id]"
                       placeholder="Variant ID">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Output mode</label>
                <select name="outputs[__INDEX__][output_mode]"
                        class="output-mode w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    <option value="qty">Standard qty</option>
                    <option value="pieces">Individual weights</option>
                </select>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Produced quantity</label>
                <input type="number" step="0.001" min="0.001"
                       name="outputs[__INDEX__][produced_quantity]"
                       class="produced-quantity w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       required>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Produced weight (kg)</label>
                <input type="number" step="0.001" min="0.001"
                       name="outputs[__INDEX__][produced_weight_kg]"
                       class="produced-weight w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       required>
            </div>

            <div class="pack-size-wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pack size (kg, optional)</label>
                <input type="number" step="0.001" min="0"
                       name="outputs[__INDEX__][pack_size_kg]"
                       class="pack-size w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
            </div>
        </div>

        <div class="piece-count-wrap hidden">
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Piece count</label>
            <input type="number" min="0"
                   name="outputs[__INDEX__][piece_count]"
                   class="piece-count w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                   readonly>
        </div>

        <div class="piece-weights-wrap hidden">
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Individual weights (kg) — one per line
            </label>
            <textarea name="outputs[__INDEX__][piece_weights]"
                      rows="4"
                      class="piece-weights w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                      placeholder="4.250&#10;4.700&#10;5.000&#10;5.000"></textarea>
            <div class="piece-weights-summary mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                Enter one slab / piece weight per line.
            </div>
        </div>

        <div>
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (optional)</label>
            <input type="text"
                   name="outputs[__INDEX__][notes]"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
        </div>
    </div>
</template>

<script>
(function () {
    const lotMeta = @json($lotMeta);
    const outputProductMeta = @json($outputProductMeta);
    const oldOutputs = @json($oldOutputs);
    const oldInputProductId = @json($oldInputProductId);
    const oldInputLotId = @json($oldInputLotId);
    const oldSelectedPieceIds = @json($oldSelectedPieceIds);

    const runTypeEl = document.getElementById('run_type');
    const inputProductEl = document.getElementById('input_product_id');
    const inputLotEl = document.getElementById('input_lot_id');
    const inputLotSummary = document.getElementById('input-lot-summary');
    const pieceWrap = document.getElementById('piece-picker-wrap');
    const pieceList = document.getElementById('pieces-list');
    const pieceSummary = document.getElementById('pieces-summary');
    const selectAllPiecesBtn = document.getElementById('select-all-pieces');
    const clearAllPiecesBtn = document.getElementById('clear-all-pieces');
    const consumedWeightEl = document.getElementById('consumed_weight_kg');
    const consumedQuantityEl = document.getElementById('consumed_quantity');
    const outputsContainer = document.getElementById('outputs-container');
    const addOutputBtn = document.getElementById('add-output-row');
    const tpl = document.getElementById('output-row-template');

    let outputIndex = 0;

    function expectedInputStage() {
        return runTypeEl.value === 'slab_to_slice' ? 'slab' : 'raw';
    }

    function expectedOutputStage() {
        return runTypeEl.value === 'raw_to_slab' ? 'slab' : 'slice';
    }

    function groupProductsForStage(stage) {
        const seen = new Map();

        Object.values(lotMeta).forEach(function (lot) {
            if (lot.lot_stage !== stage) return;

            if (!seen.has(lot.product_id)) {
                seen.set(lot.product_id, {
                    id: lot.product_id,
                    name: lot.product_name,
                });
            }
        });

        return Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name));
    }

    function lotsForSelection(productId, stage) {
        return Object.values(lotMeta)
            .filter(function (lot) {
                return String(lot.product_id) === String(productId) && lot.lot_stage === stage;
            })
            .sort(function (a, b) {
                return String(a.lot_code).localeCompare(String(b.lot_code));
            });
    }

    function buildProductOptions(selected = null) {
        const stage = expectedInputStage();
        const products = groupProductsForStage(stage);

        inputProductEl.innerHTML = '<option value="">Select product…</option>';

        products.forEach(function (product) {
            const opt = document.createElement('option');
            opt.value = product.id;
            opt.textContent = product.name;
            if (String(selected) === String(product.id)) {
                opt.selected = true;
            }
            inputProductEl.appendChild(opt);
        });

        if (selected && !products.some(p => String(p.id) === String(selected))) {
            inputProductEl.value = '';
        }
    }

    function formatLotLabel(lot) {
        const piecesText = lot.available_piece_count > 0
            ? (lot.available_piece_count + ' pcs · ')
            : '';

        const batchText = lot.batch_code ? (' · Batch ' + lot.batch_code) : '';
        const expiryText = lot.expiry_date ? (' · Exp ' + lot.expiry_date) : '';

        return lot.lot_code + ' · ' + piecesText + Number(lot.available_weight_kg || 0).toFixed(3) + ' kg' + batchText + expiryText;
    }

    function buildLotOptions(selected = null) {
        const stage = expectedInputStage();
        const productId = inputProductEl.value;

        inputLotEl.innerHTML = '<option value="">Select lot…</option>';

        if (!productId) return;

        const lots = lotsForSelection(productId, stage);

        lots.forEach(function (lot) {
            const opt = document.createElement('option');
            opt.value = lot.id;
            opt.textContent = formatLotLabel(lot);

            if (String(selected) === String(lot.id)) {
                opt.selected = true;
            }

            inputLotEl.appendChild(opt);
        });

        if (selected && !lots.some(l => String(l.id) === String(selected))) {
            inputLotEl.value = '';
        }
    }

    function refreshInputLotSummary() {
        const lot = lotMeta[inputLotEl.value];

        if (!lot) {
            inputLotSummary.textContent = 'Select a run type, input product, and input lot to view available balance.';
            return;
        }

        inputLotSummary.innerHTML =
            '<div class="font-medium text-gray-900 dark:text-gray-50">' + lot.product_name + '</div>' +
            '<div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">' +
                'Lot: ' + lot.lot_code +
                ' · Stage: ' + lot.lot_stage.toUpperCase() +
                ' · Mode: ' + lot.inward_mode.toUpperCase() +
                ' · Available weight: ' + Number(lot.available_weight_kg || 0).toFixed(3) + ' kg' +
                ' · Available quantity: ' + Number(lot.available_quantity || 0).toFixed(3) +
                (lot.available_piece_count ? ' · Pieces: ' + lot.available_piece_count : '') +
                (lot.batch_code ? ' · Batch: ' + lot.batch_code : '') +
                (lot.expiry_date ? ' · Expiry: ' + lot.expiry_date : '') +
            '</div>';
    }

    function setConsumedFieldsReadonly(readonly) {
        consumedWeightEl.readOnly = readonly;
        consumedQuantityEl.readOnly = readonly;

        if (readonly) {
            consumedWeightEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
            consumedQuantityEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
        } else {
            consumedWeightEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
            consumedQuantityEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
        }
    }

    function selectedPieceCheckboxes() {
        return Array.from(pieceList.querySelectorAll('input[type="checkbox"][name="selected_piece_ids[]"]'));
    }

    function refreshPieceSummary() {
        const lot = lotMeta[inputLotEl.value];
        const boxes = selectedPieceCheckboxes().filter(cb => cb.checked);

        if (!lot || boxes.length === 0) {
            pieceSummary.textContent = 'No pieces selected.';
            consumedWeightEl.value = '';
            consumedQuantityEl.value = '';
            return;
        }

        let totalWeight = 0;
        boxes.forEach(function (box) {
            totalWeight += Number(box.dataset.weight || 0);
        });

        const pieceCount = boxes.length;

        pieceSummary.textContent = pieceCount + ' piece(s) selected · ' + totalWeight.toFixed(3) + ' kg';
        consumedWeightEl.value = totalWeight.toFixed(3);

        consumedQuantityEl.value = lot.lot_stage === 'raw'
            ? totalWeight.toFixed(3)
            : String(pieceCount);
    }

    function renderPiecePicker() {
        const lot = lotMeta[inputLotEl.value];

        pieceList.innerHTML = '';

        if (!lot || lot.inward_mode !== 'pieces' || !Array.isArray(lot.pieces) || lot.pieces.length === 0) {
            pieceWrap.classList.add('hidden');
            setConsumedFieldsReadonly(false);
            return;
        }

        pieceWrap.classList.remove('hidden');
        setConsumedFieldsReadonly(true);

        lot.pieces.forEach(function (piece) {
            const row = document.createElement('label');
            row.className = 'flex items-center justify-between gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-2';

            const left = document.createElement('div');
            left.className = 'flex items-center gap-2';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'selected_piece_ids[]';
            checkbox.value = piece.id;
            checkbox.dataset.weight = piece.weight_kg;
            checkbox.className = 'rounded border-gray-300 dark:border-gray-700';

            if (oldSelectedPieceIds.map(String).includes(String(piece.id))) {
                checkbox.checked = true;
            }

            checkbox.addEventListener('change', refreshPieceSummary);

            const text = document.createElement('span');
            text.className = 'text-[12px] text-gray-800 dark:text-gray-200';
            text.textContent = 'Piece ' + piece.piece_no;

            left.appendChild(checkbox);
            left.appendChild(text);

            const weight = document.createElement('span');
            weight.className = 'text-[12px] font-medium text-gray-700 dark:text-gray-300';
            weight.textContent = Number(piece.weight_kg).toFixed(3) + ' kg';

            row.appendChild(left);
            row.appendChild(weight);

            pieceList.appendChild(row);
        });

        refreshPieceSummary();
    }

    function buildOutputProductOptions(selectEl, selected = null) {
        const expectedStage = expectedOutputStage();

        selectEl.innerHTML = '<option value="">Select output product…</option>';

        Object.values(outputProductMeta)
            .filter(meta => meta.lot_stage_default === expectedStage)
            .sort((a, b) => a.name.localeCompare(b.name))
            .forEach(function (meta) {
                const opt = document.createElement('option');
                opt.value = meta.id;
                opt.textContent = meta.name;

                if (String(selected) === String(meta.id)) {
                    opt.selected = true;
                }

                selectEl.appendChild(opt);
            });
    }

    function updateOutputModeUI(rowEl) {
        const modeEl = rowEl.querySelector('.output-mode');
        const qtyEl = rowEl.querySelector('.produced-quantity');
        const weightEl = rowEl.querySelector('.produced-weight');
        const pieceCountEl = rowEl.querySelector('.piece-count');
        const pieceWeightsEl = rowEl.querySelector('.piece-weights');
        const pieceSummaryEl = rowEl.querySelector('.piece-weights-summary');
        const pieceWrapEl = rowEl.querySelector('.piece-weights-wrap');
        const pieceCountWrapEl = rowEl.querySelector('.piece-count-wrap');
        const packWrapEl = rowEl.querySelector('.pack-size-wrap');

        const mode = modeEl.value || 'qty';

        if (mode === 'pieces') {
            pieceWrapEl.classList.remove('hidden');
            pieceCountWrapEl.classList.remove('hidden');
            packWrapEl.classList.add('hidden');

            qtyEl.readOnly = true;
            weightEl.readOnly = true;
            qtyEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
            weightEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
        } else {
            pieceWrapEl.classList.add('hidden');
            pieceCountWrapEl.classList.add('hidden');
            packWrapEl.classList.remove('hidden');

            qtyEl.readOnly = false;
            weightEl.readOnly = false;
            qtyEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
            weightEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');

            pieceCountEl.value = '';
            pieceWeightsEl.value = '';
            pieceSummaryEl.textContent = 'Enter one slab / piece weight per line.';
        }
    }

    function recalcOutputPieces(rowEl) {
        const modeEl = rowEl.querySelector('.output-mode');
        if (!modeEl || modeEl.value !== 'pieces') return;

        const qtyEl = rowEl.querySelector('.produced-quantity');
        const weightEl = rowEl.querySelector('.produced-weight');
        const pieceCountEl = rowEl.querySelector('.piece-count');
        const pieceWeightsEl = rowEl.querySelector('.piece-weights');
        const pieceSummaryEl = rowEl.querySelector('.piece-weights-summary');

        const lines = String(pieceWeightsEl.value || '')
            .split(/\r\n|\n|\r/)
            .map(v => v.trim())
            .filter(Boolean);

        const weights = [];
        for (const ln of lines) {
            const n = parseFloat(ln);
            if (isFinite(n) && n > 0) {
                weights.push(n);
            }
        }

        const total = weights.reduce((a, b) => a + b, 0);
        qtyEl.value = weights.length ? String(weights.length) : '';
        weightEl.value = weights.length ? total.toFixed(3) : '';
        pieceCountEl.value = weights.length ? String(weights.length) : '';

        pieceSummaryEl.textContent = weights.length
            ? weights.length + ' piece(s) · ' + total.toFixed(3) + ' kg'
            : 'Enter one slab / piece weight per line.';
    }

    function bindOutputRow(rowEl) {
        const removeBtn = rowEl.querySelector('.remove-output-row');
        const productSel = rowEl.querySelector('.output-product');
        const hintEl = rowEl.querySelector('.output-product-hint');
        const modeEl = rowEl.querySelector('.output-mode');
        const pieceWeightsEl = rowEl.querySelector('.piece-weights');

        removeBtn?.addEventListener('click', function () {
            rowEl.remove();
            if (outputsContainer.children.length === 0) addOutputRow();
        });

        productSel?.addEventListener('change', function () {
            const meta = outputProductMeta[this.value] || null;

            if (!meta) {
                hintEl.textContent = '';
                return;
            }

            hintEl.textContent =
                'Stage: ' + (meta.lot_stage_default || '—') +
                ' · Saleable: ' + (meta.inventory_is_saleable ? 'Yes' : 'No') +
                ' · Repackable: ' + (meta.inventory_can_repack ? 'Yes' : 'No');
        });

        modeEl?.addEventListener('change', function () {
            updateOutputModeUI(rowEl);
            recalcOutputPieces(rowEl);
        });

        pieceWeightsEl?.addEventListener('input', function () {
            recalcOutputPieces(rowEl);
        });

        updateOutputModeUI(rowEl);
        recalcOutputPieces(rowEl);
    }

    function refreshSelectors() {
        const currentProduct = inputProductEl.value || oldInputProductId || '';
        const currentLot = inputLotEl.value || oldInputLotId || '';

        buildProductOptions(currentProduct);
        buildLotOptions(currentLot);
        refreshInputLotSummary();
        renderPiecePicker();
        refreshOutputSelects();
    }

    function buildLotOptions(selected = null) {
        const stage = expectedInputStage();
        const productId = inputProductEl.value;

        inputLotEl.innerHTML = '<option value="">Select lot…</option>';

        if (!productId) return;

        const lots = Object.values(lotMeta)
            .filter(function (lot) {
                return String(lot.product_id) === String(productId) && lot.lot_stage === stage;
            })
            .sort(function (a, b) {
                return String(a.lot_code).localeCompare(String(b.lot_code));
            });

        lots.forEach(function (lot) {
            const opt = document.createElement('option');
            opt.value = lot.id;
            opt.textContent = lot.lot_code + ' · ' +
                (lot.available_piece_count > 0 ? (lot.available_piece_count + ' pcs · ') : '') +
                Number(lot.available_weight_kg || 0).toFixed(3) + ' kg' +
                (lot.batch_code ? (' · Batch ' + lot.batch_code) : '') +
                (lot.expiry_date ? (' · Exp ' + lot.expiry_date) : '');

            if (String(selected) === String(lot.id)) {
                opt.selected = true;
            }

            inputLotEl.appendChild(opt);
        });

        if (selected && !lots.some(l => String(l.id) === String(selected))) {
            inputLotEl.value = '';
        }
    }

    function buildProductOptions(selected = null) {
        const stage = expectedInputStage();
        const seen = new Map();

        Object.values(lotMeta).forEach(function (lot) {
            if (lot.lot_stage !== stage) return;

            if (!seen.has(lot.product_id)) {
                seen.set(lot.product_id, {
                    id: lot.product_id,
                    name: lot.product_name,
                });
            }
        });

        const products = Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name));

        inputProductEl.innerHTML = '<option value="">Select product…</option>';

        products.forEach(function (product) {
            const opt = document.createElement('option');
            opt.value = product.id;
            opt.textContent = product.name;

            if (String(selected) === String(product.id)) {
                opt.selected = true;
            }

            inputProductEl.appendChild(opt);
        });

        if (selected && !products.some(p => String(p.id) === String(selected))) {
            inputProductEl.value = '';
        }
    }

    function refreshOutputSelects() {
        outputsContainer.querySelectorAll('[data-output-row="1"]').forEach(function (rowEl) {
            const selectEl = rowEl.querySelector('.output-product');
            const selected = selectEl.dataset.selected || selectEl.value || '';
            buildOutputProductOptions(selectEl, selected);
            selectEl.dispatchEvent(new Event('change'));
        });
    }

    function addOutputRow(prefill = null) {
        const html = tpl.innerHTML.replaceAll('__INDEX__', String(outputIndex));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const rowEl = wrap.firstElementChild;

        outputsContainer.appendChild(rowEl);

        const productSel = rowEl.querySelector('.output-product');
        const modeEl = rowEl.querySelector('.output-mode');

        if (prefill) {
            productSel.dataset.selected = prefill.product_id ?? '';
            modeEl.value = prefill.output_mode ?? 'qty';

            rowEl.querySelector('[name="outputs[' + outputIndex + '][product_variant_id]"]').value = prefill.product_variant_id ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][produced_quantity]"]').value = prefill.produced_quantity ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][produced_weight_kg]"]').value = prefill.produced_weight_kg ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][piece_count]"]').value = prefill.piece_count ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][pack_size_kg]"]').value = prefill.pack_size_kg ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][piece_weights]"]').value = prefill.piece_weights ?? '';
            rowEl.querySelector('[name="outputs[' + outputIndex + '][notes]"]').value = prefill.notes ?? '';
        }

        bindOutputRow(rowEl);
        refreshOutputSelects();

        outputIndex++;
        return rowEl;
    }

    runTypeEl.addEventListener('change', function () {
        inputProductEl.value = '';
        inputLotEl.value = '';
        refreshSelectors();
    });

    inputProductEl.addEventListener('change', function () {
        buildLotOptions();
        refreshInputLotSummary();
        renderPiecePicker();
    });

    inputLotEl.addEventListener('change', function () {
        refreshInputLotSummary();
        renderPiecePicker();
    });

    selectAllPiecesBtn?.addEventListener('click', function () {
        selectedPieceCheckboxes().forEach(cb => cb.checked = true);
        refreshPieceSummary();
    });

    clearAllPiecesBtn?.addEventListener('click', function () {
        selectedPieceCheckboxes().forEach(cb => cb.checked = false);
        refreshPieceSummary();
    });

    addOutputBtn?.addEventListener('click', function () {
        addOutputRow();
    });

    refreshSelectors();

    if (oldOutputs.length > 0) {
        oldOutputs.forEach(function (row) {
            addOutputRow(row);
        });
    } else {
        addOutputRow();
    }
})();
</script>
@endsection
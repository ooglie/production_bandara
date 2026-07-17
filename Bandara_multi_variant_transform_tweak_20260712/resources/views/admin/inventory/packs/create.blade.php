@extends('layouts.company')

@section('title', 'Transform stock')

@section('content')
@php
    $lotSummary = function ($lot): string {
        $parts = [];
        $fullPacks = (int) ($lot->available_pack_count ?? 0);
        $pieces = (float) ($lot->available_piece_count ?? 0);
        $weight = (float) ($lot->available_weight_kg ?? 0);
        $quantity = (float) ($lot->available_quantity ?? 0);

        if ($fullPacks > 0) {
            $parts[] = number_format($fullPacks) . ' unopened pack/carton' . ($fullPacks === 1 ? '' : 's');
        }
        if ($pieces > 0) {
            $parts[] = number_format($pieces, 0) . ' pcs available';
        }
        if ($weight > 0) {
            $parts[] = number_format($weight, 3) . ' kg available';
        }
        if ($fullPacks <= 0 && $pieces <= 0 && $quantity > 0) {
            $parts[] = number_format($quantity, 3) . ' source units';
        }

        return $parts ? implode(' · ', array_unique($parts)) : 'No available quantity';
    };

    $productMeta = collect($outputProducts ?? [])->mapWithKeys(function ($product) {
        return [(string) $product->id => [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'inventory_role' => (string) ($product->inventory_role ?? (($product->is_active ?? false) ? 'saleable' : 'internal')),
            'pack_type' => (string) ($product->pack_type ?? 'quantity'),
            'sell_unit' => (string) ($product->sell_unit ?? 'piece'),
            'product_weight' => $product->product_weight !== null ? (float) $product->product_weight : 0,
            'pieces_per_pack' => $product->pieces_per_pack !== null ? (float) $product->pieces_per_pack : 0,
            'is_active' => (bool) $product->is_active,
        ]];
    });

    $variantMeta = collect($outputVariants ?? [])->mapWithKeys(function ($rows, $productId) {
        return [(string) $productId => collect($rows)->map(function ($variant) {
            $label = trim((string) ($variant->name ?? ''));
            if ($label === '') {
                $packType = (string) ($variant->pack_type ?? '');
                if ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
                    $label = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                } elseif ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
                    $label = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
                } else {
                    $label = (string) ($variant->sku ?? ('Variant ' . $variant->id));
                }
            }

            return [
                'id' => (int) $variant->id,
                'product_id' => (int) $variant->product_id,
                'label' => $label,
                'sku' => (string) ($variant->sku ?? ''),
                'pack_type' => (string) ($variant->pack_type ?? 'quantity'),
                'product_weight' => $variant->product_weight !== null ? (float) $variant->product_weight : 0,
                'pieces_per_pack' => $variant->pieces_per_pack !== null ? (float) $variant->pieces_per_pack : 0,
                'pricing_unit' => (string) ($variant->pricing_unit ?? 'pack'),
                'is_active' => (bool) ($variant->is_active ?? true),
            ];
        })->values()->all()];
    });

    $lotPiecesMeta = collect($lots ?? [])->mapWithKeys(function ($lot) {
        return [(string) $lot->id => collect($lot->pieces ?? [])->map(function ($piece) {
            $available = $piece->available_weight_kg !== null ? (float) $piece->available_weight_kg : (float) ($piece->weight_kg ?? 0);
            return [
                'id' => (int) $piece->id,
                'piece_no' => (int) $piece->piece_no,
                'label' => (string) ($piece->label ?: ('Piece ' . $piece->piece_no)),
                'weight_kg' => $piece->weight_kg !== null ? (float) $piece->weight_kg : null,
                'available_weight_kg' => $available,
                'status' => (string) ($piece->status ?? 'available'),
            ];
        })->filter(fn ($piece) => (float) ($piece['available_weight_kg'] ?? 0) > 0)->values()->all()];
    });

    $transformationTargetsMeta = collect($transformationTargetsBySource ?? [])->mapWithKeys(function ($ids, $sourceId) {
        return [(string) $sourceId => collect($ids)->map(fn ($id) => (int) $id)->values()->all()];
    });

    $sourcePiecesPerUnitForLot = function ($lot): float {
        $value = (float) ($lot->pieces_per_pack ?? 0);
        if ($value <= 0 && $lot->productVariant) {
            $value = (float) ($lot->productVariant->pieces_per_pack ?? 0);
        }
        if ($value <= 0 && $lot->product) {
            $value = (float) ($lot->product->pieces_per_pack ?? 0);
        }
        if ($value <= 0 && (float) ($lot->available_quantity ?? 0) > 0 && (float) ($lot->available_piece_count ?? 0) > 0) {
            $value = (float) $lot->available_piece_count / (float) $lot->available_quantity;
        }

        return $value > 0 ? round($value, 3) : 1.0;
    };

    $initialOutputs = old('outputs');
    if (! is_array($initialOutputs) || $initialOutputs === []) {
        $initialOutputs = [[
            'output_product_id' => old('output_product_id'),
            'output_product_variant_id' => old('output_product_variant_id'),
            'pack_count' => old('pack_count', 1),
            'pieces_per_pack' => old('pieces_per_pack'),
            'output_weight_kg' => old('output_weight_kg'),
        ]];
    }
    $initialOutputs = array_values($initialOutputs);
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Transform stock</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Convert one source lot into one or several finished products or pack variants in a single transaction.
            </p>
        </div>
        <a href="{{ route('admin.inventory.packs.index') }}" class="rounded border border-gray-300 px-3 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Back</a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-200">
            <div class="mb-1 font-semibold">Please fix the following:</div>
            <ul class="list-disc space-y-0.5 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-[12px] text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/25 dark:text-amber-100">
        <div class="font-semibold">Master-carton inward rule</div>
        <div class="mt-1">For one box containing 240 pieces, receive <strong>Stock Qty = 1</strong> and configure the source variant as <strong>240 pieces per pack</strong>. The system will then track one unopened carton plus its 240 internal pieces.</div>
    </div>

    <form method="POST" action="{{ route('admin.inventory.packs.store') }}" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-950" data-transform-stock-form>
        @csrf

        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">1. Source stock</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Choose the carton, bulk lot, piece, or existing stock that will be consumed.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Source lot</label>
                    <select id="source_inventory_lot_id" name="source_inventory_lot_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Select source lot…</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}"
                                    data-product-id="{{ $lot->product_id }}"
                                    data-variant-id="{{ $lot->product_variant_id }}"
                                    data-available-qty="{{ (float) ($lot->available_quantity ?? 0) }}"
                                    data-available-weight="{{ (float) ($lot->available_weight_kg ?? 0) }}"
                                    data-available-pieces="{{ (float) ($lot->available_piece_count ?? 0) }}"
                                    data-available-full-packs="{{ (int) ($lot->available_pack_count ?? 0) }}"
                                    data-source-pieces-per-unit="{{ $sourcePiecesPerUnitForLot($lot) }}"
                                    data-source-unit-weight="{{ (float) ($lot->unit_weight_kg ?? ($lot->productVariant?->product_weight ?? $lot->product?->product_weight ?? 0)) }}"
                                    data-batch="{{ $lot->batch_code }}"
                                    data-expiry="{{ optional($lot->expiry_date)->format('Y-m-d') }}"
                                    data-mode="{{ $lot->inward_mode }}"
                                    @selected((string) $selectedLotId === (string) $lot->id)>
                                {{ $lot->product?->name ?? 'Product #' . $lot->product_id }}
                                @if($lot->productVariant)
                                    — {{ $lot->productVariant->name ?: $lot->productVariant->sku }}
                                @endif
                                · Lot #{{ $lot->id }} · {{ $lotSummary($lot) }}
                                @if($lot->batch_code) · Batch {{ $lot->batch_code }} @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Opened cartons remain available here while they still contain loose pieces.</p>
                </div>

                <div id="source_pieces_wrap">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pieces in one source unit</label>
                    <input id="source_pieces_per_unit" name="source_pieces_per_unit" type="number" min="0.001" step="0.001" value="{{ old('source_pieces_per_unit', 1) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Auto-filled. A 240-piece master carton must show 240 here.</p>
                </div>

                <div id="source_piece_wrap" class="hidden md:col-span-2">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Specific source piece</label>
                    <select id="source_inventory_piece_id" name="source_inventory_piece_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Use whole source lot</option>
                    </select>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Optional for weight-based cutting from a particular belly, fillet, or cheese block.</p>
                </div>
            </div>
        </section>

        <section class="space-y-4 border-t border-gray-200 pt-5 dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">2. Output packs and variants</h2>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Add one row for each finished variant. Example: 20 packs of 10 pcs plus 2 packs of 20 pcs.</p>
                </div>
                <button type="button" id="add_output_row" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">+ Add another output</button>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
                    <input id="show_all_outputs" type="checkbox" class="rounded border-gray-300 dark:border-gray-700">
                    <span>Show all output products, not only mapped transformations</span>
                </label>
                <p id="transformation_hint" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Select a source lot. Configured transformation relationships will filter every output row automatically.</p>
            </div>

            <div id="output_rows" class="space-y-3"></div>
        </section>

        <section class="space-y-4 border-t border-gray-200 pt-5 dark:border-gray-800">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">3. Common pack details</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">These details apply to every output row created in this transform.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Packed date</label>
                    <input name="packed_date" type="date" value="{{ old('packed_date', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Expiry date</label>
                    <input id="expiry_date" name="expiry_date" type="date" value="{{ old('expiry_date') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Batch code</label>
                    <input id="batch_code" name="batch_code" type="text" value="{{ old('batch_code') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">{{ old('notes') }}</textarea>
            </div>
        </section>

        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="font-semibold text-gray-900 dark:text-gray-50">Combined preview</div>
            <div class="mt-2" id="source_qty_preview">Select a source lot and complete at least one output row.</div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.inventory.packs.index') }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Cancel</a>
            <button type="submit" data-transform-stock-submit class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Review & create stock</button>
        </div>
    </form>

    <template id="output_row_template">
        <div data-output-row class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Output <span data-output-number>1</span></div>
                <button type="button" data-remove-output class="rounded border border-gray-200 px-2.5 py-1 text-[11px] text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Remove</button>
            </div>

            <div class="grid gap-4 md:grid-cols-5">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Output product</label>
                    <select data-output-product required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Select finished product…</option>
                        @foreach($outputProducts as $product)
                            @php $packType = (string) ($product->pack_type ?? 'quantity'); @endphp
                            <option value="{{ $product->id }}" data-output-product-id="{{ $product->id }}">
                                {{ $product->name }} @if($product->sku) ({{ $product->sku }}) @endif · {{ str_replace('_', ' ', $packType) }} @unless($product->is_active) · Internal/Draft @endunless
                            </option>
                        @endforeach
                    </select>
                </div>

                <div data-output-variant-wrap class="hidden md:col-span-2">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Output pack variant</label>
                    <select data-output-variant class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900" disabled>
                        <option value="">Product-level stock</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pack count</label>
                    <input data-pack-count type="number" min="1" step="1" value="1" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                </div>

                <div data-pieces-per-pack-wrap class="hidden">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Pieces per pack</label>
                    <input data-pieces-per-pack type="number" min="0.001" step="0.001" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Auto-filled from the selected variant.</p>
                </div>

                <div data-output-weight-wrap class="hidden">
                    <label class="mb-1 block text-[12px] font-medium text-gray-700 dark:text-gray-300">Total output weight kg</label>
                    <input data-output-weight type="number" min="0.001" step="0.001" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Used only for variable/by-kg output.</p>
                </div>

                <div class="md:col-span-5 rounded-lg bg-gray-50 px-3 py-2 text-[11px] text-gray-600 dark:bg-gray-900 dark:text-gray-400" data-output-row-summary>
                    Select an output product or variant.
                </div>
            </div>
        </div>
    </template>

    <div id="transform_confirm_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-950/35 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="transform_confirm_title">
        <div class="w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-gray-800 dark:bg-gray-950">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="transform_confirm_title" class="text-base font-semibold text-gray-900 dark:text-gray-50">Confirm stock transformation</h2>
                    <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">Review all output variants and the combined source consumption.</p>
                </div>
                <button type="button" data-transform-confirm-cancel class="rounded-full border border-gray-200 px-2 py-1 text-xs text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900" aria-label="Close">×</button>
            </div>

            <div id="transform_confirm_summary" class="mt-4 max-h-[60vh] overflow-y-auto rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-[12px] text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                Review summary unavailable.
            </div>

            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" data-transform-confirm-cancel class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">Go back</button>
                <button type="button" data-transform-confirm-submit class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Confirm & create stock</button>
            </div>
        </div>
    </div>

    @if($recentPacks->isNotEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recent packs</h2>
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach($recentPacks as $pack)
                    <div class="rounded-lg border border-gray-200 p-3 text-[11px] dark:border-gray-800">
                        <div class="font-medium text-gray-900 dark:text-gray-50">{{ $pack->product?->name ?? 'Product #' . $pack->product_id }}</div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $pack->pack_code ?? 'Pack #' . $pack->id }} · {{ $pack->status }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
(function () {
    const form = document.querySelector('[data-transform-stock-form]');
    const source = document.getElementById('source_inventory_lot_id');
    const sourcePiece = document.getElementById('source_inventory_piece_id');
    const sourcePieceWrap = document.getElementById('source_piece_wrap');
    const sourcePieces = document.getElementById('source_pieces_per_unit');
    const outputRows = document.getElementById('output_rows');
    const outputTemplate = document.getElementById('output_row_template');
    const addOutputButton = document.getElementById('add_output_row');
    const showAllOutputs = document.getElementById('show_all_outputs');
    const transformationHint = document.getElementById('transformation_hint');
    const preview = document.getElementById('source_qty_preview');
    const batch = document.getElementById('batch_code');
    const expiry = document.getElementById('expiry_date');
    const confirmModal = document.getElementById('transform_confirm_modal');
    const confirmSummary = document.getElementById('transform_confirm_summary');
    const confirmSubmit = document.querySelector('[data-transform-confirm-submit]');
    const confirmCancelButtons = Array.from(document.querySelectorAll('[data-transform-confirm-cancel]'));

    const productMeta = @json($productMeta);
    const variantMeta = @json($variantMeta);
    const lotPiecesMeta = @json($lotPiecesMeta);
    const transformationTargets = @json($transformationTargetsMeta);
    const initialOutputs = @json($initialOutputs);
    const oldSourcePiecesPerUnit = @json(old('source_pieces_per_unit'));

    let confirmedSubmit = false;
    let previewValid = false;

    function n(value) {
        const parsed = parseFloat(String(value ?? '').trim());
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function fmt(value, decimals = 3) {
        return n(value).toLocaleString(undefined, { maximumFractionDigits: decimals });
    }

    function selectedOption(select) {
        return select && select.options[select.selectedIndex] ? select.options[select.selectedIndex] : null;
    }

    function selectedPiece() {
        if (!sourcePiece?.value || !source?.value) return null;
        return (lotPiecesMeta[String(source.value)] || []).find(piece => String(piece.id) === String(sourcePiece.value)) || null;
    }

    function selectedSourceProductId() {
        const option = selectedOption(source);
        return option ? String(option.dataset.productId || '') : '';
    }

    function selectedSourceVariantId() {
        const option = selectedOption(source);
        return option ? String(option.dataset.variantId || '') : '';
    }

    function mappedTargetIdsForSelectedSource() {
        const sourceProductId = selectedSourceProductId();
        if (!sourceProductId) return [];

        const ids = new Set((transformationTargets[sourceProductId] || []).map(String));
        ids.add(sourceProductId);
        return Array.from(ids);
    }

    function rowElements() {
        return Array.from(outputRows?.querySelectorAll('[data-output-row]') || []);
    }

    function selectedProduct(row) {
        const select = row.querySelector('[data-output-product]');
        return select?.value ? productMeta[String(select.value)] || null : null;
    }

    function selectedVariant(row) {
        const product = selectedProduct(row);
        const select = row.querySelector('[data-output-variant]');
        if (!product || !select?.value) return null;
        return (variantMeta[String(product.id)] || []).find(variant => String(variant.id) === String(select.value)) || null;
    }

    function selectedTarget(row) {
        const product = selectedProduct(row);
        if (!product) return null;
        const variant = selectedVariant(row);
        return variant
            ? { ...product, ...variant, product_id: product.id, variant_id: variant.id, name: product.name, variant_label: variant.label }
            : product;
    }

    function outputDisplayName(row) {
        const product = selectedProduct(row);
        const target = selectedTarget(row);
        if (!product || !target) return 'selected output';
        if (target.variant_id) return product.name + ' - ' + (target.variant_label || target.sku || 'variant');
        return product.name;
    }

    function mode(target) {
        if (!target) return 'quantity';
        const packType = target.pack_type || 'quantity';
        if (packType === 'fixed_piece_pack' || n(target.pieces_per_pack) > 0) return 'piece';
        if (target.sell_unit === 'kg' || packType === 'variable_weight') return 'variable_weight';
        if (packType === 'fixed_weight_pack' || n(target.product_weight) > 0) return 'weight';
        return 'quantity';
    }

    function filterOutputProducts(row) {
        const select = row.querySelector('[data-output-product]');
        if (!select) return;

        const mappedIds = mappedTargetIdsForSelectedSource();
        const shouldFilter = mappedIds.length > 0 && !showAllOutputs?.checked;
        let selectedVisible = false;

        Array.from(select.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            const visible = !shouldFilter || mappedIds.includes(String(option.value));
            option.hidden = !visible;
            option.disabled = !visible;
            if (visible && option.value === select.value) selectedVisible = true;
        });

        if (shouldFilter && select.value && !selectedVisible) select.value = '';
    }

    function populateOutputVariants(row, desiredVariantId = null) {
        const product = selectedProduct(row);
        const select = row.querySelector('[data-output-variant]');
        const wrap = row.querySelector('[data-output-variant-wrap]');
        if (!select) return;

        const variants = product ? (variantMeta[String(product.id)] || []) : [];
        const previous = desiredVariantId !== null ? String(desiredVariantId || '') : String(select.value || '');
        const sourceProductId = selectedSourceProductId();
        const sourceVariantId = selectedSourceVariantId();

        select.innerHTML = '';
        const productLevelOption = document.createElement('option');
        productLevelOption.value = '';
        productLevelOption.textContent = 'Product-level stock';
        productLevelOption.disabled = Boolean(product) && String(product.id) === sourceProductId && sourceVariantId === '';
        select.appendChild(productLevelOption);

        variants.forEach(variant => {
            const option = document.createElement('option');
            const sameAsSource = String(product.id) === sourceProductId && String(variant.id) === sourceVariantId;
            option.value = String(variant.id);
            option.disabled = sameAsSource;
            option.textContent = (variant.label || variant.sku || 'Variant')
                + (variant.is_active ? '' : ' — inactive')
                + (sameAsSource ? ' — source' : '');
            select.appendChild(option);
        });

        const hasVariants = variants.length > 0;
        wrap?.classList.toggle('hidden', !hasVariants);
        select.disabled = !hasVariants;

        const matchingOption = Array.from(select.options).find(option => option.value === previous && !option.disabled);
        select.value = matchingOption ? previous : '';
    }

    function updateOutputFields(row) {
        const target = selectedTarget(row);
        const outputMode = mode(target);
        const piecesInput = row.querySelector('[data-pieces-per-pack]');
        const piecesWrap = row.querySelector('[data-pieces-per-pack-wrap]');
        const weightWrap = row.querySelector('[data-output-weight-wrap]');

        piecesWrap?.classList.toggle('hidden', outputMode !== 'piece');
        weightWrap?.classList.toggle('hidden', outputMode !== 'variable_weight');

        if (target && outputMode === 'piece' && n(target.pieces_per_pack) > 0 && piecesInput) {
            piecesInput.value = String(target.pieces_per_pack);
        }
    }

    function reindexRows() {
        const rows = rowElements();
        rows.forEach((row, index) => {
            row.querySelector('[data-output-number]').textContent = String(index + 1);
            row.querySelector('[data-output-product]').name = `outputs[${index}][output_product_id]`;
            row.querySelector('[data-output-variant]').name = `outputs[${index}][output_product_variant_id]`;
            row.querySelector('[data-pack-count]').name = `outputs[${index}][pack_count]`;
            row.querySelector('[data-pieces-per-pack]').name = `outputs[${index}][pieces_per_pack]`;
            row.querySelector('[data-output-weight]').name = `outputs[${index}][output_weight_kg]`;
            row.querySelector('[data-remove-output]').classList.toggle('hidden', rows.length === 1);
        });
    }

    function addOutputRow(values = {}) {
        if (!outputTemplate || !outputRows) return;
        const fragment = outputTemplate.content.cloneNode(true);
        const row = fragment.querySelector('[data-output-row]');
        outputRows.appendChild(fragment);

        const productSelect = row.querySelector('[data-output-product]');
        const variantSelect = row.querySelector('[data-output-variant]');
        const packCount = row.querySelector('[data-pack-count]');
        const piecesInput = row.querySelector('[data-pieces-per-pack]');
        const weightInput = row.querySelector('[data-output-weight]');

        productSelect.value = values.output_product_id ? String(values.output_product_id) : '';
        packCount.value = values.pack_count ? String(values.pack_count) : '1';
        piecesInput.value = values.pieces_per_pack ? String(values.pieces_per_pack) : '';
        weightInput.value = values.output_weight_kg ? String(values.output_weight_kg) : '';

        filterOutputProducts(row);
        populateOutputVariants(row, values.output_product_variant_id || '');
        if (variantSelect && values.output_product_variant_id) {
            variantSelect.value = String(values.output_product_variant_id);
        }
        updateOutputFields(row);
        reindexRows();
        updatePreview();
    }

    function populateSourcePieces() {
        const pieces = source?.value ? lotPiecesMeta[String(source.value)] || [] : [];
        const previous = sourcePiece?.value || '';
        if (!sourcePiece) return;

        sourcePiece.innerHTML = '<option value="">Use whole source lot</option>';
        pieces.forEach(piece => {
            const option = document.createElement('option');
            option.value = String(piece.id);
            option.textContent = (piece.label || ('Piece ' + piece.piece_no)) + ' · ' + fmt(piece.available_weight_kg) + ' kg available';
            sourcePiece.appendChild(option);
        });
        sourcePiece.value = pieces.some(piece => String(piece.id) === String(previous)) ? previous : '';
        sourcePieceWrap?.classList.toggle('hidden', pieces.length === 0);
    }

    function refreshAllRows() {
        rowElements().forEach(row => {
            const previousVariant = row.querySelector('[data-output-variant]')?.value || '';
            filterOutputProducts(row);
            populateOutputVariants(row, previousVariant);
            updateOutputFields(row);
        });
    }

    function updateTransformationHint() {
        if (!transformationHint) return;
        const mappedIds = mappedTargetIdsForSelectedSource();
        if (!selectedSourceProductId()) {
            transformationHint.textContent = 'Select a source lot. Configured transformation relationships will filter every output row automatically.';
        } else if (mappedIds.length > 0 && !showAllOutputs?.checked) {
            transformationHint.textContent = 'Each output row is showing only products mapped from this source, including other variants of the same product.';
        } else if (mappedIds.length > 0) {
            transformationHint.textContent = 'All products are shown. Normal mapped outputs are still checked in the preview.';
        } else {
            transformationHint.textContent = 'No transformation mapping exists for this source, so all output products are available.';
        }
    }

    function applySourceDefaults() {
        populateSourcePieces();
        const src = selectedOption(source);
        if (src) {
            if (batch && !batch.value && src.dataset.batch) batch.value = src.dataset.batch;
            if (expiry && !expiry.value && src.dataset.expiry) expiry.value = src.dataset.expiry;
            if (sourcePieces && (!oldSourcePiecesPerUnit || sourcePieces.dataset.autoFilled === '1' || !sourcePieces.value || sourcePieces.value === '1')) {
                sourcePieces.value = String(src.dataset.sourcePiecesPerUnit || '1');
                sourcePieces.dataset.autoFilled = '1';
            }
        }
        refreshAllRows();
        updateTransformationHint();
        updatePreview();
    }

    function updateRowSummary(row) {
        const summary = row.querySelector('[data-output-row-summary]');
        const target = selectedTarget(row);
        const count = Math.max(0, parseInt(row.querySelector('[data-pack-count]')?.value || '0', 10));
        if (!summary || !target || count <= 0) {
            if (summary) summary.textContent = 'Select an output product or variant and enter its pack count.';
            return null;
        }

        const outputMode = mode(target);
        const piecesPerPack = n(row.querySelector('[data-pieces-per-pack]')?.value || target.pieces_per_pack);
        const outputWeight = n(row.querySelector('[data-output-weight]')?.value);
        let detail = '';
        let sourcePiecesRequired = 0;
        let sourceWeightRequired = 0;
        let sourceQuantityRequired = 0;

        if (outputMode === 'piece') {
            sourcePiecesRequired = count * piecesPerPack;
            detail = `${fmt(count, 0)} pack(s) × ${fmt(piecesPerPack)} pcs = ${fmt(sourcePiecesRequired)} source pieces`;
        } else if (outputMode === 'weight') {
            sourceWeightRequired = count * n(target.product_weight);
            detail = `${fmt(count, 0)} pack(s) × ${fmt(target.product_weight)} kg = ${fmt(sourceWeightRequired)} kg`;
        } else if (outputMode === 'variable_weight') {
            sourceWeightRequired = outputWeight || (count * n(target.product_weight));
            detail = `${fmt(count, 0)} pack row(s), ${fmt(sourceWeightRequired)} kg total`;
        } else {
            sourceQuantityRequired = count;
            detail = `${fmt(count, 0)} source unit(s)`;
        }

        summary.innerHTML = `<strong>${outputDisplayName(row)}</strong> · ${detail}`;
        return {
            row,
            target,
            mode: outputMode,
            count,
            piecesPerPack,
            sourcePiecesRequired,
            sourceWeightRequired,
            sourceQuantityRequired,
        };
    }

    function updatePreview() {
        const src = selectedOption(source);
        const summaries = rowElements().map(updateRowSummary).filter(Boolean);
        previewValid = false;

        if (!src || summaries.length === 0) {
            preview.textContent = 'Select a source lot and complete at least one output row.';
            return;
        }

        const availableQty = n(src.dataset.availableQty);
        const availableWeight = selectedPiece() ? n(selectedPiece().available_weight_kg) : n(src.dataset.availableWeight);
        const availablePieces = n(src.dataset.availablePieces);
        const availableFullPacks = Math.max(0, parseInt(src.dataset.availableFullPacks || '0', 10));
        const sourcePpu = n(sourcePieces?.value || src.dataset.sourcePiecesPerUnit) || 1;
        const modes = new Set(summaries.map(item => item.mode));
        const targetKeys = new Set();
        let duplicateTarget = false;
        let totalPacks = 0;
        let totalPieces = 0;
        let totalWeight = 0;
        let totalQuantity = 0;
        const lines = [];

        summaries.forEach(item => {
            const targetKey = String(item.target.product_id || item.target.id) + ':' + String(item.target.variant_id || 0);
            if (targetKeys.has(targetKey)) duplicateTarget = true;
            targetKeys.add(targetKey);
            totalPacks += item.count;
            totalPieces += item.sourcePiecesRequired;
            totalWeight += item.sourceWeightRequired;
            totalQuantity += item.sourceQuantityRequired;
            lines.push(`<li><strong>${fmt(item.count, 0)} × ${outputDisplayName(item.row)}</strong></li>`);
        });

        let html = `<div>Creates <strong>${fmt(totalPacks, 0)} pack(s)</strong> across <strong>${fmt(summaries.length, 0)} output variant(s)</strong>:</div>`;
        html += `<ul class="mt-1 list-disc space-y-0.5 pl-5">${lines.join('')}</ul>`;

        if (modes.size > 1) {
            html += '<div class="mt-2 text-red-700 dark:text-red-300">All rows in one transaction must use the same basis: pieces, weight, or quantity.</div>';
        }
        if (duplicateTarget) {
            html += '<div class="mt-2 text-red-700 dark:text-red-300">The same output variant is listed more than once. Keep one row and increase its pack count.</div>';
        }

        let enough = true;
        if (modes.size === 1 && modes.has('piece')) {
            enough = availablePieces + 0.0005 >= totalPieces;
            const remainingPieces = Math.max(availablePieces - totalPieces, 0);
            const unopenedPieces = Math.min(availablePieces, availableFullPacks * sourcePpu);
            const alreadyLoosePieces = Math.max(availablePieces - unopenedPieces, 0);
            const piecesNeedingNewCartons = Math.max(totalPieces - alreadyLoosePieces, 0);
            const cartonsOpened = piecesNeedingNewCartons > 0 ? Math.ceil(piecesNeedingNewCartons / sourcePpu) : 0;
            const fullPacksAfter = Math.max(availableFullPacks - cartonsOpened, 0);

            html += `<div class="mt-2">Combined source consumption: <strong>${fmt(totalPieces)} pieces</strong> (${fmt(totalPieces / sourcePpu)} source-unit equivalent).</div>`;
            html += `<div>Current source balance: <strong>${fmt(availableFullPacks, 0)} unopened carton(s)</strong> and <strong>${fmt(availablePieces)} pieces</strong>.</div>`;
            html += `<div>After this transform: <strong>${fmt(fullPacksAfter, 0)} unopened carton(s)</strong> and <strong>${fmt(remainingPieces)} loose/available pieces</strong>.</div>`;

            if (cartonsOpened > 0 && remainingPieces > 0) {
                html += `<div class="mt-1 text-amber-700 dark:text-amber-300">${fmt(cartonsOpened, 0)} full carton(s) will be removed from sale because they are opened; the unused ${fmt(remainingPieces)} pieces remain available for the next transform.</div>`;
            }
        } else if (modes.size === 1 && (modes.has('weight') || modes.has('variable_weight'))) {
            enough = availableWeight + 0.0005 >= totalWeight;
            html += `<div class="mt-2">Combined source consumption: <strong>${fmt(totalWeight)} kg</strong>.</div>`;
            html += `<div>Available source weight: <strong>${fmt(availableWeight)} kg</strong>; remaining: <strong>${fmt(Math.max(availableWeight - totalWeight, 0))} kg</strong>.</div>`;
        } else if (modes.size === 1) {
            enough = availableQty + 0.0005 >= totalQuantity;
            html += `<div class="mt-2">Combined source consumption: <strong>${fmt(totalQuantity)} source unit(s)</strong>.</div>`;
            html += `<div>Available source quantity: <strong>${fmt(availableQty)}</strong>; remaining: <strong>${fmt(Math.max(availableQty - totalQuantity, 0))}</strong>.</div>`;
        }

        if (!enough) {
            html += '<div class="mt-2 text-red-700 dark:text-red-300">The combined output rows exceed the available source stock.</div>';
        }

        previewValid = modes.size === 1 && !duplicateTarget && enough && summaries.length === rowElements().length;
        preview.innerHTML = html;
    }

    addOutputButton?.addEventListener('click', () => addOutputRow({ pack_count: 1 }));

    outputRows?.addEventListener('change', event => {
        const row = event.target.closest('[data-output-row]');
        if (!row) return;
        if (event.target.matches('[data-output-product]')) {
            populateOutputVariants(row);
            updateOutputFields(row);
        } else if (event.target.matches('[data-output-variant]')) {
            updateOutputFields(row);
        }
        updatePreview();
    });

    outputRows?.addEventListener('input', event => {
        if (event.target.matches('[data-pack-count], [data-pieces-per-pack], [data-output-weight]')) updatePreview();
    });

    outputRows?.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-output]');
        if (!button) return;
        const rows = rowElements();
        if (rows.length <= 1) return;
        button.closest('[data-output-row]')?.remove();
        reindexRows();
        updatePreview();
    });

    source?.addEventListener('change', applySourceDefaults);
    sourcePiece?.addEventListener('change', updatePreview);
    sourcePieces?.addEventListener('input', () => {
        sourcePieces.dataset.autoFilled = '0';
        updatePreview();
    });
    showAllOutputs?.addEventListener('change', () => {
        refreshAllRows();
        updateTransformationHint();
        updatePreview();
    });

    function openConfirmModal() {
        updatePreview();
        if (!previewValid || !confirmModal || !confirmSummary) {
            preview?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        confirmSummary.innerHTML = preview.innerHTML;
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
        confirmSubmit?.focus();
        return true;
    }

    function closeConfirmModal() {
        if (!confirmModal) return;
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
    }

    form?.addEventListener('submit', function (event) {
        if (confirmedSubmit) return;
        event.preventDefault();
        if (!this.reportValidity || this.reportValidity()) openConfirmModal();
    });

    confirmSubmit?.addEventListener('click', function () {
        if (!form) return;
        confirmedSubmit = true;
        closeConfirmModal();
        form.submit();
    });

    confirmCancelButtons.forEach(button => button.addEventListener('click', closeConfirmModal));
    confirmModal?.addEventListener('click', event => {
        if (event.target === confirmModal) closeConfirmModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeConfirmModal();
    });

    (initialOutputs.length ? initialOutputs : [{ pack_count: 1 }]).forEach(addOutputRow);
    applySourceDefaults();
})();
</script>
@endsection

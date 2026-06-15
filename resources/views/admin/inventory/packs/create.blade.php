@extends('layouts.company')

@section('title', 'Create pack stock')

@section('content')
@php
    $lotSummary = function ($lot): string {
        $parts = [];
        if ($lot->available_weight_kg !== null && (float) $lot->available_weight_kg > 0) {
            $parts[] = number_format((float) $lot->available_weight_kg, 3) . ' kg';
        }
        if ($lot->available_piece_count !== null && (int) $lot->available_piece_count > 0) {
            $parts[] = number_format((float) $lot->available_piece_count, 0) . ' pcs';
        }
        if ((float) ($lot->available_quantity ?? 0) > 0) {
            $parts[] = number_format((float) $lot->available_quantity, 3) . ' source units';
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
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Create pack stock</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Convert a raw/internal source lot into a normal saleable product. Example: Full Pork Belly lot → Pork Belly With Skin 500g Slice Pack product.
            </p>
        </div>
        <a href="{{ route('admin.inventory.packs.index') }}" class="rounded border border-gray-300 px-3 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Back</a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-[12px] text-red-800">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory.packs.store') }}" class="rounded-2xl border border-gray-200 bg-white p-5 space-y-5 dark:border-gray-800 dark:bg-gray-950">
        @csrf

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Source lot</label>
                <select id="source_inventory_lot_id" name="source_inventory_lot_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Select raw lot…</option>
                    @foreach($lots as $lot)
                        <option value="{{ $lot->id }}"
                                data-product-id="{{ $lot->product_id }}"
                                data-available-qty="{{ (float) ($lot->available_quantity ?? 0) }}"
                                data-available-weight="{{ (float) ($lot->available_weight_kg ?? 0) }}"
                                data-available-pieces="{{ (float) ($lot->available_piece_count ?? 0) }}"
                                data-batch="{{ $lot->batch_code }}"
                                data-expiry="{{ optional($lot->expiry_date)->format('Y-m-d') }}"
                                data-mode="{{ $lot->inward_mode }}"
                                @selected((string) $selectedLotId === (string) $lot->id)>
                            {{ $lot->product?->name ?? 'Product #' . $lot->product_id }} · Lot #{{ $lot->id }} · {{ $lotSummary($lot) }}
                            @if($lot->batch_code) · Batch {{ $lot->batch_code }} @endif
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Only repackable lots with available quantity are shown.</p>
            </div>

            <div id="source_piece_wrap" class="hidden">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Specific source piece</label>
                <select id="source_inventory_piece_id" name="source_inventory_piece_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Use whole source lot</option>
                </select>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Optional. Use this when cutting from one belly/fillet piece and you want piece-level balance updated.</p>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Output product</label>
                <select id="output_product_id" name="output_product_id" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Select finished product…</option>
                    @foreach($outputProducts as $product)
                        @php
                            $role = (string) ($product->inventory_role ?? (($product->is_active ?? false) ? 'saleable' : 'internal'));
                            $packType = (string) ($product->pack_type ?? 'quantity');
                        @endphp
                        <option value="{{ $product->id }}" @selected((string) old('output_product_id') === (string) $product->id)>
                            {{ $product->name }} @if($product->sku) ({{ $product->sku }}) @endif · {{ str_replace('_', ' ', $packType) }} @unless($product->is_active) · Internal/Draft @endunless
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">The output product is what the frontend/admin stock will show.</p>
            </div>

            <div id="output_variant_wrap" class="hidden">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Output pack variant</label>
                <select id="output_product_variant_id" name="output_product_variant_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900" disabled>
                    <option value="">Product-level stock</option>
                </select>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Use for Dimsum 10 pcs / 20 pcs style pack options. Leave blank for simple products and slab products.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pack count to create</label>
                <input id="pack_count" name="pack_count" type="number" min="1" step="1" value="{{ old('pack_count', 1) }}" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Example: 40 packs or 8 weighed slabs.</p>
            </div>

            <div id="output_weight_wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Total output weight kg</label>
                <input id="output_weight_kg" name="output_weight_kg" type="number" min="0.001" step="0.001" value="{{ old('output_weight_kg') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">For by-kg / variable-weight products. Fixed-weight products use product weight.</p>
            </div>

            <div id="pieces_per_pack_wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pieces per pack</label>
                <input id="pieces_per_pack" name="pieces_per_pack" type="number" min="0.001" step="0.001" value="{{ old('pieces_per_pack') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Auto-filled from fixed-piece products like dimsum.</p>
            </div>

            <div id="source_pieces_wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Source pieces per source unit</label>
                <input id="source_pieces_per_unit" name="source_pieces_per_unit" type="number" min="0.001" step="0.001" value="{{ old('source_pieces_per_unit', 1) }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-[13px] dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Usually 1 for loose pieces.</p>
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
            <div class="mt-1" id="source_qty_preview">Select a source lot and output product.</div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.inventory.packs.index') }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Cancel</a>
            <button class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Create stock</button>
        </div>
    </form>

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
    const source = document.getElementById('source_inventory_lot_id');
    const sourcePiece = document.getElementById('source_inventory_piece_id');
    const sourcePieceWrap = document.getElementById('source_piece_wrap');
    const output = document.getElementById('output_product_id');
    const outputVariant = document.getElementById('output_product_variant_id');
    const outputVariantWrap = document.getElementById('output_variant_wrap');
    const packCount = document.getElementById('pack_count');
    const outputWeight = document.getElementById('output_weight_kg');
    const pieces = document.getElementById('pieces_per_pack');
    const sourcePieces = document.getElementById('source_pieces_per_unit');
    const outputWeightWrap = document.getElementById('output_weight_wrap');
    const piecesWrap = document.getElementById('pieces_per_pack_wrap');
    const sourcePiecesWrap = document.getElementById('source_pieces_wrap');
    const preview = document.getElementById('source_qty_preview');
    const batch = document.getElementById('batch_code');
    const expiry = document.getElementById('expiry_date');
    const productMeta = @json($productMeta);
    const variantMeta = @json($variantMeta);
    const lotPiecesMeta = @json($lotPiecesMeta);
    const oldOutputVariantId = @json(old('output_product_variant_id'));

    function selectedOption(select) {
        return select && select.options[select.selectedIndex] ? select.options[select.selectedIndex] : null;
    }

    function selectedPiece() {
        return sourcePiece && sourcePiece.value ? (lotPiecesMeta[String(source.value)] || []).find(piece => String(piece.id) === String(sourcePiece.value)) || null : null;
    }

    function selectedProduct() {
        return output?.value ? productMeta[String(output.value)] || null : null;
    }

    function selectedVariant() {
        const product = selectedProduct();
        if (!product || !outputVariant?.value) return null;
        return (variantMeta[String(product.id)] || []).find(variant => String(variant.id) === String(outputVariant.value)) || null;
    }

    function selectedOutputTarget() {
        const product = selectedProduct();
        if (!product) return null;
        const variant = selectedVariant();
        return variant ? { ...product, ...variant, product_id: product.id, variant_id: variant.id, name: product.name, variant_label: variant.label } : product;
    }

    function outputDisplayName(product, target) {
        if (!product) return 'selected output';
        if (target && target.variant_id) {
            return product.name + ' - ' + (target.variant_label || target.sku || 'variant');
        }
        return product.name;
    }

    function populateOutputVariants() {
        const product = selectedProduct();
        const variants = product ? (variantMeta[String(product.id)] || []) : [];
        const previous = outputVariant ? (outputVariant.value || oldOutputVariantId || '') : '';
        if (!outputVariant) return;

        outputVariant.innerHTML = '<option value="">Product-level stock</option>';
        variants.forEach(variant => {
            const option = document.createElement('option');
            option.value = String(variant.id);
            option.textContent = (variant.label || variant.sku || 'Variant') + (variant.is_active ? '' : ' — inactive');
            outputVariant.appendChild(option);
        });

        const hasVariants = variants.length > 0;
        outputVariantWrap?.classList.toggle('hidden', !hasVariants);
        outputVariant.disabled = !hasVariants;
        outputVariant.value = variants.some(variant => String(variant.id) === String(previous)) ? previous : '';
    }

    function n(value) {
        const parsed = parseFloat(String(value || '').trim());
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function fmt(value, decimals = 3) {
        return n(value).toLocaleString(undefined, { maximumFractionDigits: decimals });
    }

    function mode(product) {
        if (!product) return 'quantity';
        const packType = product.pack_type || 'quantity';
        if (packType === 'fixed_piece_pack' || n(product.pieces_per_pack) > 0) return 'piece';
        if (product.sell_unit === 'kg' || packType === 'variable_weight') return 'variable_weight';
        if (packType === 'fixed_weight_pack' || n(product.product_weight) > 0) return 'weight';
        return 'quantity';
    }

    function populateSourcePieces() {
        const pieces = source && source.value ? lotPiecesMeta[String(source.value)] || [] : [];
        const previous = sourcePiece ? sourcePiece.value : '';
        if (!sourcePiece) return;

        sourcePiece.innerHTML = '<option value="">Use whole source lot</option>';
        pieces.forEach(piece => {
            const option = document.createElement('option');
            option.value = String(piece.id);
            option.dataset.availableWeight = String(piece.available_weight_kg || 0);
            option.textContent = (piece.label || ('Piece ' + piece.piece_no)) + ' · ' + fmt(piece.available_weight_kg) + ' kg available';
            sourcePiece.appendChild(option);
        });

        sourcePiece.value = pieces.some(piece => String(piece.id) === String(previous)) ? previous : '';
        sourcePieceWrap?.classList.toggle('hidden', pieces.length === 0);
    }

    function applySourceDefaults() {
        populateSourcePieces();
        const src = selectedOption(source);
        if (src) {
            if (!batch.value && src.dataset.batch) batch.value = src.dataset.batch;
            if (!expiry.value && src.dataset.expiry) expiry.value = src.dataset.expiry;
        }
        updatePreview();
    }

    function updateFieldsFromProduct() {
        populateOutputVariants();
        const target = selectedOutputTarget();
        const productMode = mode(target);

        outputWeightWrap.classList.toggle('hidden', productMode !== 'variable_weight');
        piecesWrap.classList.toggle('hidden', productMode !== 'piece');
        sourcePiecesWrap.classList.toggle('hidden', productMode !== 'piece');

        if (target && productMode === 'piece' && n(target.pieces_per_pack) > 0) {
            pieces.value = String(target.pieces_per_pack);
        }

        updatePreview();
    }

    function updatePreview() {
        const src = selectedOption(source);
        const product = selectedProduct();
        const target = selectedOutputTarget();
        const count = Math.max(0, parseInt(packCount.value || '0', 10));

        if (!src || !product || !target || count <= 0) {
            preview.textContent = 'Select a source lot, output product, and pack count.';
            return;
        }

        const productMode = mode(target);
        const piece = selectedPiece();
        const availableQty = n(src.dataset.availableQty);
        const availableWeight = piece ? n(piece.available_weight_kg) : (n(src.dataset.availableWeight) || availableQty);
        const availablePieces = n(src.dataset.availablePieces) || availableQty;
        const outName = outputDisplayName(product, target);
        let html = '<div>Creates <strong>' + fmt(count, 0) + '</strong> pack/stock row(s) for <strong>' + outName + '</strong>.</div>';
        if (piece) {
            html += '<div>Using source piece: <strong>' + (piece.label || ('Piece ' + piece.piece_no)) + '</strong> with <strong>' + fmt(piece.available_weight_kg) + ' kg</strong> available.</div>';
        }

        if (src.dataset.productId && String(src.dataset.productId) !== String(product.id)) {
            html += '<div class="mt-1 text-amber-700">Cross-product repack: source lot stock is consumed, and stock is added to the selected output product.</div>';
        }

        if (productMode === 'weight') {
            const packWeight = n(target.product_weight || outputWeight.value);
            const requiredWeight = count * packWeight;
            const enough = availableWeight + 0.0005 >= requiredWeight;
            html += '<div>Fixed weight output. Consumes <strong>' + fmt(requiredWeight) + ' kg</strong> from source.</div>';
            html += '<div>Available source weight: <strong>' + fmt(availableWeight) + ' kg</strong>.</div>';
            html += '<div>Output product stock increases by <strong>' + fmt(count, 0) + '</strong> pack(s).</div>';
            if (!packWeight) html += '<div class="mt-1 text-red-700">Set product weight or enter output weight.</div>';
            if (!enough) html += '<div class="mt-1 text-red-700">Not enough source weight available.</div>';
        } else if (productMode === 'variable_weight') {
            let requiredWeight = n(outputWeight.value);
            if (requiredWeight <= 0 && n(target.product_weight) > 0) requiredWeight = count * n(target.product_weight);
            const enough = availableWeight + 0.0005 >= requiredWeight;
            html += '<div>Variable/by-kg output. Consumes <strong>' + fmt(requiredWeight) + ' kg</strong> from source.</div>';
            html += '<div>Available source weight: <strong>' + fmt(availableWeight) + ' kg</strong>.</div>';
            html += '<div>Output product stock increases by <strong>' + fmt(requiredWeight) + ' kg</strong>.</div>';
            if (!requiredWeight) html += '<div class="mt-1 text-red-700">Enter total output weight.</div>';
            if (!enough) html += '<div class="mt-1 text-red-700">Not enough source weight available.</div>';
        } else if (productMode === 'piece') {
            const piecesPerPack = n(pieces.value || target.pieces_per_pack);
            const sourcePpu = n(sourcePieces.value) || 1;
            const requiredPieces = count * piecesPerPack;
            const requiredUnits = requiredPieces / sourcePpu;
            const enough = availablePieces > 0
                ? availablePieces + 0.0005 >= requiredPieces
                : availableQty + 0.0005 >= requiredUnits;
            html += '<div>Fixed piece output. Consumes <strong>' + fmt(requiredPieces) + ' piece(s)</strong>, equal to <strong>' + fmt(requiredUnits) + '</strong> source unit(s).</div>';
            html += '<div>Available source pieces/units: <strong>' + fmt(availablePieces) + '</strong> / <strong>' + fmt(availableQty) + '</strong>.</div>';
            html += '<div>Output product stock increases by <strong>' + fmt(count, 0) + '</strong> pack(s).</div>';
            if (!piecesPerPack) html += '<div class="mt-1 text-red-700">Pieces per pack is required.</div>';
            if (!enough) html += '<div class="mt-1 text-red-700">Not enough source quantity available.</div>';
        } else {
            const enough = availableQty + 0.0005 >= count;
            html += '<div>Quantity output. Consumes <strong>' + fmt(count, 0) + '</strong> source unit(s).</div>';
            html += '<div>Available source quantity: <strong>' + fmt(availableQty) + '</strong>.</div>';
            html += '<div>Output product stock increases by <strong>' + fmt(count, 0) + '</strong>.</div>';
            if (!enough) html += '<div class="mt-1 text-red-700">Not enough source quantity available.</div>';
        }

        preview.innerHTML = html;
    }

    source?.addEventListener('change', applySourceDefaults);
    sourcePiece?.addEventListener('change', updatePreview);
    output?.addEventListener('change', updateFieldsFromProduct);
    outputVariant?.addEventListener('change', () => {
        const target = selectedOutputTarget();
        if (target && n(target.pieces_per_pack) > 0) {
            pieces.value = String(target.pieces_per_pack);
        }
        updateFieldsFromProduct();
    });
    packCount?.addEventListener('input', updatePreview);
    outputWeight?.addEventListener('input', updatePreview);
    pieces?.addEventListener('input', updatePreview);
    sourcePieces?.addEventListener('input', updatePreview);

    applySourceDefaults();
    updateFieldsFromProduct();
})();
</script>
@endsection

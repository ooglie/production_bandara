@extends('layouts.company')

@section('title', 'New vendor invoice')

@section('content')
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Vendor[] $vendors */
    /** @var \Illuminate\Support\Collection|\App\Models\Product[] $products */
    /** @var \Illuminate\Support\Collection|\App\Models\HsnCode[] $hsnCodes */

    $has = fn (string $r) => \Illuminate\Support\Facades\Route::has($r);
    $storeUrl = $has('admin.vendor-invoices.store') ? route('admin.vendor-invoices.store') : '#';
    $backUrl = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();

    $productMeta = collect($products ?? [])->mapWithKeys(function ($p) {
        return [(string) $p->id => [
            'id' => (int) $p->id,
            'name' => (string) $p->name,
            'sku' => (string) ($p->sku ?? ''),
            'barcode' => (string) ($p->barcode ?? ''),
            'inventory_role' => (string) ($p->inventory_role ?? (($p->is_active ?? false) ? 'saleable' : 'internal')),
            'pack_type' => (string) ($p->pack_type ?? 'quantity'),
            'sell_unit' => (string) ($p->sell_unit ?? 'piece'),
            'product_weight' => $p->product_weight !== null ? (float) $p->product_weight : null,
            'pieces_per_pack' => $p->pieces_per_pack !== null ? (float) $p->pieces_per_pack : null,
            'hsn_code_id' => $p->hsn_code_id ? (int) $p->hsn_code_id : null,
            'is_active' => (bool) $p->is_active,
        ]];
    });

    $variantMeta = collect($productVariants ?? [])->mapWithKeys(function ($rows, $productId) {
        return [(string) $productId => collect($rows)->map(function ($v) {
            $label = trim((string) ($v->name ?? ''));
            if ($label === '') {
                $packType = (string) ($v->pack_type ?? '');
                if ($packType === 'fixed_piece_pack' && (float) ($v->pieces_per_pack ?? 0) > 0) {
                    $label = rtrim(rtrim(number_format((float) $v->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
                } elseif ($packType === 'fixed_weight_pack' && (float) ($v->product_weight ?? 0) > 0) {
                    $label = rtrim(rtrim(number_format((float) $v->product_weight, 3), '0'), '.') . ' kg pack';
                } else {
                    $label = (string) ($v->sku ?? ('Variant ' . $v->id));
                }
            }

            return [
                'id' => (int) $v->id,
                'product_id' => (int) $v->product_id,
                'label' => $label,
                'sku' => (string) ($v->sku ?? ''),
                'pack_type' => (string) ($v->pack_type ?? 'quantity'),
                'product_weight' => $v->product_weight !== null ? (float) $v->product_weight : null,
                'pieces_per_pack' => $v->pieces_per_pack !== null ? (float) $v->pieces_per_pack : null,
                'pricing_unit' => (string) ($v->pricing_unit ?? 'pack'),
                'stock_quantity' => $v->stock_quantity !== null ? (float) $v->stock_quantity : null,
                'is_active' => (bool) ($v->is_active ?? true),
            ];
        })->values()->all()];
    });

    $hsnMeta = collect($hsnCodes ?? [])->mapWithKeys(function ($h) {
        return [(string) $h->id => [
            'id' => (int) $h->id,
            'code' => (string) $h->code,
            'gst_rate' => (float) ($h->gst_rate ?? 0),
            'description' => (string) ($h->description ?: $h->name ?: ''),
        ]];
    });
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">New vendor invoice</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Receive stock as either pieces with weight or simple quantity. Products and lots are created separately; this screen only records inward stock.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $backUrl }}" class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">Back</a>
            @if($has('admin.products.create'))
                <a href="{{ route('admin.products.create') }}" target="_blank" class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">New product</a>
            @endif
        </div>
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

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Pieces with weight</div>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">For full pork belly, salmon, whole fish or similar stock charged per kg. You can enter total weight or paste individual weights.</p>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Quantity</div>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">For packs, cartons, boxes, loose dimsum pieces, or any item charged per unit/pack/piece.</p>
        </div>
    </div>

    <form method="POST" action="{{ $storeUrl }}" class="space-y-4" id="vendor-invoice-form">
        @csrf

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Invoice details</div>
            </div>
            <div class="p-5 grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor</label>
                    <select name="vendor_id" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                        <option value="">Select vendor…</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" @selected((int) old('vendor_id', $vendor?->id ?? 0) === (int) $v->id)>
                                {{ trim(($v->code ? $v->code . ' — ' : '') . $v->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice number</label>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice date</label>
                    <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Due date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                </div>
                <div class="md:col-span-4">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Stock received from vendor</div>
                </div>
                <button type="button" id="add-item-row" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">
                    <span class="text-lg leading-none">+</span> Add item
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div id="items-container" class="space-y-4"></div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
                    <div class="grid gap-3 sm:grid-cols-3 text-[13px]">
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                            <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span id="subtotal-val">0.00</span></span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                            <span class="text-gray-600 dark:text-gray-300">Tax</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span id="tax-val">0.00</span></span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                            <span class="text-gray-600 dark:text-gray-300">Grand total</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span id="grand-val">0.00</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ $backUrl }}" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-gray-900 px-5 py-2 text-[12px] font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Save vendor invoice</button>
        </div>
    </form>
</div>

<template id="invoice-item-row-template">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/20 p-4 space-y-4" data-row="1">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-xl bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 flex items-center justify-center text-[12px] font-semibold"><span class="row-no">#</span></div>
                <div>
                    <div class="text-[13px] font-semibold text-gray-900 dark:text-gray-50">Invoice item</div>
                    <div class="item-summary text-[11px] text-gray-500 dark:text-gray-400">Choose product and inward mode.</div>
                </div>
            </div>
            <button type="button" class="remove-row text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">Remove</button>
        </div>

        <div class="grid gap-3 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Product / stock item</label>
                <select class="product-select w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][product_id]" required>
                    <option value="">Select product…</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->name }} @if($p->sku) ({{ $p->sku }}) @endif @unless($p->is_active) — Internal/Draft @endunless
                        </option>
                    @endforeach
                </select>
                <div class="product-hint text-[11px] text-gray-500 dark:text-gray-400 mt-1">Use internal products for raw inward stock and active products for ready-to-sell stock.</div>
            </div>

            <div class="variant-wrap hidden lg:col-span-3">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pack variant</label>
                <select class="product-variant-select w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][product_variant_id]" disabled>
                    <option value="">Product-level stock</option>
                </select>
                <div class="variant-hint text-[11px] text-gray-500 dark:text-gray-400 mt-1">Select only for finished pack inward, such as Dimsum 10 pcs / 20 pcs.</div>
            </div>

            <div class="lg:col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Inward mode</label>
                <select class="receipt-type w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][receipt_type]" required>
                    <option value="pieces_weight">Pieces with weight</option>
                    <option value="quantity">Quantity</option>
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">HSN / GST</label>
                <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2">
                    <div class="hsn-display text-[13px] font-semibold text-gray-900 dark:text-gray-50">—</div>
                    <div class="hsn-hint text-[11px] text-gray-500 dark:text-gray-400">Select product.</div>
                </div>
                <select class="hsn-select hidden mt-2 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    <option value="">Select HSN…</option>
                    @foreach($hsnCodes as $h)
                        <option value="{{ $h->id }}">{{ $h->code }} — {{ $h->description ?: $h->name }} ({{ number_format((float) $h->gst_rate, 2) }}%)</option>
                    @endforeach
                </select>
                <input type="hidden" class="hsn-hidden" name="items[__INDEX__][hsn_code_id]" value="">
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-6">
            <div>
                <label class="qty-label block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Pieces</label>
                <input type="number" step="0.001" min="0" class="qty w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][quantity]">
                <div class="qty-help text-[11px] text-gray-500 dark:text-gray-400 mt-1">Number of physical pieces.</div>
            </div>

            <div class="weight-wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Total weight kg</label>
                <input type="number" step="0.001" min="0" class="total-weight w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][total_weight_kg]">
                <div class="weight-help text-[11px] text-gray-500 dark:text-gray-400 mt-1">Auto-filled from individual weights.</div>
            </div>

            <div>
                <label class="unit-cost-label block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Rate per kg</label>
                <input type="number" step="0.01" min="0" class="unit-cost w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][unit_cost]" required>
                <label class="mt-2 inline-flex items-center gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                    <input type="hidden" name="items[__INDEX__][unit_cost_includes_gst]" value="0">
                    <input type="checkbox" value="1" class="cost-includes-gst rounded border-gray-300 dark:border-gray-700" name="items[__INDEX__][unit_cost_includes_gst]">
                    Unit cost includes GST
                </label>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Tax amount</label>
                <input type="number" step="0.01" min="0" class="tax-amount w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][tax_amount]" value="0">
                <input type="hidden" class="tax-manual" name="items[__INDEX__][tax_manual]" value="0">
                <div class="tax-help text-[11px] text-gray-500 dark:text-gray-400 mt-1">Auto from HSN unless edited.</div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">MRP incl. GST</label>
                <input type="number" step="0.01" min="0" class="mrp-incl-gst w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][mrp_incl_gst]" placeholder="Optional">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Updates selected pack variant MRP, otherwise product MRP.</div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                <div class="flex items-center justify-between text-[12px]"><span class="text-gray-500 dark:text-gray-400">Subtotal</span><span class="font-semibold">₹<span class="line-subtotal">0.00</span></span></div>
                <div class="flex items-center justify-between text-[12px] mt-1"><span class="text-gray-500 dark:text-gray-400">Line total</span><span class="font-semibold">₹<span class="line-total">0.00</span></span></div>
                <div class="format-detail text-[11px] text-gray-500 dark:text-gray-400 mt-2">—</div>
            </div>
        </div>

        <details class="individual-weights-wrap rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
            <summary class="cursor-pointer text-[12px] font-medium text-gray-700 dark:text-gray-300">Individual weights</summary>
            <div class="mt-3 grid gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Paste weights in kg</label>
                    <textarea rows="3" class="individual-weights-text w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][individual_weights_text]" placeholder="6.200\n6.450\n5.950"></textarea>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Use one per line, comma separated, or space separated. These become rows in inventory_pieces.</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3 text-[12px]">
                    <div class="text-gray-500 dark:text-gray-400">Parsed pieces</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50"><span class="parsed-count">0</span></div>
                    <div class="mt-2 text-gray-500 dark:text-gray-400">Parsed total weight</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50"><span class="parsed-weight">0.000</span> kg</div>
                </div>
            </div>
        </details>

        <details class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
            <summary class="cursor-pointer text-[12px] font-medium text-gray-700 dark:text-gray-300">Batch / date details</summary>
            <div class="mt-3 grid gap-3 md:grid-cols-4">
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Batch code</label>
                    <input type="text" maxlength="120" class="batch-code w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][batch_code]">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Mfg date</label>
                    <input type="date" class="mfg-date w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][mfg_date]">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Packed date</label>
                    <input type="date" class="packed-date w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][packed_date]">
                </div>
                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Expiry date</label>
                    <input type="date" class="expiry-date w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]" name="items[__INDEX__][expiry_date]">
                </div>
            </div>
        </details>
    </div>
</template>

<script>
(function () {
    const container = document.getElementById('items-container');
    const template = document.getElementById('invoice-item-row-template');
    const addButton = document.getElementById('add-item-row');
    const subtotalEl = document.getElementById('subtotal-val');
    const taxEl = document.getElementById('tax-val');
    const grandEl = document.getElementById('grand-val');

    const productMeta = @json($productMeta);
    const variantMeta = @json($variantMeta);
    const hsnMeta = @json($hsnMeta);
    const oldItems = @json(old('items', []));
    let nextIndex = 0;

    function numberValue(value) {
        const n = parseFloat(String(value ?? '').trim());
        return Number.isFinite(n) ? n : 0;
    }

    function money(value) {
        return numberValue(value).toFixed(2);
    }

    function parseWeights(text) {
        return String(text || '')
            .split(/[\s,;]+/)
            .map(v => parseFloat(v))
            .filter(v => Number.isFinite(v) && v > 0);
    }

    function rowElements(row) {
        return {
            product: row.querySelector('.product-select'),
            variantWrap: row.querySelector('.variant-wrap'),
            variant: row.querySelector('.product-variant-select'),
            variantHint: row.querySelector('.variant-hint'),
            receipt: row.querySelector('.receipt-type'),
            hsnHidden: row.querySelector('.hsn-hidden'),
            hsnSelect: row.querySelector('.hsn-select'),
            hsnDisplay: row.querySelector('.hsn-display'),
            hsnHint: row.querySelector('.hsn-hint'),
            qty: row.querySelector('.qty'),
            qtyLabel: row.querySelector('.qty-label'),
            qtyHelp: row.querySelector('.qty-help'),
            weightWrap: row.querySelector('.weight-wrap'),
            totalWeight: row.querySelector('.total-weight'),
            individualWrap: row.querySelector('.individual-weights-wrap'),
            individualText: row.querySelector('.individual-weights-text'),
            parsedCount: row.querySelector('.parsed-count'),
            parsedWeight: row.querySelector('.parsed-weight'),
            unitCost: row.querySelector('.unit-cost'),
            unitCostLabel: row.querySelector('.unit-cost-label'),
            includesGst: row.querySelector('.cost-includes-gst'),
            tax: row.querySelector('.tax-amount'),
            taxManual: row.querySelector('.tax-manual'),
            taxHelp: row.querySelector('.tax-help'),
            subtotal: row.querySelector('.line-subtotal'),
            total: row.querySelector('.line-total'),
            formatDetail: row.querySelector('.format-detail'),
            summary: row.querySelector('.item-summary'),
        };
    }

    function hsnRate(hsnId) {
        return hsnId && hsnMeta[String(hsnId)] ? numberValue(hsnMeta[String(hsnId)].gst_rate) : 0;
    }

    function hsnText(hsnId) {
        if (!hsnId || !hsnMeta[String(hsnId)]) return null;
        const meta = hsnMeta[String(hsnId)];
        return `${meta.code || 'HSN'} · GST ${money(meta.gst_rate)}%`;
    }

    function selectedProduct(row) {
        const els = rowElements(row);
        return els.product?.value ? productMeta[String(els.product.value)] || null : null;
    }

    function selectedVariant(row) {
        const els = rowElements(row);
        const product = selectedProduct(row);
        if (!product || !els.variant?.value) return null;
        return (variantMeta[String(product.id)] || []).find(v => String(v.id) === String(els.variant.value)) || null;
    }

    function selectedTarget(row) {
        const product = selectedProduct(row);
        if (!product) return null;
        const variant = selectedVariant(row);
        return variant ? { ...product, ...variant, product_id: product.id, variant_id: variant.id } : product;
    }

    function populateVariants(row, selectedId = null) {
        const els = rowElements(row);
        const product = selectedProduct(row);
        const variants = product ? (variantMeta[String(product.id)] || []) : [];

        if (!els.variant || !els.variantWrap) return;

        els.variant.innerHTML = '<option value="">Product-level stock</option>';
        variants.forEach(variant => {
            const option = document.createElement('option');
            option.value = String(variant.id);
            option.textContent = `${variant.label || variant.sku || 'Variant'}${variant.is_active ? '' : ' — inactive'}`;
            els.variant.appendChild(option);
        });

        const hasVariants = variants.length > 0;
        els.variantWrap.classList.toggle('hidden', !hasVariants);
        els.variant.disabled = !hasVariants;
        els.variant.value = selectedId ? String(selectedId) : '';
        els.variantHint.textContent = hasVariants
            ? 'Optional. Select for finished pack inward such as Dimsum 10 pcs / 20 pcs.'
            : 'No pack variants exist for this product.';
    }

    function updateRowsNumbering() {
        container.querySelectorAll('.row-no').forEach((el, index) => {
            el.textContent = String(index + 1);
        });
    }

    function applyProduct(row) {
        const els = rowElements(row);
        const product = selectedProduct(row);

        if (!product) {
            populateVariants(row);
            els.hsnHidden.value = '';
            els.hsnDisplay.textContent = '—';
            els.hsnHint.textContent = 'Select product.';
            els.hsnSelect.classList.add('hidden');
            updateReceipt(row);
            return;
        }

        populateVariants(row);

        if (product.hsn_code_id && hsnMeta[String(product.hsn_code_id)]) {
            els.hsnHidden.value = String(product.hsn_code_id);
            els.hsnDisplay.textContent = hsnText(product.hsn_code_id) || '—';
            els.hsnHint.textContent = 'Locked from product master.';
            els.hsnSelect.classList.add('hidden');
            els.hsnSelect.required = false;
        } else {
            els.hsnHidden.value = els.hsnSelect.value || '';
            els.hsnDisplay.textContent = hsnText(els.hsnHidden.value) || 'Select HSN';
            els.hsnHint.textContent = 'This HSN will be saved on the product.';
            els.hsnSelect.classList.remove('hidden');
            els.hsnSelect.required = true;
        }

        const variants = variantMeta[String(product.id)] || [];
        if (variants.length > 0) {
            els.receipt.value = 'quantity';
        } else if (product.sell_unit === 'kg' || product.pack_type === 'bulk' || product.pack_type === 'variable_weight') {
            els.receipt.value = 'pieces_weight';
        } else {
            els.receipt.value = 'quantity';
        }

        updateReceipt(row);
        calculateRow(row);
    }

    function updateHsnFromSelect(row) {
        const els = rowElements(row);
        els.hsnHidden.value = els.hsnSelect.value || '';
        els.hsnDisplay.textContent = hsnText(els.hsnHidden.value) || 'Select HSN';
        els.taxManual.value = '0';
        calculateRow(row);
    }

    function updateIndividualWeights(row) {
        const els = rowElements(row);
        const weights = parseWeights(els.individualText.value);
        const total = weights.reduce((sum, v) => sum + v, 0);
        els.parsedCount.textContent = String(weights.length);
        els.parsedWeight.textContent = total.toFixed(3);

        if (weights.length > 0 && els.receipt.value === 'pieces_weight') {
            els.qty.value = String(weights.length);
            els.totalWeight.value = total.toFixed(3);
        }
    }

    function updateReceipt(row) {
        const els = rowElements(row);
        const receipt = els.receipt.value;
        const isWeightMode = receipt === 'pieces_weight';

        if (isWeightMode && els.variant) {
            els.variant.value = '';
        }
        if (els.variant) {
            els.variant.disabled = isWeightMode || els.variant.options.length <= 1;
        }

        els.weightWrap.classList.toggle('hidden', !isWeightMode);
        els.individualWrap.classList.toggle('hidden', !isWeightMode);
        els.totalWeight.required = isWeightMode;

        if (isWeightMode) {
            els.qtyLabel.textContent = 'Pieces';
            els.qtyHelp.textContent = 'Physical piece count. Auto-filled if individual weights are pasted.';
            els.unitCostLabel.textContent = 'Rate per kg';
            els.summary.textContent = 'Creates one raw lot and optional inventory_pieces rows for each weight.';
            els.formatDetail.textContent = 'Amount = total kg × rate per kg';
            updateIndividualWeights(row);
        } else {
            els.qtyLabel.textContent = 'Quantity';
            els.qtyHelp.textContent = 'Received packs / pieces / cartons / units.';
            els.unitCostLabel.textContent = 'Unit cost';
            const variant = selectedVariant(row);
            els.summary.textContent = variant
                ? `Creates stock quantity for pack variant: ${variant.label || variant.sku || 'selected variant'}.`
                : 'Creates stock quantity for the selected product.';
            els.formatDetail.textContent = 'Amount = quantity × unit cost';
        }

        calculateRow(row);
    }

    function rowBasis(row) {
        const els = rowElements(row);
        return els.receipt.value === 'pieces_weight'
            ? numberValue(els.totalWeight.value)
            : numberValue(els.qty.value);
    }

    function calculateRow(row) {
        const els = rowElements(row);
        const basis = rowBasis(row);
        const cost = numberValue(els.unitCost.value);
        const subtotal = basis * cost;
        const includesGst = Boolean(els.includesGst.checked);

        if (includesGst) {
            els.tax.value = '0.00';
            els.tax.readOnly = true;
            els.tax.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
            els.taxManual.value = '0';
            els.taxHelp.textContent = 'Cost includes GST, so no extra tax is added.';
        } else {
            els.tax.readOnly = false;
            els.tax.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
            els.taxHelp.textContent = 'Auto from HSN unless edited.';

            if (els.taxManual.value !== '1') {
                const rate = hsnRate(els.hsnHidden.value);
                els.tax.value = money((subtotal * rate) / 100);
            }
        }

        const tax = includesGst ? 0 : numberValue(els.tax.value);
        els.subtotal.textContent = money(subtotal);
        els.total.textContent = money(subtotal + tax);
        updateTotals();
    }

    function updateTotals() {
        let subtotal = 0;
        let tax = 0;

        container.querySelectorAll('[data-row="1"]').forEach(row => {
            const els = rowElements(row);
            subtotal += rowBasis(row) * numberValue(els.unitCost.value);
            tax += els.includesGst.checked ? 0 : numberValue(els.tax.value);
        });

        subtotalEl.textContent = money(subtotal);
        taxEl.textContent = money(tax);
        grandEl.textContent = money(subtotal + tax);
    }

    function setValue(row, selector, value) {
        if (value === undefined || value === null) return;
        const el = row.querySelector(selector);
        if (el) el.value = String(value);
    }

    function attachRow(row) {
        const els = rowElements(row);
        row.querySelector('.remove-row')?.addEventListener('click', () => {
            row.remove();
            if (!container.querySelector('[data-row="1"]')) addRow();
            updateRowsNumbering();
            updateTotals();
        });

        els.product?.addEventListener('change', () => applyProduct(row));
        els.variant?.addEventListener('change', () => {
            if (els.variant.value) {
                els.receipt.value = 'quantity';
            }
            updateReceipt(row);
            calculateRow(row);
        });
        els.receipt?.addEventListener('change', () => updateReceipt(row));
        els.hsnSelect?.addEventListener('change', () => updateHsnFromSelect(row));
        els.qty?.addEventListener('input', () => calculateRow(row));
        els.totalWeight?.addEventListener('input', () => calculateRow(row));
        els.individualText?.addEventListener('input', () => { updateIndividualWeights(row); calculateRow(row); });
        els.unitCost?.addEventListener('input', () => calculateRow(row));
        els.includesGst?.addEventListener('change', () => calculateRow(row));
        els.tax?.addEventListener('input', () => {
            els.taxManual.value = '1';
            calculateRow(row);
        });
    }

    function prefillRow(row, data) {
        setValue(row, '.product-select', data.product_id);
        applyProduct(row);
        setValue(row, '.product-variant-select', data.product_variant_id);
        setValue(row, '.receipt-type', data.receipt_type || 'quantity');
        updateReceipt(row);
        setValue(row, '.hsn-select', data.hsn_code_id);
        if (data.hsn_code_id) updateHsnFromSelect(row);
        setValue(row, '.qty', data.quantity || 1);
        setValue(row, '.total-weight', data.total_weight_kg);
        setValue(row, '.individual-weights-text', data.individual_weights_text);
        updateIndividualWeights(row);
        setValue(row, '.unit-cost', data.unit_cost);
        if (String(data.unit_cost_includes_gst || '0') === '1') {
            row.querySelector('.cost-includes-gst').checked = true;
        }
        setValue(row, '.tax-manual', data.tax_manual || '0');
        setValue(row, '.tax-amount', data.tax_amount);
        setValue(row, '.mrp-incl-gst', data.mrp_incl_gst);
        setValue(row, '.batch-code', data.batch_code);
        setValue(row, '.mfg-date', data.mfg_date);
        setValue(row, '.packed-date', data.packed_date);
        setValue(row, '.expiry-date', data.expiry_date);
        calculateRow(row);
    }

    function addRow(prefill = null) {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        container.appendChild(row);
        attachRow(row);
        nextIndex++;
        updateRowsNumbering();

        if (prefill) {
            prefillRow(row, prefill);
        } else {
            row.querySelector('.qty').value = '1';
            row.querySelector('.unit-cost').value = '0';
            updateReceipt(row);
            calculateRow(row);
        }

        return row;
    }

    addButton?.addEventListener('click', () => addRow());

    if (Array.isArray(oldItems) && oldItems.length > 0) {
        oldItems.forEach(item => addRow(item));
    } else {
        addRow();
    }
})();
</script>
@endsection

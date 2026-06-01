@extends('layouts.company')

@section('title', 'New vendor invoice')

@section('content')
@php
    /** @var \Illuminate\Support\Collection|\App\Models\Vendor[] $vendors */
    /** @var \Illuminate\Support\Collection|\App\Models\Product[] $products */
    /** @var \Illuminate\Support\Collection|\App\Models\HsnCode[] $hsnCodes */

    $has = fn(string $r) => \Illuminate\Support\Facades\Route::has($r);

    $storeUrl = $has('admin.vendor-invoices.store') ? route('admin.vendor-invoices.store') : '#';
    $backUrl  = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : url()->previous();

    $barcodeLookupUrl = $has('admin.products.barcodeLookup') ? route('admin.products.barcodeLookup') : null;

    $productMeta = collect($products ?? [])->mapWithKeys(function ($p) {
        $variants = collect($p->variants ?? []);
        $isVariantProduct = strtolower((string)($p->type ?? '')) === 'variable' || $variants->isNotEmpty();

        return [(string)$p->id => [
            'id'                 => (int)$p->id,
            'name'               => (string)$p->name,
            'sku'                => (string)($p->sku ?? ''),
            'barcode'            => (string)($p->barcode ?? ''),
            'type'               => (string)($p->type ?? 'simple'),
            'is_variant_product' => $isVariantProduct,
            'is_active'          => (bool)$p->is_active,
            'hsn_code_id'        => $p->hsn_code_id ? (int)$p->hsn_code_id : null,
            'variants'           => $variants->map(function ($v) {
                $label = $v->sku ? (string)$v->sku : ('Variant #' . $v->id);
                if (!empty($v->name)) {
                    $label .= ' — ' . $v->name;
                }
                if (!empty($v->product_weight)) {
                    $label .= ' — ' . number_format((float) $v->product_weight, 3) . ' kg';
                }
                if (! $v->is_active) {
                    $label .= ' — inactive';
                }

                return [
                    'id'             => (int)$v->id,
                    'label'          => $label,
                    'sku'            => (string)($v->sku ?? ''),
                    'barcode'        => (string)($v->barcode ?? ''),
                    'price'          => (float)($v->price ?? 0),
                    'product_weight' => $v->product_weight !== null ? (float)$v->product_weight : null,
                    'pricing_unit'   => (string)($v->pricing_unit ?? ''),
                    'is_active'      => (bool)$v->is_active,
                ];
            })->values(),
        ]];
    });

    $hsnMeta = collect($hsnCodes ?? [])->mapWithKeys(function ($h) {
        return [(string)$h->id => [
            'id'       => (int)$h->id,
            'code'     => (string)$h->code,
            'gst_rate' => (float)($h->gst_rate ?? 0),
        ]];
    });
@endphp

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

    {{-- Top bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">New vendor invoice</h1>
            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                Create a vendor invoice, update stock, and capture inward batch/expiry details.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ $backUrl }}"
               class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Back
            </a>

            @if($has('admin.products.create'))
                <a href="{{ route('admin.products.create') }}" target="_blank"
                   class="text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    New product
                </a>
            @endif
        </div>
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

    <form method="POST" action="{{ $storeUrl }}" class="space-y-4">
        @csrf

        {{-- Step 1: Invoice details --}}
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Step 1</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Invoice details</div>
                <div class="text-[12px] text-gray-500 dark:text-gray-400">Vendor + invoice metadata.</div>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor</label>
                        <select name="vendor_id" required
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                            <option value="">Select vendor…</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" @selected((int)old('vendor_id', $vendor?->id ?? 0) === (int)$v->id)>
                                    {{ $v->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice number</label>
                        <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" required
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice date</label>
                        <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Due date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (optional)</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    </div>
                </div>
            </div>
        </section>

        {{-- Step 2: Items --}}
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Step 2</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Invoice items & inward stock</div>
                        <div class="text-[12px] text-gray-500 dark:text-gray-400">
                            Add active products or inactive draft products. Receiving stock does not publish drafts.
                        </div>
                    </div>

                    <button type="button" id="add-item-row"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-[12px] hover:bg-gray-50 dark:hover:bg-gray-800">
                        <span class="text-lg leading-none">+</span> Add item
                    </button>
                </div>

                {{-- Barcode scan --}}
                <div class="mt-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <label for="scan-barcode" class="text-[12px] font-medium text-gray-700 dark:text-gray-300">
                                Scan barcode
                            </label>
                            <input id="scan-barcode"
                                   type="text"
                                   autocomplete="off"
                                   class="w-64 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                                   placeholder="Focus and scan">
                        </div>
                        <div id="scan-barcode-message" class="text-[11px] text-gray-500 dark:text-gray-400">
                            Scanning adds a row (qty 1). Active and inactive draft products are both supported.
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 space-y-4">
                <div id="items-container" class="space-y-4"></div>

                {{-- Totals --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
                    <div class="grid gap-3 sm:grid-cols-3 text-[13px]">
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                            <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span id="subtotal-val">0.00</span></span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                            <span class="text-gray-600 dark:text-gray-300">Tax total</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span id="tax-val">0.00</span></span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                            <span class="text-gray-600 dark:text-gray-300">Grand total</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span id="grand-val">0.00</span></span>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
                        Tip: HSN is shown from product master. Set HSN on product to auto-calc GST.
                    </p>
                </div>
            </div>
        </section>

        {{-- Bottom actions --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ $backUrl }}"
               class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
                Cancel
            </a>

            <button type="submit"
                    class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-6 py-2 text-[13px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                Save invoice & update stock
            </button>
        </div>
    </form>
</div>

{{-- Row template --}}
<template id="invoice-item-row-template">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950/20 p-4 space-y-4" data-row="1">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-xl bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 flex items-center justify-center text-[12px] font-semibold">
                    <span class="row-no">#</span>
                </div>
                <div>
                    <div class="text-[13px] font-semibold text-gray-900 dark:text-gray-50">Invoice item</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">Product + inward details</div>
                </div>
            </div>

            <button type="button"
                    class="remove-row text-[12px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                Remove
            </button>
        </div>

        {{-- Product + HSN display --}}
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Product</label>
                <select class="product-select w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                        name="items[__INDEX__][product_id]" required>
                    <option value="">Select…</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->name }}
                            @if($p->sku) ({{ $p->sku }}) @endif
                            @unless($p->is_active) — Draft / inactive @endunless
                        </option>
                    @endforeach
                </select>
                <div class="product-status-hint text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    Active and inactive draft products are available for inward stock. Drafts stay hidden from the storefront.
                </div>

                <div class="variant-wrap hidden mt-2">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Variant</label>
                    <select class="variant-select w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                            name="items[__INDEX__][product_variant_id]">
                        <option value="">Select variant…</option>
                    </select>
                    <div class="variant-hint text-[11px] text-amber-700 dark:text-amber-300 mt-1">
                        Qty mode requires a selected variant. Pieces mode can auto-create one variant per weight row.
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">HSN</label>

                <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2">
                    <div class="text-[13px] font-semibold text-gray-900 dark:text-gray-50 hsn-display">—</div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 hsn-hint"></div>
                </div>

                <select class="hsn-select hidden mt-2 w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]">
                    <option value="">Select HSN from invoice…</option>
                    @foreach($hsnCodes as $h)
                        <option value="{{ $h->id }}">
                            {{ $h->code }} — {{ $h->description }} ({{ number_format((float)$h->gst_rate, 2) }}% GST)
                        </option>
                    @endforeach
                </select>
                <div class="hsn-select-hint hidden text-[11px] text-amber-700 dark:text-amber-300 mt-1">
                    This product has no HSN yet. Select the HSN from the vendor invoice; it will be saved on the product master.
                </div>

                <input type="hidden" class="hsn-id-hidden" name="items[__INDEX__][hsn_code_id]" value="">
            </div>
        </div>

        {{-- Inward meta --}}
        <div class="grid gap-3 md:grid-cols-5">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Inward mode</label>
                <select class="inward-mode w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                        name="items[__INDEX__][inward_mode]">
                    <option value="qty">Qty (normal)</option>
                    <option value="pieces">Pieces (weights)</option>
                </select>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Batch code</label>
                <input type="text" maxlength="80"
                       class="batch-code w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][batch_code]"
                       placeholder="Optional">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Mfg date</label>
                <input type="date"
                       class="mfg-date w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][mfg_date]">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Packed date</label>
                <input type="date"
                       class="packed-date w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][packed_date]">
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Expiry date</label>
                <input type="date"
                       class="expiry-date w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][expiry_date]">
            </div>
        </div>

        {{-- Pieces --}}
        <div class="pieces-wrap hidden rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-3">
            <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Piece weights (kg) — one per piece
            </label>
            <textarea rows="3"
                      class="piece-weights w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                      name="items[__INDEX__][piece_weights]"
                      placeholder="2.45&#10;2.30&#10;2.55&#10;2.40"></textarea>
            <div class="pieces-summary text-[11px] text-gray-500 dark:text-gray-400 mt-1"></div>
        </div>

        {{-- Qty / weight / cost / tax + totals --}}
        <div class="grid gap-3 md:grid-cols-6 items-end">
            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                <input type="number" step="0.01" min="0.01"
                       class="qty w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][quantity]" required>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Units in Qty mode; number of pieces in Pieces mode. Total kg is tracked separately.</div>
            </div>

            <div class="unit-weight-wrap">
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Unit weight (kg)</label>
                <input type="number" step="0.001" min="0"
                       class="unit-weight w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][unit_weight_kg]"
                       placeholder="e.g. 0.500">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Per unit in Qty mode. Hidden for Pieces mode.</div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Unit cost</label>
                <input type="number" step="0.01" min="0"
                       class="unit-cost w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][unit_cost]" required>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Per unit/piece.</div>
            </div>

            <div>
                <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Tax amount</label>
                <input type="number" step="0.01" min="0"
                       class="tax-amount w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px]"
                       name="items[__INDEX__][tax_amount]" value="0" data-auto="1">
                <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Auto-calculated from HSN unless edited.</div>
            </div>

            <div class="md:col-span-2">
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-4 py-3">
                    <input type="hidden" class="total-weight-hidden" name="items[__INDEX__][total_weight_kg]" value="">

                    <div class="flex items-center justify-between text-[13px]">
                        <span class="text-gray-600 dark:text-gray-300">Received weight</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-50"><span class="total-weight">0.000</span> kg</span>
                    </div>

                    <div class="flex items-center justify-between text-[13px] mt-1">
                        <span class="text-gray-600 dark:text-gray-300">Line subtotal</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span class="line-subtotal">0.00</span></span>
                    </div>

                    <div class="flex items-center justify-between text-[13px] mt-1">
                        <span class="text-gray-600 dark:text-gray-300">Line total</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-50">₹<span class="line-total">0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    const container = document.getElementById('items-container');
    const tpl = document.getElementById('invoice-item-row-template');
    const addBtn = document.getElementById('add-item-row');

    const subtotalEl = document.getElementById('subtotal-val');
    const taxValEl   = document.getElementById('tax-val');
    const grandEl    = document.getElementById('grand-val');

    const productMeta = @json($productMeta);
    const hsnMeta     = @json($hsnMeta);

    const barcodeLookupUrl = @json($barcodeLookupUrl);
    const oldItems = @json(old('items', []));

    let index = 0;

    function money(n) {
        const v = isFinite(n) ? Number(n) : 0;
        return v.toFixed(2);
    }

    function parseNum(v) {
        const n = parseFloat(String(v ?? '').trim());
        return isFinite(n) ? n : 0;
    }

    function updateRowNumbers() {
        const rows = container.querySelectorAll('.row-no');
        rows.forEach((el, i) => el.textContent = (i + 1));
    }

    function updateTotals() {
        let sub = 0;
        let tax = 0;

        container.querySelectorAll('[data-row="1"]').forEach(row => {
            const qty  = parseNum(row.querySelector('.qty')?.value);
            const cost = parseNum(row.querySelector('.unit-cost')?.value);
            const t    = parseNum(row.querySelector('.tax-amount')?.value);

            const lineSub = qty * cost;
            const lineTot = lineSub + t;

            row.querySelector('.line-subtotal').textContent = money(lineSub);
            row.querySelector('.line-total').textContent    = money(lineTot);

            sub += lineSub;
            tax += t;
        });

        subtotalEl.textContent = money(sub);
        taxValEl.textContent   = money(tax);
        grandEl.textContent    = money(sub + tax);
    }

    function hsnLabel(hsnId) {
        if (!hsnId || !hsnMeta[String(hsnId)]) return null;

        const meta = hsnMeta[String(hsnId)];
        const code = meta.code || '—';
        const rate = parseNum(meta.gst_rate);

        return { code, rate };
    }

    function setManualHsn(rowEl, hsnId) {
        const display = rowEl.querySelector('.hsn-display');
        const hint    = rowEl.querySelector('.hsn-hint');
        const hidden  = rowEl.querySelector('.hsn-id-hidden');
        const label   = hsnLabel(hsnId);

        if (hidden) hidden.value = hsnId || '';

        if (label) {
            if (display) display.textContent = label.code;
            if (hint) hint.textContent = `GST ${label.rate}% (selected from invoice)`;
        } else {
            if (display) display.textContent = '—';
            if (hint) hint.textContent = 'Select HSN from the vendor invoice.';
        }
    }

    function applyHsnDisplay(rowEl, productId) {
        const display = rowEl.querySelector('.hsn-display');
        const hint    = rowEl.querySelector('.hsn-hint');
        const hidden  = rowEl.querySelector('.hsn-id-hidden');
        const hsnSelect = rowEl.querySelector('.hsn-select');
        const hsnSelectHint = rowEl.querySelector('.hsn-select-hint');

        if (!display || !hidden) return;

        const meta = productMeta[String(productId)] || null;
        const hsnId = meta && meta.hsn_code_id ? String(meta.hsn_code_id) : '';

        const rowStatus = rowEl.querySelector('.product-status-hint');
        if (rowStatus && meta) {
            rowStatus.textContent = meta.is_active === false
                ? 'Draft/inactive product selected. This invoice will add stock, but the product will stay hidden until activated.'
                : 'Active product selected.';
        }

        if (hsnId && hsnMeta[hsnId]) {
            const label = hsnLabel(hsnId);
            hidden.value = hsnId;
            display.textContent = label?.code || '—';
            if (hint) hint.textContent = `GST ${label?.rate ?? 0}% (locked from product)`;

            if (hsnSelect) {
                hsnSelect.value = hsnId;
                hsnSelect.required = false;
                hsnSelect.classList.add('hidden');
            }
            if (hsnSelectHint) hsnSelectHint.classList.add('hidden');
        } else {
            hidden.value = hsnSelect?.value || '';
            setManualHsn(rowEl, hidden.value);

            if (hsnSelect) {
                hsnSelect.required = true;
                hsnSelect.classList.remove('hidden');
            }
            if (hsnSelectHint) hsnSelectHint.classList.remove('hidden');
        }
    }

    function parsePieceWeightsFromRow(rowEl) {
        const pieceText = rowEl.querySelector('.piece-weights');
        const lines = String(pieceText?.value || '')
            .split(/\r\n|\n|\r/)
            .map(s => s.trim())
            .filter(Boolean);

        const weights = [];
        for (const ln of lines) {
            const n = parseFloat(ln);
            if (isFinite(n) && n > 0) weights.push(n);
        }

        return weights;
    }

    function populateVariantSelect(rowEl, productId, preferredVariantId = '') {
        const wrap = rowEl.querySelector('.variant-wrap');
        const select = rowEl.querySelector('.variant-select');
        const hint = rowEl.querySelector('.variant-hint');
        if (!wrap || !select) return;

        const meta = productMeta[String(productId)] || null;
        const variants = Array.isArray(meta?.variants) ? meta.variants : [];
        const mode = rowEl.querySelector('.inward-mode')?.value || 'qty';
        const isVariantProduct = Boolean(meta?.is_variant_product) || variants.length > 0;
        const piecesModeCanCreate = isVariantProduct && mode === 'pieces';

        select.innerHTML = '<option value="">Select variant…</option>';

        for (const variant of variants) {
            const option = document.createElement('option');
            option.value = String(variant.id);
            option.textContent = variant.label || ('Variant #' + variant.id);
            select.appendChild(option);
        }

        if (!isVariantProduct) {
            select.value = '';
            select.required = false;
            wrap.classList.add('hidden');
            if (hint) hint.textContent = '';
            return;
        }

        wrap.classList.remove('hidden');
        select.required = !piecesModeCanCreate;

        if (preferredVariantId) {
            select.value = String(preferredVariantId);
        } else if (variants.length === 1 && mode !== 'pieces') {
            select.value = String(variants[0].id);
        } else {
            select.value = '';
        }

        if (hint) {
            if (piecesModeCanCreate) {
                hint.textContent = select.value
                    ? 'Stock will be added to the selected variant.'
                    : 'Leave blank in Pieces mode to auto-create one kg-priced variant for each weight row.';
            } else if (variants.length) {
                hint.textContent = select.value
                    ? 'Stock will be added to the selected variant.'
                    : 'Please select the variant that this vendor invoice line is receiving.';
            } else {
                hint.textContent = 'This variable product has no variants yet. Switch to Pieces mode and enter one weight per new variant, or create variants first.';
            }
        }
    }

    function maybeAutoTax(rowEl) {
        const qtyEl  = rowEl.querySelector('.qty');
        const costEl = rowEl.querySelector('.unit-cost');
        const taxEl  = rowEl.querySelector('.tax-amount');
        const hsnHidden = rowEl.querySelector('.hsn-id-hidden');

        if (!qtyEl || !costEl || !taxEl || !hsnHidden) return;
        if (String(taxEl.dataset.auto || '1') !== '1') return;

        const hsnId = hsnHidden.value ? String(hsnHidden.value) : null;
        const rate = hsnId && hsnMeta[hsnId] ? parseNum(hsnMeta[hsnId].gst_rate) : 0;

        const qty  = parseNum(qtyEl.value);
        const cost = parseNum(costEl.value);

        const lineSub = qty * cost;
        const tax = (lineSub * rate) / 100;

        taxEl.value = money(tax);
    }

    function updateWeight(rowEl) {
        const modeSel = rowEl.querySelector('.inward-mode');
        const qtyEl = rowEl.querySelector('.qty');
        const unitWeightEl = rowEl.querySelector('.unit-weight');
        const totalWeightEl = rowEl.querySelector('.total-weight');
        const totalWeightHidden = rowEl.querySelector('.total-weight-hidden');

        if (!qtyEl || !totalWeightEl || !totalWeightHidden) return;

        const mode = modeSel ? modeSel.value : 'qty';
        const qty = parseNum(qtyEl.value);

        let totalWeight = 0;

        if (mode === 'pieces') {
            totalWeight = parsePieceWeightsFromRow(rowEl).reduce((a, b) => a + b, 0);
        } else {
            const unitWeight = unitWeightEl ? parseNum(unitWeightEl.value) : 0;
            totalWeight = qty * unitWeight;
        }

        totalWeightEl.textContent = totalWeight.toFixed(3);
        totalWeightHidden.value = totalWeight > 0 ? totalWeight.toFixed(3) : '';
    }

    function handleInwardMode(rowEl) {
        const modeSel = rowEl.querySelector('.inward-mode');
        const piecesWrap = rowEl.querySelector('.pieces-wrap');
        const qtyEl = rowEl.querySelector('.qty');
        const unitWeightWrap = rowEl.querySelector('.unit-weight-wrap');
        const unitWeightEl = rowEl.querySelector('.unit-weight');

        if (!modeSel || !piecesWrap || !qtyEl) return;

        const mode = modeSel.value || 'qty';

        if (mode === 'pieces') {
            piecesWrap.classList.remove('hidden');
            qtyEl.readOnly = true;
            qtyEl.classList.add('bg-gray-100', 'dark:bg-gray-900/40');

            if (unitWeightWrap) unitWeightWrap.classList.add('hidden');
            if (unitWeightEl) {
                unitWeightEl.value = '';
                unitWeightEl.readOnly = true;
            }
        } else {
            piecesWrap.classList.add('hidden');
            qtyEl.readOnly = false;
            qtyEl.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');

            if (unitWeightWrap) unitWeightWrap.classList.remove('hidden');
            if (unitWeightEl) unitWeightEl.readOnly = false;

            const summary = rowEl.querySelector('.pieces-summary');
            if (summary) summary.textContent = '';
        }

        updateWeight(rowEl);
    }

    function computePieces(rowEl) {
        const modeSel = rowEl.querySelector('.inward-mode');
        const pieceText = rowEl.querySelector('.piece-weights');
        const qtyEl = rowEl.querySelector('.qty');
        const summary = rowEl.querySelector('.pieces-summary');

        if (!modeSel || !pieceText || !qtyEl) return;
        if (modeSel.value !== 'pieces') return;

        const weights = parsePieceWeightsFromRow(rowEl);
        const total = weights.reduce((a, b) => a + b, 0);
        qtyEl.value = weights.length ? String(weights.length) : '';

        if (summary) {
            summary.textContent = weights.length
                ? `${weights.length} piece(s), total ${total.toFixed(3)} kg. Quantity/stock will increase by ${weights.length}.`
                : 'Enter one weight per piece (kg). Quantity/stock will be the number of valid lines.';
        }
    }

    function attachRowHandlers(rowEl) {
        const productSel    = rowEl.querySelector('.product-select');
        const qtyEl         = rowEl.querySelector('.qty');
        const unitWeightEl  = rowEl.querySelector('.unit-weight');
        const costEl        = rowEl.querySelector('.unit-cost');
        const taxEl         = rowEl.querySelector('.tax-amount');
        const hsnSelect     = rowEl.querySelector('.hsn-select');
        const modeSel       = rowEl.querySelector('.inward-mode');
        const pieceText     = rowEl.querySelector('.piece-weights');
        const removeBtn     = rowEl.querySelector('.remove-row');

        removeBtn?.addEventListener('click', () => {
            rowEl.remove();
            if (container.querySelectorAll('[data-row="1"]').length === 0) addRow();
            updateRowNumbers();
            updateTotals();
        });

        productSel?.addEventListener('change', () => {
            const pid = productSel.value ? parseInt(productSel.value, 10) : null;
            if (!pid) return;

            populateVariantSelect(rowEl, pid);
            applyHsnDisplay(rowEl, pid);

            if (taxEl) taxEl.dataset.auto = '1';
            maybeAutoTax(rowEl);
            updateTotals();
        });

        rowEl.querySelector('.variant-select')?.addEventListener('change', () => {
            const pid = productSel?.value ? parseInt(productSel.value, 10) : null;
            if (pid) populateVariantSelect(rowEl, pid, rowEl.querySelector('.variant-select')?.value || '');
        });

        [qtyEl, unitWeightEl, costEl].forEach(el => {
            if (!el) return;
            el.addEventListener('input', () => {
                updateWeight(rowEl);
                maybeAutoTax(rowEl);
                updateTotals();
            });
        });

        taxEl?.addEventListener('input', () => {
            taxEl.dataset.auto = '0';
            updateTotals();
        });

        hsnSelect?.addEventListener('change', () => {
            setManualHsn(rowEl, hsnSelect.value);
            if (taxEl) taxEl.dataset.auto = '1';
            maybeAutoTax(rowEl);
            updateTotals();
        });

        modeSel?.addEventListener('change', () => {
            handleInwardMode(rowEl);
            const pid = productSel?.value ? parseInt(productSel.value, 10) : null;
            if (pid) populateVariantSelect(rowEl, pid, rowEl.querySelector('.variant-select')?.value || '');
            computePieces(rowEl);
            updateWeight(rowEl);
            if (taxEl) taxEl.dataset.auto = '1';
            maybeAutoTax(rowEl);
            updateTotals();
        });

        pieceText?.addEventListener('input', () => {
            computePieces(rowEl);
            updateWeight(rowEl);
            if (taxEl) taxEl.dataset.auto = '1';
            maybeAutoTax(rowEl);
            updateTotals();
        });

        handleInwardMode(rowEl);
    }

    function setVal(rowEl, selector, val) {
        const el = rowEl.querySelector(selector);
        if (!el) return;
        if (val === undefined || val === null) return;
        el.value = String(val);
    }

    function prefillRow(rowEl, data) {
        if (!data) return;

        if (data.product_id) {
            setVal(rowEl, '.product-select', data.product_id);
            populateVariantSelect(rowEl, data.product_id, data.product_variant_id || '');
            applyHsnDisplay(rowEl, data.product_id);

            if (data.hsn_code_id) {
                setVal(rowEl, '.hsn-select', data.hsn_code_id);
                setManualHsn(rowEl, String(data.hsn_code_id));
            }
        }

        setVal(rowEl, '.inward-mode', data.inward_mode || 'qty');
        handleInwardMode(rowEl);

        setVal(rowEl, '.batch-code', data.batch_code);
        setVal(rowEl, '.mfg-date', data.mfg_date);
        setVal(rowEl, '.packed-date', data.packed_date);
        setVal(rowEl, '.expiry-date', data.expiry_date);

        setVal(rowEl, '.piece-weights', data.piece_weights);
        computePieces(rowEl);

        if ((data.inward_mode || 'qty') === 'qty') {
            setVal(rowEl, '.qty', data.quantity);
        }

        if (data.product_id) {
            populateVariantSelect(rowEl, data.product_id, data.product_variant_id || '');
        }

        setVal(rowEl, '.unit-weight', data.unit_weight_kg);
        updateWeight(rowEl);

        setVal(rowEl, '.unit-cost', data.unit_cost);

        const taxEl = rowEl.querySelector('.tax-amount');
        if (taxEl) {
            if (data.tax_amount !== undefined && data.tax_amount !== null && data.tax_amount !== '') {
                taxEl.value = money(parseNum(data.tax_amount));
                taxEl.dataset.auto = '0';
            } else {
                taxEl.dataset.auto = '1';
                maybeAutoTax(rowEl);
            }
        }

        maybeAutoTax(rowEl);
        updateTotals();
    }

    function addRow(prefill = null) {
        const html = tpl.innerHTML.replaceAll('__INDEX__', String(index));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const rowEl = wrap.firstElementChild;

        container.appendChild(rowEl);
        attachRowHandlers(rowEl);
        updateRowNumbers();

        index++;

        if (!prefill) {
            const qtyEl = rowEl.querySelector('.qty');
            const taxEl = rowEl.querySelector('.tax-amount');

            if (qtyEl) qtyEl.value = '1';
            if (taxEl) taxEl.value = '0';

            updateWeight(rowEl);
            updateTotals();
        } else {
            prefillRow(rowEl, prefill);
        }

        return rowEl;
    }

    addBtn?.addEventListener('click', () => addRow());

    if (Array.isArray(oldItems) && oldItems.length > 0) {
        oldItems.forEach(it => addRow(it));
    } else {
        addRow();
    }

    const scanInput = document.getElementById('scan-barcode');
    const scanMsg   = document.getElementById('scan-barcode-message');

    scanInput?.addEventListener('keydown', async function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        const code = String(scanInput.value || '').trim();
        if (!code) return;

        if (!barcodeLookupUrl) {
            scanMsg.textContent = 'Barcode lookup route not available.';
            return;
        }

        scanMsg.textContent = 'Looking up barcode…';

        try {
            const res = await fetch(barcodeLookupUrl + '?barcode=' + encodeURIComponent(code), {
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Product not found.');

            if (!data || data.ok !== true || !data.product || !data.product.id) {
                throw new Error(data.message || 'Product not found.');
            }

            addRow({
                product_id: data.product.id,
                product_variant_id: data.variant ? data.variant.id : '',
                quantity: 1,
                inward_mode: 'qty',
                tax_amount: 0
            });

            const inactiveText = data.product.is_active === false ? ' (draft/inactive)' : '';
            scanMsg.textContent = 'Added: ' + (data.product.name || ('Product #' + data.product.id)) + inactiveText;
            scanInput.value = '';
            scanInput.focus();
        } catch (err) {
            scanMsg.textContent = err.message || 'Barcode lookup failed.';
        }
    });
})();
</script>
@endsection
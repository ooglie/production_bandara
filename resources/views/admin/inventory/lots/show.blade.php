@extends('layouts.company')

@section('title', 'Inventory Lot #' . $lot->id)

@section('content')
@php
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);
    $fmt = fn($n, $d = 2) => rtrim(rtrim(number_format((float) $n, $d), '0'), '.');
    $mode = $lot->inward_mode ?? 'qty';
    $receivedQty = (float) ($lot->received_quantity ?? 0);
    $availableQty = (float) ($lot->available_quantity ?? ($remaining ?? max($receivedQty - (float) ($lot->consumed_quantity ?? 0), 0)));
    $consumedQty = max($receivedQty - $availableQty, (float) ($lot->consumed_quantity ?? 0));
@endphp

<div class="max-w-5xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Inventory Lot #{{ $lot->id }}
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ $product?->name ?? ('Product #' . $lot->product_id) }}
                @if($variant)
                    · {{ $variant->sku ?? ('Variant #' . $variant->id) }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($has('admin.inventory.lots.index'))
                <a href="{{ route('admin.inventory.lots.index') }}"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Back to lots
                </a>
            @endif
            @if($mode === 'pieces' && $has('admin.inventory.lots.pieces.index'))
                <a href="{{ route('admin.inventory.lots.pieces.index', $lot) }}"
                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    View pieces
                </a>
            @endif
            @if($has('admin.production.create'))
                <a href="{{ route('admin.production.create', ['lot_id' => $lot->id]) }}"
                   class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Produce / Repack
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Mode</div>
            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $mode === 'pieces' ? 'Pieces' : 'Quantity' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Batch</div>
            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $lot->batch_code ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Received</div>
            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $lot->received_date ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
            <div class="text-[10px] uppercase tracking-wide text-gray-400">Expiry</div>
            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $lot->expiry_date ?? '—' }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 font-medium text-gray-900 dark:text-gray-50">
            Stock summary
        </div>
        <dl class="grid gap-px bg-gray-100 dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-3 text-[11px]">
            <div class="bg-white dark:bg-gray-900 px-4 py-3">
                <dt class="text-gray-500 dark:text-gray-400">Received quantity</dt>
                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $fmt($lot->received_quantity ?? 0, 3) }}</dd>
            </div>
            <div class="bg-white dark:bg-gray-900 px-4 py-3">
                <dt class="text-gray-500 dark:text-gray-400">Consumed quantity</dt>
                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $fmt($consumedQty, 3) }}</dd>
            </div>
            <div class="bg-white dark:bg-gray-900 px-4 py-3">
                <dt class="text-gray-500 dark:text-gray-400">Remaining quantity</dt>
                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $remaining !== null ? $fmt($remaining, 3) : '—' }}</dd>
            </div>
            <div class="bg-white dark:bg-gray-900 px-4 py-3">
                <dt class="text-gray-500 dark:text-gray-400">Total weight</dt>
                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $lot->total_weight_kg !== null ? $fmt($lot->total_weight_kg, 3) . ' kg' : '—' }}</dd>
            </div>
            <div class="bg-white dark:bg-gray-900 px-4 py-3">
                <dt class="text-gray-500 dark:text-gray-400">Available pieces</dt>
                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $availablePieces ?? '—' }}</dd>
            </div>
            <div class="bg-white dark:bg-gray-900 px-4 py-3">
                <dt class="text-gray-500 dark:text-gray-400">Available weight</dt>
                <dd class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $availableKg !== null ? $fmt($availableKg, 3) . ' kg' : ($lot->available_weight_kg !== null ? $fmt($lot->available_weight_kg, 3) . ' kg' : '—') }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
        <h2 class="font-medium text-gray-900 dark:text-gray-50 mb-2">Notes</h2>
        <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $lot->notes ?: 'No notes recorded.' }}</p>
    </div>
</div>
@endsection

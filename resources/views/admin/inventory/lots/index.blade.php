@extends('layouts.company')

@section('title', 'Inventory Lots')

@section('content')
@php
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);
    $fmt = fn($n, $d = 2) => rtrim(rtrim(number_format((float)$n, $d), '0'), '.');
@endphp

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Inventory Lots
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Received batches from vendor invoices. Use these lots to produce blocks/slices/repacks.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($has('admin.inventory.packs.create'))
                <a href="{{ route('admin.inventory.packs.create') }}"
                   class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Create packs
                </a>
            @endif
            @if($has('admin.production.create'))
                <a href="{{ route('admin.production.create') }}"
                   class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    New production
                </a>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ $q ?? '' }}"
               placeholder="Search by batch code or lot id"
               class="w-64 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
        <button class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Search
        </button>
        @if(!empty($q))
            <a href="{{ url()->current() }}"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        @endif
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Lot</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Mode</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Batch</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Expiry</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Received</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Consumed</th>
                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Remaining</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($lots as $lot)
                @php
                    $p = $productsById[$lot->product_id] ?? null;
                    $v = $lot->product_variant_id ? ($variantsById[$lot->product_variant_id] ?? null) : null;

                    $mode = $lot->inward_mode ?? 'qty';

                    $remaining = null;
                    if ($mode === 'qty') {
                        $remaining = max(((float)$lot->received_quantity) - ((float)($lot->consumed_quantity ?? 0)), 0);
                    } else {
                        $stat = $pieceStats[(int)$lot->id] ?? null;
                        $remaining = $stat ? (float)$stat['kg'] : 0;
                    }
                @endphp

                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50">#{{ $lot->id }}</div>
                        <div class="text-[10px] text-gray-400">
                            {{ $lot->received_date ?? '—' }}
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50">
                            {{ $p?->name ?? ('Product #' . $lot->product_id) }}
                        </div>
                        <div class="text-[10px] text-gray-400">
                            @if($v) {{ $v->sku ?? ('Variant #' . $v->id) }} @endif
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <span class="text-[10px] px-2 py-0.5 rounded-full border border-gray-300 text-gray-600 dark:text-gray-300">
                            {{ $mode === 'pieces' ? 'Pieces' : 'Qty' }}
                        </span>
                        @if($mode === 'pieces')
                            @php $stat = $pieceStats[(int)$lot->id] ?? ['count'=>0,'kg'=>0]; @endphp
                            <div class="text-[10px] text-gray-400 mt-1">
                                {{ $stat['count'] }} pcs · {{ number_format((float)$stat['kg'],3) }} kg avail
                            </div>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        {{ $lot->batch_code ?? '—' }}
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        {{ $lot->expiry_date ?? '—' }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                        {{ $fmt($lot->received_quantity ?? 0, 2) }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">
                        {{ $fmt($lot->consumed_quantity ?? 0, 2) }}
                    </td>
                    <td class="px-3 py-2 text-right text-gray-900 dark:text-gray-50">
                        {{ $fmt($remaining ?? 0, $mode === 'pieces' ? 3 : 2) }}
                    </td>
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-2">
                            @if($has('admin.inventory.packs.create') && ($lot->can_repack ?? false) && ($lot->lot_status ?? 'available') === 'available')
                                <a href="{{ route('admin.inventory.packs.create', ['source_inventory_lot_id' => $lot->id]) }}"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Create packs
                                </a>
                            @endif
                            @if($has('admin.production.create'))
                                <a href="{{ route('admin.production.create', ['lot_id' => $lot->id]) }}"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Produce / Repack
                                </a>
                            @endif
                            @if(($mode === 'pieces') && $has('admin.inventory.lots.pieces.index'))
                                <a href="{{ route('admin.inventory.lots.pieces.index', $lot) }}"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Pieces
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No lots found.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $lots->links() }}
    </div>
</div>
@endsection

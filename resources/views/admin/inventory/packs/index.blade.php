@extends('layouts.company')

@section('title', 'Inventory Packs')

@section('content')
@php
    $fmt = fn($n, $d = 2) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, $d), '0'), '.');
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Inventory Packs</h1>
            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                Convert repackable source stock such as bulk belly, fillets, boxes, or loose pieces into saleable finished packs/cuts. Output stock can belong to a different finished product when you make slices/slabs from a raw source lot.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.inventory.lots.index') }}" class="rounded border border-gray-300 px-4 py-2 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Inventory lots</a>
            <a href="{{ route('admin.inventory.packs.create') }}" class="rounded bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">+ New repack</a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        This screen updates backend pack stock and the output product stock. Frontend product display remains unchanged.
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <table class="min-w-full divide-y divide-gray-200 text-xs dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr class="text-left text-[11px] uppercase text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">Pack</th>
                    <th class="px-3 py-2">Output</th>
                    <th class="px-3 py-2">Source lot</th>
                    <th class="px-3 py-2 text-right">Pack qty</th>
                    <th class="px-3 py-2 text-right">Pieces / pack</th>
                    <th class="px-3 py-2 text-right">Source consumed</th>
                    <th class="px-3 py-2 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($packs as $pack)
                    @php
                        $displaySourceLot = $pack->sourceLot?->parentLot ?: $pack->sourceLot;
                    @endphp
                    <tr>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $pack->pack_code ?: ('Pack #' . $pack->id) }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ optional($pack->packed_date)->format('d M Y') ?: '—' }}
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">{{ $pack->product?->name ?? ('Product #' . $pack->product_id) }}</div>
                            @if($pack->productVariant)
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">Variant: {{ $pack->productVariant->name ?: $pack->productVariant->sku ?: ('#' . $pack->productVariant->id) }}</div>
                            @endif
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ str_replace('_', ' ', (string) ($pack->productVariant?->pack_type ?? $pack->product?->pack_type ?? 'finished stock')) }}
                                @if($pack->productVariant?->product_weight || $pack->product?->product_weight)
                                    · {{ $fmt($pack->productVariant?->product_weight ?? $pack->product->product_weight, 3) }} kg
                                @endif
                                @if($pack->productVariant?->pieces_per_pack || $pack->product?->pieces_per_pack)
                                    · {{ $fmt($pack->productVariant?->pieces_per_pack ?? $pack->product->pieces_per_pack, 0) }} pcs/pack
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-gray-700 dark:text-gray-300">
                            <div>{{ $displaySourceLot?->lot_code ?: ('Lot #' . ($displaySourceLot?->id ?? $pack->source_inventory_lot_id ?? '—')) }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                {{ $displaySourceLot?->product?->name ?? '—' }}
                                @if($pack->sourceLot && $displaySourceLot && (int) $pack->sourceLot->id !== (int) $displaySourceLot->id)
                                    · Output lot {{ $pack->sourceLot->lot_code ?: ('#' . $pack->sourceLot->id) }}
                                @endif
                                @if($pack->sourcePiece)
                                    <div>Piece: {{ $pack->sourcePiece->label ?: ('Piece ' . $pack->sourcePiece->piece_no) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top text-right text-gray-900 dark:text-gray-50">{{ $fmt($pack->available_pack_quantity ?? $pack->pack_quantity) }}</td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $fmt($pack->pieces_per_pack, 0) }}</td>
                        <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">{{ $fmt($pack->source_quantity_consumed, 3) }}</td>
                        <td class="px-3 py-2 align-top text-center">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ ucfirst($pack->status ?? 'available') }}</span>
                            @if($pack->soldOrder)
                                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                    Order {{ $pack->soldOrder->order_number ?: ('#' . $pack->sold_order_id) }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                            No repacked pack stock yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $packs->links() }}
</div>
@endsection

@extends('layouts.company')

@section('title', 'B2B Order Request')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.b2b.order-requests.index') }}" class="text-[11px] text-gray-500 underline dark:text-gray-400">← Back to queue</a>
            <h1 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $orderRequest->request_number ?? ('Request #' . $orderRequest->id) }}</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Allocate actual inventory pieces for B2B piece/kg requests. This reserves stock and can finalize allocated requests into B2B orders/invoices.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($orderRequest->status === 'pending_allocation')
                <form method="POST" action="{{ route('admin.b2b.order-requests.reviewing', $orderRequest) }}">
                    @csrf
                    <button class="rounded bg-gray-900 px-4 py-2 text-[11px] font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Mark reviewing</button>
                </form>
            @endif

            @if($orderRequest->finalized_order_id)
                <a href="{{ route('admin.orders.show', $orderRequest->finalized_order_id) }}" class="rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-[11px] font-medium text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">View order</a>
            @elseif($orderRequest->status === 'allocated')
                <form method="POST" action="{{ route('admin.b2b.order-requests.finalize', $orderRequest) }}" onsubmit="return confirm('Finalize this allocation into a B2B order and invoice? Reserved pieces will be marked sold, but stock will not be deducted again.');">
                    @csrf
                    <button class="rounded bg-emerald-700 px-4 py-2 text-[11px] font-medium text-white hover:bg-emerald-800">Finalize order/invoice</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
            <div class="font-semibold">Please fix the following:</div>
            <ul class="mt-1 list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($orderRequest->finalized_order_id)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[11px] text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100">
            This request has been finalized into order
            <a href="{{ route('admin.orders.show', $orderRequest->finalized_order_id) }}" class="font-semibold underline">#{{ $orderRequest->finalizedOrder?->order_number ?? $orderRequest->finalized_order_id }}</a>
            @if($orderRequest->finalized_at)
                on {{ $orderRequest->finalized_at->format('d M Y, h:i A') }}
            @endif
            .
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
        <div class="space-y-4">
            @foreach($orderRequest->items as $item)
                @php
                    $availablePieces = $availablePiecesByItem[$item->id] ?? collect();
                    $reservedAllocations = $item->allocations->where('status', 'reserved');
                    $soldAllocations = $item->allocations->where('status', 'sold');
                    $displayAllocations = $reservedAllocations->isNotEmpty() ? $reservedAllocations : $soldAllocations;
                    $releasedAllocations = $item->allocations->where('status', 'released');
                    $soldAllocations = $item->allocations->where('status', 'sold');
                    $allocatedWeight = (float) ($item->allocated_weight_kg ?? $reservedAllocations->sum('weight_kg'));
                    $allocatedPieces = (int) ($item->allocated_piece_count ?? $reservedAllocations->count());
                    $allocatedSubtotal = $item->allocated_subtotal !== null ? (float) $item->allocated_subtotal : null;
                    $targetLabel = $item->request_mode === 'weight'
                        ? ($item->tolerance_range ?? $item->request_summary)
                        : $item->request_summary;
                @endphp

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Product</div>
                            <h2 class="mt-1 text-base font-semibold text-gray-900 dark:text-gray-50">{{ $item->product?->name ?? 'Product removed' }}</h2>
                            @if($item->sellUnit)
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Sellable unit: {{ $item->sellUnit->display_label }}</p>
                            @endif
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $item->request_summary }}</div>
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">{{ $item->request_mode === 'weight' ? 'Weight request' : 'Piece request' }}</div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-4">
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-950/50">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Quoted unit price</div>
                            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $item->quoted_unit_price ? '₹' . number_format((float) $item->quoted_unit_price, 2) . ' / ' . $item->pricing_unit : 'To confirm' }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-950/50">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Target</div>
                            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ $targetLabel }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-950/50">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Status</div>
                            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">{{ ucwords(str_replace('_', ' ', $item->status)) }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-950/50">
                            <div class="text-[10px] uppercase tracking-wide text-gray-400">Allocated</div>
                            <div class="mt-1 font-semibold text-gray-900 dark:text-gray-50">
                                @if($allocatedPieces > 0)
                                    {{ $allocatedPieces }} pcs · {{ rtrim(rtrim(number_format($allocatedWeight, 3), '0'), '.') }} kg
                                @else
                                    Not allocated
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($item->customer_note)
                        <div class="mt-4 rounded-lg border border-gray-200 px-3 py-2 text-[11px] text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            <div class="mb-1 font-semibold text-gray-900 dark:text-gray-50">Customer note</div>
                            {{ $item->customer_note }}
                        </div>
                    @endif

                    @if($displayAllocations->isNotEmpty())
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-semibold text-emerald-900 dark:text-emerald-100">{{ $reservedAllocations->isNotEmpty() ? 'Reserved allocation' : 'Finalized allocation' }}</div>
                                    <div class="mt-1 text-[11px] text-emerald-700 dark:text-emerald-200">
                                        {{ $allocatedPieces }} piece(s), {{ rtrim(rtrim(number_format($allocatedWeight, 3), '0'), '.') }} kg
                                        @if($allocatedSubtotal !== null)
                                            · Estimated value ₹{{ number_format($allocatedSubtotal, 2) }}
                                        @endif
                                    </div>
                                </div>
                                @if($reservedAllocations->isNotEmpty() && ! $orderRequest->finalized_order_id)
                                    <form method="POST" action="{{ route('admin.b2b.order-requests.items.release', [$orderRequest, $item]) }}" onsubmit="return confirm('Release these reserved pieces back to available stock?');">
                                        @csrf
                                        <button class="rounded border border-emerald-700 px-3 py-1.5 text-[11px] font-medium text-emerald-800 hover:bg-emerald-100 dark:border-emerald-400 dark:text-emerald-100 dark:hover:bg-emerald-900/50">Release allocation</button>
                                    </form>
                                @endif
                            </div>

                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-[11px]">
                                    <thead>
                                        <tr class="text-left text-emerald-800 dark:text-emerald-200">
                                            <th class="py-1 pr-3">Piece</th>
                                            <th class="py-1 pr-3">Weight</th>
                                            <th class="py-1 pr-3">Variant</th>
                                            <th class="py-1 pr-3">Lot</th>
                                            <th class="py-1 pr-3">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($displayAllocations as $allocation)
                                            <tr class="border-t border-emerald-200 dark:border-emerald-900/60">
                                                <td class="py-1 pr-3">#{{ $allocation->inventoryPiece?->piece_no ?? $allocation->inventory_piece_id }}</td>
                                                <td class="py-1 pr-3">{{ rtrim(rtrim(number_format((float)$allocation->weight_kg, 3), '0'), '.') }} kg</td>
                                                <td class="py-1 pr-3">{{ $allocation->variant?->sku ?? $allocation->inventoryPiece?->inventoryLot?->productVariant?->sku ?? '—' }}</td>
                                                <td class="py-1 pr-3">{{ $allocation->inventoryPiece?->inventoryLot?->lot_code ?? ('Lot #' . $allocation->inventory_lot_id) }}</td>
                                                <td class="py-1 pr-3">{{ $allocation->line_total !== null ? '₹' . number_format((float)$allocation->line_total, 2) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif($soldAllocations->isNotEmpty())
                        <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-900 dark:bg-sky-950/30">
                            <div class="font-semibold text-sky-900 dark:text-sky-100">Finalized allocation</div>
                            <div class="mt-1 text-[11px] text-sky-700 dark:text-sky-200">
                                {{ $soldAllocations->count() }} piece(s), {{ rtrim(rtrim(number_format((float) $soldAllocations->sum('weight_kg'), 3), '0'), '.') }} kg were finalized into the order/invoice.
                            </div>
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-[11px]">
                                    <thead>
                                        <tr class="text-left text-sky-800 dark:text-sky-200">
                                            <th class="py-1 pr-3">Piece</th>
                                            <th class="py-1 pr-3">Weight</th>
                                            <th class="py-1 pr-3">Variant</th>
                                            <th class="py-1 pr-3">Order item</th>
                                            <th class="py-1 pr-3">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($soldAllocations as $allocation)
                                            <tr class="border-t border-sky-200 dark:border-sky-900/60">
                                                <td class="py-1 pr-3">#{{ $allocation->inventoryPiece?->piece_no ?? $allocation->inventory_piece_id }}</td>
                                                <td class="py-1 pr-3">{{ rtrim(rtrim(number_format((float)$allocation->weight_kg, 3), '0'), '.') }} kg</td>
                                                <td class="py-1 pr-3">{{ $allocation->variant?->sku ?? $allocation->inventoryPiece?->inventoryLot?->productVariant?->sku ?? '—' }}</td>
                                                <td class="py-1 pr-3">#{{ $allocation->sold_order_item_id ?? '—' }}</td>
                                                <td class="py-1 pr-3">{{ $allocation->line_total !== null ? '₹' . number_format((float)$allocation->line_total, 2) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif(in_array($orderRequest->status, ['pending_allocation', 'reviewing', 'partially_allocated'], true))
                        <div class="mt-4 rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-gray-50">Allocate actual pieces</div>
                                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Select inventory pieces internally. Customer does not see these piece/stock details.
                                    </div>
                                </div>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400">Available pieces shown: {{ $availablePieces->count() }}</div>
                            </div>

                            @if($availablePieces->isEmpty())
                                <div class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                                    No available pieces found for this product. Receive stock or release reserved pieces before allocation.
                                </div>
                            @else
                                <form method="POST" action="{{ route('admin.b2b.order-requests.items.allocate', [$orderRequest, $item]) }}" class="mt-3 space-y-3">
                                    @csrf
                                    <div class="max-h-96 overflow-auto rounded-lg border border-gray-200 dark:border-gray-800">
                                        <table class="min-w-full text-[11px]">
                                            <thead class="sticky top-0 bg-gray-50 text-left text-[10px] uppercase tracking-wide text-gray-500 dark:bg-gray-950 dark:text-gray-400">
                                                <tr>
                                                    <th class="px-3 py-2">Select</th>
                                                    <th class="px-3 py-2">Piece</th>
                                                    <th class="px-3 py-2">Weight</th>
                                                    <th class="px-3 py-2">Variant</th>
                                                    <th class="px-3 py-2">Lot</th>
                                                    <th class="px-3 py-2">Expiry</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                @foreach($availablePieces as $piece)
                                                    @php($lot = $piece->inventoryLot)
                                                    <tr>
                                                        <td class="px-3 py-2">
                                                            <input type="checkbox" name="piece_ids[]" value="{{ $piece->id }}" class="rounded border-gray-300 text-gray-900">
                                                        </td>
                                                        <td class="px-3 py-2">#{{ $piece->piece_no }} <span class="text-gray-400">/ {{ $piece->id }}</span></td>
                                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-gray-50">{{ rtrim(rtrim(number_format((float)$piece->weight_kg, 3), '0'), '.') }} kg</td>
                                                        <td class="px-3 py-2">{{ $lot?->productVariant?->sku ?? '—' }}</td>
                                                        <td class="px-3 py-2">{{ $lot?->lot_code ?? ('Lot #' . $piece->inventory_lot_id) }}</td>
                                                        <td class="px-3 py-2">{{ optional($lot?->expiry_date)->format('d M Y') ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if($item->request_mode === 'weight')
                                        <label class="inline-flex items-center gap-2 text-[11px] text-gray-600 dark:text-gray-300">
                                            <input type="checkbox" name="override_tolerance" value="1" class="rounded border-gray-300 text-gray-900">
                                            Override requested weight tolerance. Manager/Admin only.
                                        </label>
                                    @endif

                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Internal allocation note</label>
                                        <textarea name="admin_note" rows="2" class="mt-1 w-full rounded border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950" placeholder="Optional note for allocation/finalization team"></textarea>
                                    </div>

                                    <button class="rounded bg-gray-900 px-4 py-2 text-[11px] font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">
                                        Reserve selected pieces
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if($releasedAllocations->isNotEmpty())
                        <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $releasedAllocations->count() }} previous allocation piece(s) were released.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Customer</div>
                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $orderRequest->user?->name ?? 'Customer removed' }}</div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ $orderRequest->user?->email }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Request status</div>
                <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $orderRequest->status_label }}</div>
                <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Submitted {{ optional($orderRequest->created_at)->format('d M Y, h:i A') }}</div>
                @if($orderRequest->reviewedBy)
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">Reviewed by {{ $orderRequest->reviewedBy->name }}</div>
                @endif
                @if($orderRequest->allocatedBy)
                    <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Allocated by {{ $orderRequest->allocatedBy->name }}</div>
                @endif
            </div>
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-[11px] text-sky-800 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                <div class="font-semibold">Finalization</div>
                <div class="mt-1">
                    Allocated requests can now be converted into an order and invoice. Stock is already reserved at allocation, so finalization only marks those pieces sold and links them to order items.
                </div>
                @if($orderRequest->finalizedOrder)
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('admin.orders.show', $orderRequest->finalizedOrder) }}" class="block font-medium underline">View order {{ $orderRequest->finalizedOrder->order_number }}</a>
                        @if($orderRequest->finalizedInvoice)
                            <div>Invoice: {{ $orderRequest->finalizedInvoice->invoice_number }} · {{ ucfirst($orderRequest->finalizedInvoice->status) }}</div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-[11px] text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                For weight requests, the system enforces the stored tolerance range unless Manager/Admin uses the override checkbox.
            </div>
        </div>
    </div>
</div>
@endsection

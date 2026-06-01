<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BOrderItemAllocation;
use App\Models\B2BOrderRequest;
use App\Models\B2BOrderRequestItem;
use App\Models\InventoryLot;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\B2BOrderRequestFinalizationService;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class B2BOrderRequestController extends Controller
{
    protected static array $columnCache = [];

    public function index(Request $request)
    {
        $status = (string) $request->input('status', 'open');
        $allowed = ['open', 'pending_allocation', 'reviewing', 'partially_allocated', 'allocated', 'finalized', 'cancelled', 'rejected', 'all'];

        if (! in_array($status, $allowed, true)) {
            $status = 'open';
        }

        $query = B2BOrderRequest::query()
            ->with(['user', 'finalizedOrder', 'finalizedInvoice', 'items.product', 'items.sellUnit', 'items.allocations'])
            ->latest();

        if ($status === 'open') {
            $query->open();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('admin.b2b.order-requests.index', [
            'requests' => $query->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(B2BOrderRequest $orderRequest)
    {
        $orderRequest->load([
            'user',
            'items.product.images',
            'items.sellUnit',
            'items.allocations.inventoryPiece.inventoryLot.productVariant',
            'items.allocations.variant',
            'items.allocatedBy',
            'reviewedBy',
            'allocatedBy',
            'finalizedBy',
            'finalizedOrder',
            'finalizedInvoice',
            'invoice',
        ]);

        $availablePiecesByItem = [];
        foreach ($orderRequest->items as $item) {
            $availablePiecesByItem[$item->id] = in_array($item->status, [B2BOrderRequestItem::STATUS_ALLOCATED, B2BOrderRequestItem::STATUS_FINALIZED], true)
                ? collect()
                : $this->availablePiecesForItem($item, 250);
        }

        return view('admin.b2b.order-requests.show', [
            'orderRequest' => $orderRequest,
            'availablePiecesByItem' => $availablePiecesByItem,
        ]);
    }

    public function markReviewing(Request $request, B2BOrderRequest $orderRequest)
    {
        if ($orderRequest->status === B2BOrderRequest::STATUS_PENDING_ALLOCATION) {
            $orderRequest->status = B2BOrderRequest::STATUS_REVIEWING;
            $orderRequest->reviewed_by_id = $request->user()?->id;
            $orderRequest->reviewed_at = now();
            $orderRequest->save();
        }

        return back()->with('status', 'Request marked as reviewing. Stores/Admin can now allocate actual pieces.');
    }

    public function allocateItem(Request $request, B2BOrderRequest $orderRequest, B2BOrderRequestItem $item)
    {
        $this->assertItemBelongsToRequest($orderRequest, $item);

        $data = $request->validate([
            'piece_ids' => ['required', 'array', 'min:1'],
            'piece_ids.*' => ['integer', 'distinct', 'exists:inventory_pieces,id'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'override_tolerance' => ['nullable', 'boolean'],
        ]);

        $pieceIds = collect($data['piece_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        try {
            DB::transaction(function () use ($request, $orderRequest, $item, $pieceIds, $data) {
                $item = B2BOrderRequestItem::query()
                    ->where('id', $item->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $orderRequest = B2BOrderRequest::query()
                    ->where('id', $orderRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertItemBelongsToRequest($orderRequest, $item);

                if ($item->status === B2BOrderRequestItem::STATUS_ALLOCATED) {
                    throw new RuntimeException('This request item is already allocated. Release it before allocating again.');
                }

                if (B2BOrderItemAllocation::query()
                    ->where('b2b_order_request_item_id', $item->id)
                    ->where('status', B2BOrderItemAllocation::STATUS_RESERVED)
                    ->exists()) {
                    throw new RuntimeException('This request item already has reserved allocations. Release them before allocating again.');
                }

                $pieces = InventoryPiece::query()
                    ->with('inventoryLot.productVariant')
                    ->whereIn('id', $pieceIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($pieces->count() !== $pieceIds->count()) {
                    throw new RuntimeException('One or more selected pieces could not be found.');
                }

                $selectedWeight = 0.0;
                $variantCounts = [];

                foreach ($pieceIds as $pieceId) {
                    $piece = $pieces->get($pieceId);
                    $lot = $piece?->inventoryLot;

                    if (! $piece || ! $lot) {
                        throw new RuntimeException('One or more selected pieces has no inventory lot.');
                    }

                    if ((int) $lot->product_id !== (int) $item->product_id) {
                        throw new RuntimeException('One or more selected pieces does not belong to the requested product.');
                    }

                    if (! empty($piece->sold_order_item_id)) {
                        throw new RuntimeException('One or more selected pieces has already been sold.');
                    }

                    $pieceStatus = strtolower((string) ($piece->status ?? 'available'));
                    if ($pieceStatus !== '' && $pieceStatus !== 'available') {
                        throw new RuntimeException('One or more selected pieces is not available.');
                    }

                    $reservedElsewhere = B2BOrderItemAllocation::query()
                        ->where('inventory_piece_id', $piece->id)
                        ->where('status', B2BOrderItemAllocation::STATUS_RESERVED)
                        ->exists();

                    if ($reservedElsewhere) {
                        throw new RuntimeException('One or more selected pieces is already reserved for another B2B request.');
                    }

                    $selectedWeight += round((float) ($piece->weight_kg ?? 0), 3);
                    $variantId = (int) ($lot->product_variant_id ?? 0);
                    if ($variantId > 0) {
                        $variantCounts[$variantId] = ($variantCounts[$variantId] ?? 0) + 1;
                    }
                }

                $selectedCount = $pieceIds->count();
                $selectedWeight = round($selectedWeight, 3);

                $this->validateSelectionAgainstRequest($request, $item, $selectedCount, $selectedWeight, (bool) ($data['override_tolerance'] ?? false));

                $unitPrice = round((float) ($item->quoted_unit_price ?? 0), 2);
                $pricingUnit = strtolower((string) ($item->pricing_unit ?? 'kg'));
                $subtotal = 0.0;

                foreach ($pieceIds as $pieceId) {
                    $piece = $pieces->get($pieceId);
                    $lot = $piece->inventoryLot;
                    $weight = round((float) ($piece->weight_kg ?? 0), 3);
                    $lineTotal = $unitPrice > 0
                        ? ($pricingUnit === 'kg' ? round($weight * $unitPrice, 2) : $unitPrice)
                        : null;

                    $allocation = B2BOrderItemAllocation::query()->create([
                        'b2b_order_request_id' => $orderRequest->id,
                        'b2b_order_request_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $lot->product_variant_id,
                        'inventory_lot_id' => $lot->id,
                        'inventory_piece_id' => $piece->id,
                        'weight_kg' => $weight,
                        'unit_price' => $unitPrice > 0 ? $unitPrice : null,
                        'line_total' => $lineTotal,
                        'status' => B2BOrderItemAllocation::STATUS_RESERVED,
                        'allocated_by_id' => $request->user()?->id,
                        'allocated_at' => now(),
                        'notes' => $data['admin_note'] ?? null,
                    ]);

                    $subtotal += (float) ($lineTotal ?? 0);
                    $this->reserveInventoryPiece($piece, $allocation);
                }

                $this->writeAllocationMovements($item, $pieces, $variantCounts, $request->user()?->id);

                $item->status = B2BOrderRequestItem::STATUS_ALLOCATED;
                $item->allocated_piece_count = $selectedCount;
                $item->allocated_weight_kg = $selectedWeight;
                $item->allocated_subtotal = $subtotal > 0 ? round($subtotal, 2) : null;
                $item->allocated_by_id = $request->user()?->id;
                $item->allocated_at = now();
                $item->admin_note = $data['admin_note'] ?? $item->admin_note;
                $item->save();

                $this->syncRequestStatus($orderRequest, $request->user()?->id);
            }, 3);
        } catch (RuntimeException $e) {
            return back()->withErrors(['allocation' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Pieces allocated and reserved. Final order/invoice creation is intentionally left for the next phase.');
    }

    public function releaseItem(Request $request, B2BOrderRequest $orderRequest, B2BOrderRequestItem $item)
    {
        $this->assertItemBelongsToRequest($orderRequest, $item);

        try {
            DB::transaction(function () use ($request, $orderRequest, $item) {
                $item = B2BOrderRequestItem::query()
                    ->where('id', $item->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $orderRequest = B2BOrderRequest::query()
                    ->where('id', $orderRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $allocations = B2BOrderItemAllocation::query()
                    ->with('inventoryPiece.inventoryLot')
                    ->where('b2b_order_request_item_id', $item->id)
                    ->where('status', B2BOrderItemAllocation::STATUS_RESERVED)
                    ->lockForUpdate()
                    ->get();

                if ($allocations->isEmpty()) {
                    throw new RuntimeException('There are no reserved allocations to release.');
                }

                $variantCounts = [];
                foreach ($allocations as $allocation) {
                    $piece = $allocation->inventoryPiece;
                    if ($piece) {
                        $this->releaseInventoryPiece($piece, $allocation);
                    }

                    $variantId = (int) ($allocation->product_variant_id ?? 0);
                    if ($variantId > 0) {
                        $variantCounts[$variantId] = ($variantCounts[$variantId] ?? 0) + 1;
                    }

                    $allocation->status = B2BOrderItemAllocation::STATUS_RELEASED;
                    $allocation->released_by_id = $request->user()?->id;
                    $allocation->released_at = now();
                    $allocation->save();
                }

                $this->writeReleaseMovements($item, $variantCounts, $request->user()?->id);

                $item->status = B2BOrderRequestItem::STATUS_RELEASED;
                $item->allocated_piece_count = null;
                $item->allocated_weight_kg = null;
                $item->allocated_subtotal = null;
                $item->allocated_by_id = null;
                $item->allocated_at = null;
                $item->save();

                $this->syncRequestStatus($orderRequest, $request->user()?->id);
            }, 3);
        } catch (RuntimeException $e) {
            return back()->withErrors(['allocation' => $e->getMessage()]);
        }

        return back()->with('status', 'Reserved pieces released back to available stock.');
    }


    public function finalize(Request $request, B2BOrderRequest $orderRequest, B2BOrderRequestFinalizationService $finalizer)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($data['admin_note'])) {
            $orderRequest->admin_note = $data['admin_note'];
            $orderRequest->save();
        }

        try {
            $result = $finalizer->finalize($orderRequest, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['finalize' => $e->getMessage()]);
        }

        $invoice = $result['invoice'] ?? null;
        $order = $result['order'] ?? null;

        if ($invoice) {
            try {
                app(InvoicePdfService::class)->generateAndStore($invoice);
            } catch (\Throwable $e) {
                Log::warning('Failed to generate finalized B2B request invoice PDF', [
                    'invoice_id' => $invoice->id,
                    'order_id' => $order?->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = ($result['created'] ?? false)
            ? 'B2B request finalized into order ' . ($order?->order_number ?? '#' . $order?->id) . '.'
            : 'This B2B request was already finalized.';

        return redirect()
            ->route('admin.b2b.order-requests.show', $orderRequest)
            ->with('status', $message);
    }

    protected function validateSelectionAgainstRequest(Request $request, B2BOrderRequestItem $item, int $selectedCount, float $selectedWeight, bool $overrideTolerance): void
    {
        if ($item->request_mode === B2BOrderRequestItem::MODE_PIECES) {
            $required = (int) ($item->requested_piece_count ?? 0);
            if ($required > 0 && $selectedCount !== $required) {
                throw new RuntimeException("Please select exactly {$required} piece(s) for this request. You selected {$selectedCount}.");
            }

            return;
        }

        $target = round((float) ($item->requested_weight_kg ?? 0), 3);
        $tolerance = round((float) ($item->weight_tolerance_kg ?? max($target * 0.05, 0.05)), 3);

        if ($target <= 0) {
            return;
        }

        $min = max(round($target - $tolerance, 3), 0);
        $max = round($target + $tolerance, 3);

        if ($selectedWeight >= $min && $selectedWeight <= $max) {
            return;
        }

        if ($overrideTolerance && $this->canOverrideTolerance($request)) {
            return;
        }

        throw new RuntimeException(
            "Selected pieces total {$selectedWeight} kg, outside the requested range {$min} kg – {$max} kg. Manager/Admin can override if needed."
        );
    }

    protected function canOverrideTolerance(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['Admin', 'Manager']);
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('Admin') || $user->hasRole('Manager');
        }

        return false;
    }

    protected function availablePiecesForItem(B2BOrderRequestItem $item, int $limit = 250)
    {
        return InventoryPiece::query()
            ->select('inventory_pieces.*')
            ->with(['inventoryLot.productVariant'])
            ->join('inventory_lots', 'inventory_lots.id', '=', 'inventory_pieces.inventory_lot_id')
            ->where('inventory_lots.product_id', $item->product_id)
            ->where(function ($query) {
                $query->whereNull('inventory_pieces.status')
                    ->orWhere('inventory_pieces.status', 'available');
            })
            ->whereNull('inventory_pieces.sold_order_item_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('b2b_order_item_allocations')
                    ->whereColumn('b2b_order_item_allocations.inventory_piece_id', 'inventory_pieces.id')
                    ->where('b2b_order_item_allocations.status', B2BOrderItemAllocation::STATUS_RESERVED);
            })
            ->where(function ($query) {
                $query->whereNull('inventory_lots.lot_status')
                    ->orWhereIn('inventory_lots.lot_status', ['available', 'partial']);
            })
            ->where(function ($query) {
                $query->whereNull('inventory_lots.available_piece_count')
                    ->orWhere('inventory_lots.available_piece_count', '>', 0)
                    ->orWhere('inventory_lots.available_weight_kg', '>', 0);
            })
            ->orderByRaw('CASE WHEN inventory_lots.expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('inventory_lots.expiry_date')
            ->orderBy('inventory_pieces.weight_kg')
            ->orderBy('inventory_pieces.id')
            ->limit($limit)
            ->get();
    }

    protected function reserveInventoryPiece(InventoryPiece $piece, B2BOrderItemAllocation $allocation): void
    {
        $pieceWeight = round((float) ($piece->weight_kg ?? 0), 3);
        $piece->status = 'reserved';
        $piece->save();

        $lot = InventoryLot::query()->lockForUpdate()->find($allocation->inventory_lot_id);
        if ($lot) {
            $this->adjustLotAvailability($lot, -1, -1 * $pieceWeight);
        }

        $this->adjustStockTarget((int) $allocation->product_id, (int) ($allocation->product_variant_id ?? 0), -1);
    }

    protected function releaseInventoryPiece(InventoryPiece $piece, B2BOrderItemAllocation $allocation): void
    {
        $pieceWeight = round((float) ($piece->weight_kg ?? 0), 3);

        if (empty($piece->sold_order_item_id)) {
            $piece->status = 'available';
            $piece->save();
        }

        $lot = InventoryLot::query()->lockForUpdate()->find($allocation->inventory_lot_id);
        if ($lot) {
            $this->adjustLotAvailability($lot, 1, $pieceWeight);
        }

        $this->adjustStockTarget((int) $allocation->product_id, (int) ($allocation->product_variant_id ?? 0), 1);
    }

    protected function adjustLotAvailability(InventoryLot $lot, int $pieceDelta, float $weightDelta): void
    {
        if ($this->hasColumn($lot->getTable(), 'available_piece_count')) {
            $lot->available_piece_count = max(0, (int) ($lot->available_piece_count ?? 0) + $pieceDelta);
        }

        if ($this->hasColumn($lot->getTable(), 'available_quantity')) {
            $lot->available_quantity = round(max(0, (float) ($lot->available_quantity ?? 0) + $pieceDelta), 3);
        }

        if ($this->hasColumn($lot->getTable(), 'available_weight_kg')) {
            $lot->available_weight_kg = round(max(0, (float) ($lot->available_weight_kg ?? 0) + $weightDelta), 3);
        }

        if ($this->hasColumn($lot->getTable(), 'lot_status')) {
            $hasAvailability = ((float) ($lot->available_weight_kg ?? 0) > 0)
                || ((int) ($lot->available_piece_count ?? 0) > 0)
                || ((float) ($lot->available_quantity ?? 0) > 0);

            $lot->lot_status = $hasAvailability ? 'available' : 'reserved';
        }

        $lot->save();
    }

    protected function adjustStockTarget(int $productId, int $variantId, int $delta): void
    {
        if ($variantId > 0) {
            $variant = ProductVariant::query()->lockForUpdate()->find($variantId);
            if (! $variant) {
                throw new RuntimeException('Selected piece variant was not found.');
            }

            if ($this->hasColumn($variant->getTable(), 'stock_quantity')) {
                $current = round((float) ($variant->stock_quantity ?? 0), 3);
                $next = round($current + $delta, 3);

                if ($next < -0.0005) {
                    throw new RuntimeException("Insufficient stock on variant {$variant->sku} for allocation.");
                }

                $variant->stock_quantity = max(0, $next);
                $variant->save();
                $this->syncParentProductStockFromVariants((int) $variant->product_id);
            }

            return;
        }

        $product = Product::query()->lockForUpdate()->find($productId);
        if ($product && $this->hasColumn($product->getTable(), 'stock_quantity')) {
            $current = round((float) ($product->stock_quantity ?? 0), 3);
            $next = round($current + $delta, 3);
            if ($next < -0.0005) {
                throw new RuntimeException('Insufficient product stock for allocation.');
            }
            $product->stock_quantity = max(0, $next);
            $product->save();
        }
    }

    protected function syncParentProductStockFromVariants(int $productId): void
    {
        $product = Product::query()->lockForUpdate()->find($productId);

        if (! $product || ! $this->hasColumn($product->getTable(), 'stock_quantity')) {
            return;
        }

        $product->stock_quantity = round((float) ProductVariant::query()
            ->where('product_id', $productId)
            ->sum('stock_quantity'), 3);

        $product->save();
    }

    protected function writeAllocationMovements(B2BOrderRequestItem $item, $pieces, array $variantCounts, ?int $userId): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        foreach ($variantCounts ?: [0 => $pieces->count()] as $variantId => $qty) {
            $attributes = [
                'product_id' => $item->product_id,
                'product_variant_id' => $variantId ?: null,
                'movement_type' => 'adjustment',
                'reference_type' => 'b2b_order_request_item',
                'reference_id' => $item->id,
            ];

            $values = [
                'vendor_id' => null,
                'quantity' => -1 * abs((float) $qty),
                'cost_price' => null,
                'notes' => 'B2B request piece allocation reserved by user ' . ($userId ?: 'system') . '.',
                'created_at' => now(),
            ];

            if ($this->hasColumn('stock_movements', 'product_sell_unit_id')) {
                $values['product_sell_unit_id'] = $item->product_sell_unit_id;
            }

            StockMovement::query()->create($attributes + $values);
        }
    }

    protected function writeReleaseMovements(B2BOrderRequestItem $item, array $variantCounts, ?int $userId): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        foreach ($variantCounts ?: [0 => 0] as $variantId => $qty) {
            if ((float) $qty <= 0) {
                continue;
            }

            $values = [
                'product_id' => $item->product_id,
                'product_variant_id' => $variantId ?: null,
                'movement_type' => 'adjustment',
                'reference_type' => 'b2b_order_request_item_release',
                'reference_id' => $item->id,
                'vendor_id' => null,
                'quantity' => abs((float) $qty),
                'cost_price' => null,
                'notes' => 'B2B request piece allocation released by user ' . ($userId ?: 'system') . '.',
                'created_at' => now(),
            ];

            if ($this->hasColumn('stock_movements', 'product_sell_unit_id')) {
                $values['product_sell_unit_id'] = $item->product_sell_unit_id;
            }

            StockMovement::query()->create($values);
        }
    }

    protected function syncRequestStatus(B2BOrderRequest $orderRequest, ?int $userId): void
    {
        $items = B2BOrderRequestItem::query()
            ->where('b2b_order_request_id', $orderRequest->id)
            ->get();

        $allocatedCount = $items->where('status', B2BOrderRequestItem::STATUS_ALLOCATED)->count();

        if ($allocatedCount === 0) {
            $orderRequest->status = B2BOrderRequest::STATUS_REVIEWING;
            $orderRequest->allocated_by_id = null;
            $orderRequest->allocated_at = null;
        } elseif ($allocatedCount === $items->count()) {
            $orderRequest->status = B2BOrderRequest::STATUS_ALLOCATED;
            $orderRequest->allocated_by_id = $userId;
            $orderRequest->allocated_at = now();
        } else {
            $orderRequest->status = B2BOrderRequest::STATUS_PARTIALLY_ALLOCATED;
            $orderRequest->allocated_by_id = $userId;
            $orderRequest->allocated_at = now();
        }

        if (! $orderRequest->reviewed_at) {
            $orderRequest->reviewed_by_id = $userId;
            $orderRequest->reviewed_at = now();
        }

        $orderRequest->save();
    }


    protected function assertItemBelongsToRequest(B2BOrderRequest $orderRequest, B2BOrderRequestItem $item): void
    {
        if ((int) $item->b2b_order_request_id !== (int) $orderRequest->id) {
            abort(404);
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $cacheKey = "{$table}.{$column}";

        if (! array_key_exists($cacheKey, self::$columnCache)) {
            self::$columnCache[$cacheKey] = Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$cacheKey];
    }
}

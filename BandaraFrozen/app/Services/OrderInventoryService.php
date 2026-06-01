<?php

namespace App\Services;

use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class OrderInventoryService
{
    protected static array $columnCache = [];

    /**
     * Commit stock only after payment is successfully verified.
     *
     * Idempotent:
     * - standard products: guarded by stock_movements(reference_type=order_item)
     * - production/slab items: guarded by inventory_pieces.sold_order_item_id
     */
    public function commitPaidOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $items = OrderItem::query()
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                if ($this->isPendingB2BWeightItem($item)) {
                    continue;
                }

                if ($this->alreadyCommitted($item)) {
                    continue;
                }

                if ($this->hasInventoryPacksForOrderItem($item)) {
                    $this->commitStandardProductItem($item);
                } elseif ($this->selectedPieceIdForOrderItem($item) !== null) {
                    $this->commitSelectedPieceItem($item);
                } elseif ($this->isProductionManagedItem($item)) {
                    $this->commitProductionManagedItem($item);
                } else {
                    $this->commitStandardProductItem($item);
                }
            }
        }, 3);
    }

    protected function isPendingB2BWeightItem(OrderItem $item): bool
    {
        $mode = strtolower((string) ($item->b2b_order_mode ?? ''));
        if (! in_array($mode, ['pieces', 'weight'], true)) {
            return false;
        }

        return round((float) ($item->actual_weight_kg ?? 0), 3) <= 0;
    }

    protected function alreadyCommitted(OrderItem $item): bool
    {
        $hasMovement = StockMovement::query()
            ->where('movement_type', 'sale')
            ->where('reference_type', 'order_item')
            ->where('reference_id', $item->id)
            ->exists();

        if ($hasMovement) {
            return true;
        }

        return InventoryPiece::query()
            ->where('sold_order_item_id', $item->id)
            ->exists();
    }


    protected function hasInventoryPacksForOrderItem(OrderItem $item): bool
    {
        $variantId = (int) ($item->product_variant_id ?? 0);
        $sellUnitId = $this->sellUnitIdForOrderItem($item);

        if ($variantId <= 0 || ! $sellUnitId || ! Schema::hasTable('inventory_packs')) {
            return false;
        }

        return InventoryPack::query()
            ->where('product_id', $item->product_id)
            ->where('product_variant_id', $variantId)
            ->where('product_sell_unit_id', $sellUnitId)
            ->exists();
    }

    /**
     * A product is treated as production-managed if a saleable inventory lot
     * exists for it that came from a production run.
     */
    protected function isProductionManagedItem(OrderItem $item): bool
    {
        return InventoryLot::query()
            ->where('product_id', $item->product_id)
            ->when($item->product_variant_id, function ($q) use ($item) {
                $q->where('product_variant_id', $item->product_variant_id);
            })
            ->whereNotNull('production_run_id')
            ->where('is_saleable', true)
            ->exists();
    }

    protected function selectedPieceIdForOrderItem(OrderItem $item): ?int
    {
        $snapshot = is_array($item->attributes_snapshot ?? null) ? $item->attributes_snapshot : [];
        $selectedPiece = is_array($snapshot['selected_piece'] ?? null) ? $snapshot['selected_piece'] : [];
        $pieceId = (int) ($selectedPiece['piece_id'] ?? 0);

        return $pieceId > 0 ? $pieceId : null;
    }

    protected function commitSelectedPieceItem(OrderItem $item): void
    {
        $pieceId = $this->selectedPieceIdForOrderItem($item);

        if (! $pieceId) {
            $this->commitStandardProductItem($item);
            return;
        }

        $piece = InventoryPiece::query()
            ->with('inventoryLot')
            ->lockForUpdate()
            ->find($pieceId);

        if (! $piece) {
            throw new RuntimeException("Selected inventory piece {$pieceId} was not found for order item {$item->id}.");
        }

        if (! empty($piece->sold_order_item_id) && (int) $piece->sold_order_item_id !== (int) $item->id) {
            throw new RuntimeException("Selected inventory piece {$pieceId} has already been sold.");
        }

        $pieceStatus = strtolower((string) ($piece->status ?? 'available'));
        if (! in_array($pieceStatus, ['', 'available'], true)) {
            throw new RuntimeException("Selected inventory piece {$pieceId} is currently {$pieceStatus} and cannot be sold.");
        }

        $lot = $piece->inventoryLot;
        $lotVariantId = (int) ($lot?->product_variant_id ?? 0);

        if ($lotVariantId > 0 && empty($item->product_variant_id)) {
            $item->product_variant_id = $lotVariantId;
            $item->save();
        }

        $movementQty = max(1.0, round((float) ($item->quantity ?? 1), 3));

        $this->deductStockTarget($item, $movementQty);
        $this->markPieceSold($piece, $item);

        $this->recordSaleMovement(
            $item,
            $movementQty,
            'Selected inventory piece sold after successful payment.'
        );
    }

    /**
     * Standard products / variants deduct from stock_quantity.
     */
    protected function commitStandardProductItem(OrderItem $item): void
    {
        $qtyToDeduct = round((float) ($item->quantity ?? 0), 3);

        if ($qtyToDeduct <= 0) {
            return;
        }

        $this->deductStockTarget($item, $qtyToDeduct);

        $packConsumption = $this->consumeInventoryPacksForSale($item, $qtyToDeduct);
        $notes = 'Standard product stock deducted after successful payment.';

        if (($packConsumption['consumed'] ?? 0) > 0) {
            $notes .= ' Repacked inventory pack stock consumed: ' . $packConsumption['consumed'] . ' pack(s).';
        }

        $this->recordSaleMovement(
            $item,
            $qtyToDeduct,
            $notes
        );
    }

    protected function deductStockTarget(OrderItem $item, float $qtyToDeduct): void
    {
        $target = $this->resolveStandardStockTarget($item);

        if (! $target) {
            return;
        }

        if (! $this->hasColumn($target->getTable(), 'stock_quantity')) {
            return;
        }

        $fresh = $target->newQuery()
            ->lockForUpdate()
            ->find($target->getKey());

        if (! $fresh) {
            throw new RuntimeException("Stock target not found for order item {$item->id}.");
        }

        $available = round((float) ($fresh->stock_quantity ?? 0), 3);

        if ($available < $qtyToDeduct) {
            throw new RuntimeException(
                "Insufficient stock for order item {$item->id}. Available {$available}, required {$qtyToDeduct}."
            );
        }

        $fresh->stock_quantity = round($available - $qtyToDeduct, 3);
        $fresh->save();

        if ($fresh instanceof ProductVariant) {
            $this->syncParentProductStockFromVariants((int) $fresh->product_id);
        }
    }


    protected function consumeInventoryPacksForSale(OrderItem $item, float $qtyToDeduct): array
    {
        $variantId = (int) ($item->product_variant_id ?? 0);
        $sellUnitId = $this->sellUnitIdForOrderItem($item);

        if ($variantId <= 0 || ! $sellUnitId || ! Schema::hasTable('inventory_packs')) {
            return ['consumed' => 0, 'pack_ids' => []];
        }

        $baseQuery = InventoryPack::query()
            ->where('product_id', $item->product_id)
            ->where('product_variant_id', $variantId)
            ->where('product_sell_unit_id', $sellUnitId);

        // Do not block legacy stock that predates the repack layer. Once pack
        // rows exist for this product/variant/unit, sales must consume them too.
        if (! (clone $baseQuery)->exists()) {
            return ['consumed' => 0, 'pack_ids' => []];
        }

        $packsRequired = max(1, (int) ceil($qtyToDeduct - 0.000001));

        $availableQuery = (clone $baseQuery)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'available');
            })
            ->where(function ($query) {
                $query->whereNull('available_pack_quantity')
                    ->orWhere('available_pack_quantity', '>', 0);
            });

        $availablePacks = round((float) (clone $availableQuery)
            ->selectRaw('SUM(COALESCE(available_pack_quantity, pack_quantity, 1)) as available_packs')
            ->value('available_packs'), 3);

        if ($availablePacks + 0.0005 < $packsRequired) {
            throw new RuntimeException(
                "Insufficient repacked pack stock for order item {$item->id}. Available {$availablePacks}, required {$packsRequired}."
            );
        }

        $remaining = (float) $packsRequired;
        $consumed = 0.0;
        $packIds = [];

        $packs = $availableQuery
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('packed_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($packs as $pack) {
            if ($remaining <= 0.0005) {
                break;
            }

            $available = round((float) ($pack->available_pack_quantity ?? $pack->pack_quantity ?? 1), 3);
            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);
            $newAvailable = round(max(0, $available - $take), 3);

            $pack->available_pack_quantity = $newAvailable;

            $piecesPerPack = round((float) ($pack->pieces_per_pack ?? 0), 3);
            if ($piecesPerPack > 0 && $this->hasColumn('inventory_packs', 'available_pieces')) {
                $pack->available_pieces = round(
                    max(0, (float) ($pack->available_pieces ?? ($available * $piecesPerPack)) - ($take * $piecesPerPack)),
                    3
                );
            }

            if ($newAvailable <= 0.0005) {
                $pack->status = 'sold';

                if ($this->hasColumn('inventory_packs', 'sold_order_id')) {
                    $pack->sold_order_id = $item->order_id;
                }
                if ($this->hasColumn('inventory_packs', 'sold_order_item_id')) {
                    $pack->sold_order_item_id = $item->id;
                }
                if ($this->hasColumn('inventory_packs', 'sold_at')) {
                    $pack->sold_at = now();
                }
            }

            $pack->save();

            $remaining = round($remaining - $take, 3);
            $consumed = round($consumed + $take, 3);
            $packIds[] = (int) $pack->id;
        }

        if ($remaining > 0.0005) {
            throw new RuntimeException(
                "Unable to consume enough repacked pack stock for order item {$item->id}. Remaining {$remaining}."
            );
        }

        return [
            'consumed' => $consumed,
            'pack_ids' => $packIds,
            'product_sell_unit_id' => $sellUnitId,
        ];
    }

    protected function sellUnitIdForOrderItem(OrderItem $item): ?int
    {
        if ($this->hasColumn('order_items', 'product_sell_unit_id') && ! empty($item->product_sell_unit_id)) {
            return (int) $item->product_sell_unit_id;
        }

        if (! empty($item->product_variant_id)) {
            $variant = ProductVariant::query()->find($item->product_variant_id);
            $sellUnitId = (int) ($variant?->product_sell_unit_id ?? 0);

            if ($sellUnitId > 0) {
                return $sellUnitId;
            }
        }

        return null;
    }

    protected function resolveStandardStockTarget(OrderItem $item)
    {
        if (! empty($item->product_variant_id)) {
            $variant = ProductVariant::query()->find($item->product_variant_id);
            if ($variant) {
                return $variant;
            }
        }

        if (! empty($item->product_id)) {
            return Product::query()->find($item->product_id);
        }

        return null;
    }

    /**
     * Production/slab path:
     * - prefer selling available pieces if they exist
     * - otherwise deduct by weight from inventory lots
     */
    protected function commitProductionManagedItem(OrderItem $item): void
    {
        $pieceTarget = max(1, (int) ceil((float) ($item->quantity ?? 1)));
        $weightTarget = round((float) ($item->item_weight ?? 0), 3);

        if ($pieceTarget === 1 && $this->trySellSinglePiece($item, $weightTarget)) {
            return;
        }

        if ($pieceTarget > 1 && $this->trySellMultiplePieces($item, $pieceTarget, $weightTarget)) {
            return;
        }

        $this->deductFromProductionLotsByWeight($item, $weightTarget, $pieceTarget);
    }

    protected function trySellSinglePiece(OrderItem $item, float $targetWeight): bool
    {
        $query = $this->availableProductionPieceQuery($item);

        if (! (clone $query)->exists()) {
            return false;
        }

        $piece = $query
            ->when($targetWeight > 0, function ($q) use ($targetWeight) {
                $q->orderByRaw('ABS(weight_kg - ?) asc', [$targetWeight]);
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (! $piece) {
            return false;
        }

        $soldWeight = $this->markPieceSold($piece, $item);

        $this->recordSaleMovement(
            $item,
            $soldWeight > 0 ? $soldWeight : 1,
            'Production slab piece sold.'
        );

        return true;
    }

    protected function trySellMultiplePieces(OrderItem $item, int $pieceTarget, float $weightTarget): bool
    {
        $query = $this->availableProductionPieceQuery($item);

        if ((clone $query)->count() < $pieceTarget) {
            return false;
        }

        $pieces = $query
            ->orderBy('inventory_lot_id')
            ->orderBy('piece_no')
            ->orderBy('id')
            ->lockForUpdate()
            ->limit($pieceTarget)
            ->get();

        if ($pieces->count() < $pieceTarget) {
            return false;
        }

        $soldWeight = 0.0;

        foreach ($pieces as $piece) {
            $soldWeight += $this->markPieceSold($piece, $item);
        }

        $movementQty = $weightTarget > 0 ? $weightTarget : $soldWeight;

        $this->recordSaleMovement(
            $item,
            $movementQty > 0 ? $movementQty : $pieceTarget,
            'Production slab pieces sold.'
        );

        return true;
    }

    protected function markPieceSold(InventoryPiece $piece, OrderItem $item): float
    {
        $pieceWeight = round((float) ($piece->weight_kg ?? 0), 3);

        $piece->sold_order_item_id = $item->id;
        $piece->status = 'sold';
        $piece->save();

        $lot = InventoryLot::query()
            ->lockForUpdate()
            ->find($piece->inventory_lot_id);

        if ($lot) {
            if ($this->hasColumn($lot->getTable(), 'available_weight_kg')) {
                $lot->available_weight_kg = round(
                    max(0, (float) ($lot->available_weight_kg ?? 0) - $pieceWeight),
                    3
                );
            }

            if ($this->hasColumn($lot->getTable(), 'available_piece_count')) {
                $lot->available_piece_count = max(
                    0,
                    (int) ($lot->available_piece_count ?? 0) - 1
                );
            }

            if ($this->hasColumn($lot->getTable(), 'available_quantity')) {
                $lot->available_quantity = round(
                    max(0, (float) ($lot->available_quantity ?? 0) - 1),
                    3
                );
            }

            if ($this->hasColumn($lot->getTable(), 'lot_status')
                && (float) ($lot->available_weight_kg ?? 0) <= 0
            ) {
                $lot->lot_status = 'sold';
            }

            $lot->save();
        }

        return $pieceWeight;
    }

    protected function deductFromProductionLotsByWeight(OrderItem $item, float $weightTarget, int $pieceTarget): void
    {
        if ($weightTarget <= 0) {
            throw new RuntimeException(
                "Order item {$item->id} is production-managed but has no valid item_weight."
            );
        }

        $remainingWeight = $weightTarget;
        $remainingPieces = $pieceTarget;

        $lots = $this->availableProductionLotQuery($item)
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            $availableWeight = round((float) ($lot->available_weight_kg ?? 0), 3);

            if ($availableWeight <= 0) {
                continue;
            }

            $deductWeight = min($availableWeight, $remainingWeight);

            $lot->available_weight_kg = round(
                max(0, $availableWeight - $deductWeight),
                3
            );

            if ($this->hasColumn($lot->getTable(), 'available_piece_count') && $remainingPieces > 0) {
                $currentPieces = (int) ($lot->available_piece_count ?? 0);
                $deductPieces = min($currentPieces, $remainingPieces);
                $lot->available_piece_count = max(0, $currentPieces - $deductPieces);
                $remainingPieces -= $deductPieces;
            }

            if ($this->hasColumn($lot->getTable(), 'available_quantity') && $remainingPieces > 0) {
                $currentQty = round((float) ($lot->available_quantity ?? 0), 3);
                $deductQty = min($currentQty, (float) $remainingPieces);
                $lot->available_quantity = round(max(0, $currentQty - $deductQty), 3);
            }

            if ($this->hasColumn($lot->getTable(), 'lot_status')
                && (float) ($lot->available_weight_kg ?? 0) <= 0
            ) {
                $lot->lot_status = 'sold';
            }

            $lot->save();

            $remainingWeight = round($remainingWeight - $deductWeight, 3);

            if ($remainingWeight <= 0) {
                break;
            }
        }

        if ($remainingWeight > 0) {
            throw new RuntimeException(
                "Insufficient production stock for order item {$item->id}. Remaining weight: {$remainingWeight} kg."
            );
        }

        $this->recordSaleMovement(
            $item,
            $weightTarget,
            'Production lot weight deducted after successful payment.'
        );
    }

    protected function availableProductionPieceQuery(OrderItem $item)
    {
        return InventoryPiece::query()
            ->whereNull('sold_order_item_id')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['sold', 'consumed', 'reserved']);
            })
            ->whereHas('inventoryLot', function ($q) use ($item) {
                $q->where('product_id', $item->product_id)
                    ->when($item->product_variant_id, function ($sub) use ($item) {
                        $sub->where('product_variant_id', $item->product_variant_id);
                    })
                    ->whereNotNull('production_run_id')
                    ->where('is_saleable', true)
                    ->where(function ($sub) {
                        $sub->where('available_piece_count', '>', 0)
                            ->orWhere('available_weight_kg', '>', 0);
                    });
            });
    }

    protected function availableProductionLotQuery(OrderItem $item)
    {
        return InventoryLot::query()
            ->where('product_id', $item->product_id)
            ->when($item->product_variant_id, function ($q) use ($item) {
                $q->where('product_variant_id', $item->product_variant_id);
            })
            ->whereNotNull('production_run_id')
            ->where('is_saleable', true)
            ->where(function ($q) {
                $q->where('available_weight_kg', '>', 0)
                    ->orWhere('available_piece_count', '>', 0)
                    ->orWhere('available_quantity', '>', 0);
            })
            ->orderByRaw('COALESCE(packed_date, received_date, created_at) asc')
            ->orderBy('id');
    }

    protected function syncParentProductStockFromVariants(int $productId): void
    {
        $product = Product::query()
            ->lockForUpdate()
            ->find($productId);

        if (! $product || ! $this->hasColumn($product->getTable(), 'stock_quantity')) {
            return;
        }

        $product->stock_quantity = round((float) ProductVariant::query()
            ->where('product_id', $productId)
            ->sum('stock_quantity'), 3);

        $product->save();
    }

    protected function recordSaleMovement(OrderItem $item, float $movementQty, string $notes): void
    {
        $attributes = [
            'product_id'         => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'movement_type'      => 'sale',
            'reference_type'     => 'order_item',
            'reference_id'       => $item->id,
        ];

        $values = [
            'vendor_id'   => null,
            'quantity'    => -1 * abs(round($movementQty, 2)),
            'cost_price'  => null,
            'notes'       => "Order {$item->order_id} / order item {$item->id}: {$notes}",
            'created_at'  => now(),
        ];

        if ($this->hasColumn('stock_movements', 'product_sell_unit_id')) {
            $values['product_sell_unit_id'] = $this->sellUnitIdForOrderItem($item);
        }

        StockMovement::query()->firstOrCreate($attributes, $values);
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
<?php

namespace App\Services;

use App\Models\InventoryPiece;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StockReservationService
{
    public function holdMinutes(): int
    {
        $minutes = (int) config('store.stock_reservation_ttl_minutes', 5);

        return max(1, min($minutes, 30));
    }

    public function holdSeconds(): int
    {
        return $this->holdMinutes() * 60;
    }

    public function enabled(): bool
    {
        return Schema::hasTable('stock_reservations');
    }

    public function releaseExpired(?Carbon $now = null): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $now = $now ?: now();

        $expiredOrderIds = StockReservation::query()
            ->where('status', 'reserved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $count = StockReservation::query()
            ->where('status', 'reserved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update([
                'status' => 'expired',
                'released_at' => $now,
                'release_reason' => 'expired',
                'updated_at' => $now,
            ]);

        if ($count > 0 && ! empty($expiredOrderIds)) {
            $this->markOrdersExpiredAfterReservationTimeout($expiredOrderIds, $now);
        }

        return $count;
    }

    /**
     * Reserve stock for a pending online-payment order.
     *
     * Cart items do not reserve stock. This method is called only when the
     * customer places the order / starts online payment. The reservation is
     * intentionally short-lived to avoid blocking stock for abandoned carts.
     */
    public function reserveOrder(Order $order, ?int $minutes = null): array
    {
        if (! $this->enabled()) {
            return ['reserved' => 0, 'expires_at' => null, 'reservable' => 0];
        }

        $minutes = $minutes ? max(1, min($minutes, 30)) : $this->holdMinutes();
        $expiresAt = now()->addMinutes($minutes);
        $reserved = 0;
        $reservable = 0;

        DB::transaction(function () use ($order, $expiresAt, &$reserved, &$reservable) {
            $this->releaseExpired();

            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->releaseForOrder($lockedOrder, 'reservation_refreshed');

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $result = $this->reserveOrderItem($lockedOrder, $item, $expiresAt);
                $reservable += (int) ($result['reservable'] ?? 0);
                $reserved += (int) ($result['reserved'] ?? 0);
            }
        }, 3);

        return [
            'reserved' => $reserved,
            'reservable' => $reservable,
            'expires_at' => $reserved > 0 ? $expiresAt : null,
        ];
    }

    public function ensureOrderReservedForPayment(Order $order): array
    {
        if (! $this->enabled()) {
            return ['ok' => true, 'expires_at' => null, 'message' => null, 'reserved' => 0, 'reservable' => 0];
        }

        $this->releaseExpired();

        $summary = $this->reservationSummaryForOrder($order);

        if (($summary['reservable'] ?? 0) <= 0) {
            return ['ok' => true, 'expires_at' => null, 'message' => null, 'reserved' => 0, 'reservable' => 0];
        }

        if (($summary['active'] ?? 0) > 0) {
            return [
                'ok' => true,
                'expires_at' => $summary['expires_at'],
                'message' => null,
                'reserved' => $summary['active'],
                'reservable' => $summary['reservable'],
            ];
        }

        try {
            $reserved = $this->reserveOrder($order);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'expires_at' => null,
                'message' => $e->getMessage(),
                'reserved' => 0,
                'reservable' => $summary['reservable'],
            ];
        }

        return [
            'ok' => true,
            'expires_at' => $reserved['expires_at'] ?? null,
            'message' => null,
            'reserved' => $reserved['reserved'] ?? 0,
            'reservable' => $reserved['reservable'] ?? 0,
        ];
    }

    public function assertOrderStillReservedForPayment(Order $order): array
    {
        if (! $this->enabled()) {
            return ['ok' => true, 'message' => null];
        }

        $this->releaseExpired();

        $summary = $this->reservationSummaryForOrder($order);

        if (($summary['reservable'] ?? 0) <= 0) {
            return ['ok' => true, 'message' => null];
        }

        if (($summary['active'] ?? 0) > 0) {
            return ['ok' => true, 'message' => null, 'expires_at' => $summary['expires_at']];
        }

        return [
            'ok' => false,
            'message' => 'The stock hold for this order has expired. Please place the order again so we can confirm current availability before payment.',
        ];
    }

    public function releaseForOrder(Order $order, string $reason = 'released'): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return StockReservation::query()
            ->where('order_id', $order->id)
            ->where('status', 'reserved')
            ->update([
                'status' => $reason === 'expired' ? 'expired' : 'released',
                'released_at' => now(),
                'release_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function markCommittedForOrder(Order $order): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return StockReservation::query()
            ->where('order_id', $order->id)
            ->where('status', 'reserved')
            ->update([
                'status' => 'committed',
                'committed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function reservationSummaryForOrder(Order $order): array
    {
        if (! $this->enabled()) {
            return ['reservable' => 0, 'active' => 0, 'expires_at' => null];
        }

        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->get();

        $reservable = 0;
        foreach ($items as $item) {
            if ($this->orderItemNeedsReservation($item)) {
                $reservable++;
            }
        }

        $activeQuery = StockReservation::query()
            ->where('order_id', $order->id)
            ->where('status', 'reserved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        return [
            'reservable' => $reservable,
            'active' => (int) (clone $activeQuery)->count(),
            'expires_at' => (clone $activeQuery)->min('expires_at'),
        ];
    }

    protected function reserveOrderItem(Order $order, OrderItem $item, Carbon $expiresAt): array
    {
        $product = Product::query()->find($item->product_id);
        if (! $product) {
            throw new RuntimeException('One of the products in your order is no longer available.');
        }

        $variant = null;
        if ($item->product_variant_id) {
            $variant = ProductVariant::query()
                ->where('id', $item->product_variant_id)
                ->where('product_id', $product->id)
                ->first();
        }

        $selectedPieceId = $this->selectedPieceIdForOrderItem($item);
        $qtyToReserve = $this->stockQuantityForItem($item, $product, $variant, $selectedPieceId);

        if ($selectedPieceId > 0) {
            $this->reserveSelectedPiece($order, $item, $product, $variant, $selectedPieceId, $qtyToReserve, $expiresAt);
            return ['reservable' => 1, 'reserved' => 1];
        }

        if (! $this->stockTargetNeedsReservation($product, $variant)) {
            return ['reservable' => 0, 'reserved' => 0];
        }

        $this->reserveStockTarget($order, $item, $product, $variant, $qtyToReserve, $expiresAt);

        return ['reservable' => 1, 'reserved' => 1];
    }

    protected function reserveStockTarget(Order $order, OrderItem $item, Product $product, ?ProductVariant $variant, float $qtyToReserve, Carbon $expiresAt): void
    {
        $qtyToReserve = round(max($qtyToReserve, 0), 3);
        if ($qtyToReserve <= 0) {
            return;
        }

        $target = $variant ?: $product;
        $fresh = $target->newQuery()
            ->lockForUpdate()
            ->find($target->getKey());

        if (! $fresh) {
            throw new RuntimeException('Stock target was not found while reserving this order.');
        }

        $availablePhysical = round((float) ($fresh->stock_quantity ?? 0), 3);
        $activeReserved = $this->activeReservedQuantityForTarget(
            (int) $product->id,
            $variant?->id ? (int) $variant->id : null,
            (int) $order->id
        );
        $availableForCheckout = round(max(0, $availablePhysical - $activeReserved), 3);

        if ($availableForCheckout + 1e-9 < $qtyToReserve) {
            $label = $variant?->name ?: $product->name;
            throw new RuntimeException("Only {$availableForCheckout} available for {$label}. Please update your cart and try again.");
        }

        StockReservation::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'user_id' => $order->user_id,
            'session_id' => session()->getId(),
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $qtyToReserve,
            'weight_kg' => $item->item_weight !== null ? round((float) $item->item_weight, 3) : null,
            'status' => 'reserved',
            'expires_at' => $expiresAt,
            'meta' => [
                'type' => 'stock_target',
                'order_number' => $order->order_number,
                'product_name' => $product->name,
                'variant_name' => $variant?->name,
            ],
        ]);
    }

    protected function reserveSelectedPiece(Order $order, OrderItem $item, Product $product, ?ProductVariant $variant, int $pieceId, float $qtyToReserve, Carbon $expiresAt): void
    {
        $piece = InventoryPiece::query()
            ->with('inventoryLot')
            ->lockForUpdate()
            ->find($pieceId);

        if (! $piece) {
            throw new RuntimeException('The selected slab/piece is no longer available.');
        }

        $pieceStatus = strtolower((string) ($piece->status ?? 'available'));
        if (! in_array($pieceStatus, ['', 'available'], true) || ! empty($piece->sold_order_item_id)) {
            throw new RuntimeException('The selected slab/piece has already been sold. Please select another piece.');
        }

        $activePieceReservation = StockReservation::query()
            ->where('inventory_piece_id', $piece->id)
            ->where('status', 'reserved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('order_id', '!=', $order->id)
            ->exists();

        if ($activePieceReservation) {
            throw new RuntimeException('The selected slab/piece is currently being checked out by another customer. Please select another piece.');
        }

        $qtyToReserve = round(max($qtyToReserve, 0), 3);
        $pieceWeight = round((float) ($piece->weight_kg ?? $item->item_weight ?? 0), 3);

        if ($this->stockTargetNeedsReservation($product, $variant)) {
            $this->reserveStockTarget($order, $item, $product, $variant, $qtyToReserve, $expiresAt);
        }

        StockReservation::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'user_id' => $order->user_id,
            'session_id' => session()->getId(),
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'inventory_piece_id' => $piece->id,
            'quantity' => 0,
            'weight_kg' => $pieceWeight > 0 ? $pieceWeight : null,
            'status' => 'reserved',
            'expires_at' => $expiresAt,
            'meta' => [
                'type' => 'inventory_piece',
                'order_number' => $order->order_number,
                'piece_no' => $piece->piece_no,
                'label' => $piece->label,
                'lot_id' => $piece->inventory_lot_id,
                'lot_code' => $piece->inventoryLot?->lot_code,
            ],
        ]);
    }

    protected function orderItemNeedsReservation(OrderItem $item): bool
    {
        $product = Product::query()->find($item->product_id);
        if (! $product) {
            return false;
        }

        $variant = null;
        if ($item->product_variant_id) {
            $variant = ProductVariant::query()->find($item->product_variant_id);
        }

        if ($this->selectedPieceIdForOrderItem($item) > 0) {
            return true;
        }

        return $this->stockTargetNeedsReservation($product, $variant);
    }

    protected function stockTargetNeedsReservation(Product $product, ?ProductVariant $variant): bool
    {
        if ($variant) {
            return $variant->stock_quantity !== null || (bool) ($variant->manage_stock ?? false);
        }

        return (bool) ($product->manage_stock ?? false);
    }

    protected function activeReservedQuantityForTarget(int $productId, ?int $variantId, ?int $excludeOrderId = null): float
    {
        $query = StockReservation::query()
            ->where('status', 'reserved')
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }

        if ($excludeOrderId) {
            $query->where('order_id', '!=', $excludeOrderId);
        }

        return round((float) $query->sum('quantity'), 3);
    }

    protected function selectedPieceIdForOrderItem(OrderItem $item): int
    {
        $snapshot = is_array($item->attributes_snapshot ?? null) ? $item->attributes_snapshot : [];
        $selectedPiece = is_array($snapshot['selected_piece'] ?? null) ? $snapshot['selected_piece'] : [];

        return max(0, (int) ($selectedPiece['piece_id'] ?? 0));
    }

    protected function stockQuantityForItem(OrderItem $item, Product $product, ?ProductVariant $variant, int $selectedPieceId = 0): float
    {
        $qty = round((float) ($item->quantity ?? 0), 3);

        if ($selectedPieceId > 0) {
            $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));
            if ($sellUnit === 'kg') {
                $snapshot = is_array($item->attributes_snapshot ?? null) ? $item->attributes_snapshot : [];
                $selectedPiece = is_array($snapshot['selected_piece'] ?? null) ? $snapshot['selected_piece'] : [];
                $pieceWeight = round((float) ($selectedPiece['weight_kg'] ?? $item->item_weight ?? 0), 3);

                return $pieceWeight > 0 ? $pieceWeight : $qty;
            }
        }

        return $qty;
    }

    protected function markOrdersExpiredAfterReservationTimeout(array $orderIds, Carbon $now): void
    {
        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->where('payment_status', 'pending')
            ->whereIn('status', ['pending_payment', 'processing'])
            ->get();

        foreach ($orders as $order) {
            try {
                $order->status = 'payment_expired';
                $order->payment_status = 'expired';
                $order->save();

                Payment::query()
                    ->where('order_id', $order->id)
                    ->whereIn('status', ['created', 'authorized'])
                    ->update([
                        'status' => 'failed',
                        'updated_at' => $now,
                    ]);

                try {
                    app(\App\Services\BandaraCreditService::class)
                        ->releaseReservedRedemptionForOrder($order->fresh(), 'stock_reservation_expired');
                } catch (\Throwable $e) {
                    Log::error('Failed to release Bandara Credit reservation after stock reservation expiry', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to mark order payment expired after stock reservation timeout', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

}

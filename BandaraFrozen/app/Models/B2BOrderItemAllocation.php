<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BOrderItemAllocation extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_RELEASED = 'released';
    public const STATUS_SOLD = 'sold';

    protected $fillable = [
        'b2b_order_request_id',
        'b2b_order_request_item_id',
        'product_id',
        'product_variant_id',
        'inventory_lot_id',
        'inventory_piece_id',
        'weight_kg',
        'unit_price',
        'line_total',
        'status',
        'allocated_by_id',
        'allocated_at',
        'released_by_id',
        'released_at',
        'sold_order_id',
        'sold_order_item_id',
        'sold_at',
        'notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'allocated_at' => 'datetime',
        'released_at' => 'datetime',
        'sold_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(B2BOrderRequest::class, 'b2b_order_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(B2BOrderRequestItem::class, 'b2b_order_request_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function inventoryLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function inventoryPiece(): BelongsTo
    {
        return $this->belongsTo(InventoryPiece::class, 'inventory_piece_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_id');
    }

    public function soldOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sold_order_id');
    }

    public function soldOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'sold_order_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sold_order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'sold_order_item_id');
    }
}

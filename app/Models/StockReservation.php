<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'session_id',
        'product_id',
        'product_variant_id',
        'inventory_piece_id',
        'inventory_pack_id',
        'quantity',
        'weight_kg',
        'status',
        'reserved_at',
        'expires_at',
        'committed_at',
        'released_at',
        'release_reason',
    ];

    protected $casts = [
        'quantity' => 'float',
        'weight_kg' => 'float',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'committed_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function inventoryPiece()
    {
        return $this->belongsTo(InventoryPiece::class);
    }
}

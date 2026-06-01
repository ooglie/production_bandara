<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryPack extends Model
{
    protected $table = 'inventory_packs';

    protected $fillable = [
        'production_run_id',
        'source_inventory_lot_id',
        'source_inventory_piece_id',
        'product_id',
        'product_variant_id',
        'product_sell_unit_id',
        'pack_code',
        'pack_no',
        'pack_quantity',
        'available_pack_quantity',
        'pieces_per_pack',
        'total_pieces',
        'available_pieces',
        'source_pieces_per_unit',
        'source_quantity_consumed',
        'source_weight_kg_consumed',
        'unit_weight_kg',
        'total_weight_kg',
        'unit_cost',
        'total_cost',
        'sold_weight_kg',
        'actual_weight_kg',
        'packed_date',
        'expiry_date',
        'batch_code',
        'status',
        'reserved_until',
        'sold_order_id',
        'sold_order_item_id',
        'sold_at',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'pack_no' => 'integer',
        'pack_quantity' => 'decimal:3',
        'available_pack_quantity' => 'decimal:3',
        'pieces_per_pack' => 'decimal:3',
        'total_pieces' => 'decimal:3',
        'available_pieces' => 'decimal:3',
        'source_pieces_per_unit' => 'decimal:3',
        'source_quantity_consumed' => 'decimal:3',
        'source_weight_kg_consumed' => 'decimal:3',
        'unit_weight_kg' => 'decimal:3',
        'total_weight_kg' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'sold_weight_kg' => 'decimal:3',
        'actual_weight_kg' => 'decimal:3',
        'packed_date' => 'date',
        'expiry_date' => 'date',
        'reserved_until' => 'datetime',
        'sold_order_id' => 'integer',
        'sold_order_item_id' => 'integer',
        'sold_at' => 'datetime',
    ];

    public function productionRun(): BelongsTo
    {
        return $this->belongsTo(ProductionRun::class, 'production_run_id');
    }

    public function sourceLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'source_inventory_lot_id');
    }

    public function sourcePiece(): BelongsTo
    {
        return $this->belongsTo(InventoryPiece::class, 'source_inventory_piece_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function sellUnit(): BelongsTo
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function soldOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sold_order_id');
    }

    public function soldOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'sold_order_item_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

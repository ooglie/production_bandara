<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionRunInput extends Model
{
    protected $fillable = [
        'production_run_id',
        'inventory_lot_id',
        'product_id',
        'product_variant_id',
        'consumed_quantity',
        'consumed_weight_kg',
        'consumed_piece_count',
        'unit_cost_snapshot',
        'total_cost_snapshot',
        'notes',
    ];

    protected $casts = [
        'consumed_quantity' => 'decimal:3',
        'consumed_weight_kg' => 'decimal:3',
        'unit_cost_snapshot' => 'decimal:2',
        'total_cost_snapshot' => 'decimal:2',
    ];

    public function run()
    {
        return $this->belongsTo(ProductionRun::class, 'production_run_id');
    }

    public function inventoryLot()
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
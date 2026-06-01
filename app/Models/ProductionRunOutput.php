<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionRunOutput extends Model
{
    protected $fillable = [
        'production_run_id',
        'inventory_lot_id',
        'product_id',
        'product_variant_id',
        'output_stage',
        'produced_quantity',
        'produced_weight_kg',
        'piece_count',
        'unit_weight_kg',
        'pack_size_kg',
        'is_saleable',
        'can_repack',
        'inventory_output',
        'allocated_cost',
        'notes',
    ];

    protected $casts = [
        'produced_quantity' => 'decimal:3',
        'produced_weight_kg' => 'decimal:3',
        'unit_weight_kg' => 'decimal:3',
        'pack_size_kg' => 'decimal:3',
        'is_saleable' => 'boolean',
        'can_repack' => 'boolean',
        'inventory_output' => 'boolean',
        'allocated_cost' => 'decimal:2',
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
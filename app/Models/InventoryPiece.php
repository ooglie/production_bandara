<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryPiece extends Model
{
    protected $fillable = [
        'inventory_lot_id',
        'piece_no',
        'label',
        'weight_kg',
        'available_weight_kg',
        'status',
        'consumed_in_production_run_id',
        'sold_order_item_id',
        'notes',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'available_weight_kg' => 'decimal:3',
    ];

    public function inventoryLot()
    {
        return $this->belongsTo(InventoryLot::class);
    }

    public function consumedInRun()
    {
        return $this->belongsTo(ProductionRun::class, 'consumed_in_production_run_id');
    }
}
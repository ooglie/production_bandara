<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'product_sell_unit_id',
        'vendor_id',
        'quantity',
        'movement_type',   // sale, purchase, adjustment, return
        'reference_type',  // e.g. 'vendor_invoice'
        'reference_id',
        'cost_price',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'cost_price' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function sellUnit()
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorInvoiceItem extends Model
{
    protected $fillable = [
        'vendor_invoice_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'tax_amount',
        'total',
        'unit_weight_kg',
        'total_weight_kg',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'unit_weight_kg' => 'decimal:2',
        'total_weight_kg' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function inventoryLot()
    {
        return $this->hasOne(\App\Models\InventoryLot::class, 'vendor_invoice_item_id');
    }
}

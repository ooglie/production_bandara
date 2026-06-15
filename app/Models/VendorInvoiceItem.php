<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorInvoiceItem extends Model
{
    protected $fillable = [
        'vendor_invoice_id',
        'product_id',
        'product_variant_id',
        'product_sell_unit_id',
        'receipt_type',
        'quantity',
        'unit_cost',
        'unit_cost_includes_gst',
        'tax_manual',
        'hsn_code_id',
        'gst_rate',
        'mrp_incl_gst',
        'tax_amount',
        'total',
        'unit_weight_kg',
        'total_weight_kg',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_cost'  => 'decimal:2',
        'unit_cost_includes_gst' => 'boolean',
        'tax_manual' => 'boolean',
        'gst_rate' => 'decimal:2',
        'mrp_incl_gst' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'unit_weight_kg' => 'decimal:3',
        'total_weight_kg' => 'decimal:3',
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

    public function sellUnit()
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function hsnCode()
    {
        return $this->belongsTo(HsnCode::class, 'hsn_code_id');
    }

    public function inventoryLot()
    {
        return $this->hasOne(\App\Models\InventoryLot::class, 'vendor_invoice_item_id');
    }
}

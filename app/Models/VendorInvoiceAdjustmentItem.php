<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorInvoiceAdjustmentItem extends Model
{
    protected $fillable = [
        'vendor_invoice_adjustment_id',
        'vendor_invoice_item_id',
        'product_id',
        'product_variant_id',
        'inventory_lot_id',
        'quantity_delta',
        'weight_delta_kg',
        'piece_count_delta',
        'original_unit_cost',
        'revised_unit_cost',
        'subtotal_delta',
        'tax_delta',
        'total_delta',
        'affects_stock',
        'meta',
    ];

    protected $casts = [
        'quantity_delta' => 'decimal:3',
        'weight_delta_kg' => 'decimal:3',
        'piece_count_delta' => 'integer',
        'original_unit_cost' => 'decimal:2',
        'revised_unit_cost' => 'decimal:2',
        'subtotal_delta' => 'decimal:2',
        'tax_delta' => 'decimal:2',
        'total_delta' => 'decimal:2',
        'affects_stock' => 'boolean',
        'meta' => 'array',
    ];

    public function adjustment()
    {
        return $this->belongsTo(VendorInvoiceAdjustment::class, 'vendor_invoice_adjustment_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(VendorInvoiceItem::class, 'vendor_invoice_item_id');
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
        return $this->belongsTo(InventoryLot::class);
    }
}

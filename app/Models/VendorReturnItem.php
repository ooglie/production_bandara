<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorReturnItem extends Model
{
    protected $fillable = [
        'vendor_return_id',
        'vendor_invoice_item_id',
        'product_id',
        'product_variant_id',
        'inventory_lot_id',
        'return_mode',
        'quantity',
        'weight_kg',
        'piece_count',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'inventory_piece_ids',
        'inventory_pack_ids',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'weight_kg' => 'decimal:3',
        'piece_count' => 'integer',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'inventory_piece_ids' => 'array',
        'inventory_pack_ids' => 'array',
        'meta' => 'array',
    ];

    public function vendorReturn()
    {
        return $this->belongsTo(VendorReturn::class);
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

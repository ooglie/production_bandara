<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use App\Models\VendorPayment;
use App\Models\Product;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'gst_number',
        'fssai_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'state_code',
        'country',
        'pincode',
        'notes',
        'is_active',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function vendorInvoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function vendorPayments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function invoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function invoiceItems()
    {
        return $this->hasManyThrough(
            VendorInvoiceItem::class,
            VendorInvoice::class,
            'vendor_id',         // FK on vendor_invoices -> vendors
            'vendor_invoice_id', // FK on vendor_invoice_items -> vendor_invoices
            'id',                // local key on vendors
            'id'                 // local key on vendor_invoices
        );
    }

    /**
     * Helper: products this vendor has actually supplied
     * (based on vendor_invoice_items).
     */
    public function suppliedProducts()
    {
        $productIds = $this->invoiceItems()
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::whereIn('id', $productIds)
            ->orderBy('name')
            ->get();
    }

}

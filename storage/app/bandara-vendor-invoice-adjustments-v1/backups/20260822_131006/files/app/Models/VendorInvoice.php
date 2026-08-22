<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorInvoice extends Model
{
    protected $fillable = [
        'vendor_id',
        'invoice_number',
        'invoice_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',          // pending, partially_paid, paid, cancelled
        'due_date',
        'notes',
        'tally_reference',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorInvoice()
    {
        return $this->belongsTo(vendorInvoice::class);
    }

    public function items()
    {
        return $this->hasMany(VendorInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceAmountAttribute(): float
    {
        return (float) max(0, $this->total_amount - $this->paid_amount);
    }
}

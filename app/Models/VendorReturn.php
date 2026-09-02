<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorReturn extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CREDIT_PENDING = 'credit_pending';
    public const STATUS_CREDITED = 'credited';

    protected $fillable = [
        'vendor_invoice_id',
        'return_number',
        'return_date',
        'status',
        'reference_number',
        'reason',
        'notes',
        'expected_subtotal',
        'expected_tax',
        'expected_total',
        'credit_note_received',
        'supplier_credit_note_number',
        'supplier_credit_note_date',
        'supplier_credit_adjustment_id',
        'meta',
        'created_by_id',
        'posted_by_id',
        'posted_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'expected_subtotal' => 'decimal:2',
        'expected_tax' => 'decimal:2',
        'expected_total' => 'decimal:2',
        'credit_note_received' => 'boolean',
        'supplier_credit_note_date' => 'date',
        'meta' => 'array',
        'posted_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(VendorReturnItem::class);
    }

    public function supplierCreditAdjustment()
    {
        return $this->belongsTo(VendorInvoiceAdjustment::class, 'supplier_credit_adjustment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}

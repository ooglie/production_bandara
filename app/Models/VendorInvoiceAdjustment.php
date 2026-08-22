<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorInvoiceAdjustment extends Model
{
    public const TYPE_CREDIT_NOTE = 'credit_note';
    public const TYPE_DEBIT_NOTE = 'debit_note';
    public const TYPE_PURCHASE_RETURN_CREDIT = 'purchase_return_credit';
    public const TYPE_FULL_REVERSAL = 'full_reversal';
    public const TYPE_METADATA_CORRECTION = 'metadata_correction';
    public const TYPE_ADJUSTMENT_REVERSAL = 'adjustment_reversal';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';
    public const DIRECTION_NEUTRAL = 'neutral';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'vendor_invoice_id',
        'adjustment_number',
        'type',
        'direction',
        'status',
        'supplier_document_number',
        'supplier_document_date',
        'reason',
        'notes',
        'subtotal_delta',
        'tax_delta',
        'total_delta',
        'affects_stock',
        'reverses_adjustment_id',
        'meta',
        'created_by_id',
        'posted_by_id',
        'posted_at',
    ];

    protected $casts = [
        'supplier_document_date' => 'date',
        'subtotal_delta' => 'decimal:2',
        'tax_delta' => 'decimal:2',
        'total_delta' => 'decimal:2',
        'affects_stock' => 'boolean',
        'meta' => 'array',
        'posted_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'vendor_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(VendorInvoiceAdjustmentItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function reversesAdjustment()
    {
        return $this->belongsTo(self::class, 'reverses_adjustment_id');
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reverses_adjustment_id');
    }

    public function vendorReturn()
    {
        return $this->hasOne(VendorReturn::class, 'supplier_credit_adjustment_id');
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_CREDIT_NOTE => 'Supplier credit note',
            self::TYPE_DEBIT_NOTE => 'Supplier debit note',
            self::TYPE_PURCHASE_RETURN_CREDIT => 'Purchase return credit',
            self::TYPE_FULL_REVERSAL => 'Full invoice reversal',
            self::TYPE_METADATA_CORRECTION => 'Invoice details corrected',
            self::TYPE_ADJUSTMENT_REVERSAL => 'Adjustment reversal',
            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }
}

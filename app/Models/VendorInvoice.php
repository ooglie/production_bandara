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

    public function items()
    {
        return $this->hasMany(VendorInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function adjustments()
    {
        return $this->hasMany(VendorInvoiceAdjustment::class);
    }

    public function postedAdjustments()
    {
        return $this->hasMany(VendorInvoiceAdjustment::class)
            ->where('status', VendorInvoiceAdjustment::STATUS_POSTED);
    }

    public function vendorReturns()
    {
        return $this->hasMany(VendorReturn::class);
    }

    public function getPaidAmountAttribute(): float
    {
        if (array_key_exists('paid_total', $this->attributes)) {
            return round((float) $this->attributes['paid_total'], 2);
        }

        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('amount'), 2);
        }

        return round((float) $this->payments()->sum('amount'), 2);
    }

    public function getPostedAdjustmentTotalAttribute(): float
    {
        if (array_key_exists('posted_adjustment_total', $this->attributes)) {
            return round((float) $this->attributes['posted_adjustment_total'], 2);
        }

        if ($this->relationLoaded('postedAdjustments')) {
            return round((float) $this->postedAdjustments->sum('total_delta'), 2);
        }

        if ($this->relationLoaded('adjustments')) {
            return round((float) $this->adjustments
                ->where('status', VendorInvoiceAdjustment::STATUS_POSTED)
                ->sum('total_delta'), 2);
        }

        return round((float) $this->postedAdjustments()->sum('total_delta'), 2);
    }

    public function getAdjustedSubtotalAttribute(): float
    {
        $delta = $this->postedAdjustmentComponent('subtotal_delta', 'posted_adjustment_subtotal');

        return round(max(0, (float) $this->subtotal + $delta), 2);
    }

    public function getAdjustedTaxAmountAttribute(): float
    {
        $delta = $this->postedAdjustmentComponent('tax_delta', 'posted_adjustment_tax');

        return round(max(0, (float) $this->tax_amount + $delta), 2);
    }

    public function getAdjustedTotalAmountAttribute(): float
    {
        return round(max(0, (float) $this->total_amount + $this->posted_adjustment_total), 2);
    }

    public function getBalanceAmountAttribute(): float
    {
        return round(max(0, $this->adjusted_total_amount - $this->paid_amount), 2);
    }

    public function getVendorCreditDueAttribute(): float
    {
        return round(max(0, $this->paid_amount - $this->adjusted_total_amount), 2);
    }

    private function postedAdjustmentComponent(string $column, string $aggregateAttribute): float
    {
        if (array_key_exists($aggregateAttribute, $this->attributes)) {
            return round((float) $this->attributes[$aggregateAttribute], 2);
        }

        if ($this->relationLoaded('postedAdjustments')) {
            return round((float) $this->postedAdjustments->sum($column), 2);
        }

        if ($this->relationLoaded('adjustments')) {
            return round((float) $this->adjustments
                ->where('status', VendorInvoiceAdjustment::STATUS_POSTED)
                ->sum($column), 2);
        }

        return round((float) $this->postedAdjustments()->sum($column), 2);
    }
}

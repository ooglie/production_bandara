<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'status',            // pending, due, part_payment, past_due, paid
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_total',
        'discount_total',
        'bandara_credit_redeemed_points',
        'bandara_credit_redeemed_amount',
        'bandara_credit_points_redeemed',
        'bandara_credit_discount_total',
        'grand_total',
        'pdf_path',
        'mailed_to_customer_at',
        'mailed_to_accountant_at',
        'tally_reference',
        
    ];

    protected $casts = [
        'invoice_date'             => 'date',
        'due_date'                 => 'date',
        'subtotal'                 => 'float',
        'tax_total'                => 'float',
        'discount_total'           => 'float',
        'bandara_credit_redeemed_points' => 'integer',
        'bandara_credit_redeemed_amount' => 'float',
        'bandara_credit_points_redeemed' => 'integer',
        'bandara_credit_discount_total' => 'float',
        'grand_total'              => 'float',
        'mailed_to_customer_at'    => 'datetime',
        'mailed_to_accountant_at'  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers / scopes
    |--------------------------------------------------------------------------
    */

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'invoice_payments')
            ->withPivot('amount_applied')
            ->withTimestamps();
    }

    public function paymentSubmissions()
    {
        return $this->hasMany(InvoicePaymentSubmission::class);
    }

    public function getPendingSubmittedPaymentAmountAttribute(): float
    {
        return (float) $this->paymentSubmissions()
            ->where('status', 'pending')
            ->sum('amount');
    }

    public function getAmountPaidAttribute()
    {
        return (float) $this->payments()->sum('invoice_payments.amount_applied');
    }

    public function getBalanceAmountAttribute()
    {
        return max(0, (float) $this->grand_total - $this->amount_paid);
    }

    public function syncStatusFromPayments(): void
    {
        $paid  = $this->amount_paid;
        $total = (float) $this->grand_total;

        if ($paid <= 0) {
            $this->status = $this->defaultOpenStatus();
        } elseif ($paid + 0.00001 < $total) {
            $this->status = 'part_payment';
        } else {
            $this->status = 'paid';
        }

        $this->save();
    }

    public function defaultOpenStatus(): string
    {
        $order = $this->relationLoaded('order') ? $this->order : $this->order()->first();

        if (($order?->payment_method ?? null) === 'pay_later') {
            if ($this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday()) {
                return 'past_due';
            }

            return 'due';
        }

        return 'pending';
    }

    public function getIsPayLaterAttribute(): bool
    {
        $order = $this->relationLoaded('order') ? $this->order : $this->order()->first();

        return ($order?->payment_method ?? null) === 'pay_later';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        $order = $this->relationLoaded('order') ? $this->order : $this->order()->first();

        return ($order?->payment_method ?? 'razorpay') === 'pay_later'
            ? 'Pay Later on invoice'
            : 'Pay Now / Razorpay';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        $status = strtolower((string) ($this->status ?? 'pending'));

        return match ($status) {
            'paid' => 'Paid',
            'part_payment' => 'Part payment',
            'past_due' => 'Past due',
            'due' => 'Due',
            'pending' => 'Pending',
            default => str($status)->replace('_', ' ')->headline()->toString(),
        };
    }

}

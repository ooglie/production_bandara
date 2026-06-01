<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;

class Order extends Model
{
    use HasFactory;

    // Table name is "orders" as per your migration
    protected $table = 'orders';

    // We mostly set fields manually in controllers, but it's safe to use guarded = []
    protected $guarded = [];

    protected $casts = [
        'subtotal'        => 'float',
        'discount_total'  => 'float',
        'tax_total'       => 'float',
        'shipping_total'  => 'float',
        'bandara_credit_redeemed_points' => 'integer',
        'bandara_credit_redeemed_amount' => 'float',
        'bandara_credit_points_redeemed' => 'integer',
        'bandara_credit_discount_total' => 'float',
        'bandara_credit_order_total_before_redemption' => 'float',
        'grand_total'     => 'float',
        'payment_method'  => 'string',
        'payment_terms_days' => 'integer',
        'payment_due_at'  => 'datetime',
        'pay_later_approved_at' => 'datetime',
        'cgst_amount'     => 'float',
        'sgst_amount'     => 'float',
        'igst_amount'     => 'float',
        'placed_at'       => 'datetime',
        'printed_at'      => 'datetime',
        'cancelled_at'    => 'datetime',
        'shipped_at'      => 'datetime',
        'delivered_at'    => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'item_weight'     => 'decimal:3',
        'sell_unit'       => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'shipping');
    }

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class)->where('type', 'billing');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    // // Only if you already created Invoice model + table
    // public function invoice()
    // {
    //     return $this->hasOne(Invoice::class);
    // }

    /*
    |--------------------------------------------------------------------------
    | Helpers / accessors
    |--------------------------------------------------------------------------
    */

    public function getIsPaidAttribute(): bool
    {
        return strtolower($this->payment_status ?? '') === 'paid';
    }

    public function getIsPayLaterAttribute(): bool
    {
        return strtolower((string) ($this->payment_method ?? 'razorpay')) === 'pay_later';
    }

    public function getIsCancelledAttribute(): bool
    {
        return strtolower($this->status ?? '') === 'cancelled';
    }

    public function getIsDeliveredAttribute(): bool
    {
        return strtolower($this->status ?? '') === 'delivered';
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    //Invoice related scope
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoice()
    {
        // Primary invoice for the order (we generally expect one)
        return $this->hasOne(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function printedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'printed_by_id');
    }

}

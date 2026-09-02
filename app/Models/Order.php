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

    protected $fillable = [
        'order_number',
        'user_id',
        'delivery_agent_id',
        'status',
        'delivery_status',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'delivery_zone_id',
        'delivery_pincode',
        'delivery_distance_km',
        'delivery_duration_minutes',
        'delivery_distance_provider',
        'delivery_distance_calculated_at',
        'delivery_fee_source',
        'delivery_fee',
        'handling_fee',
        'delivery_tax_amount',
        'handling_tax_amount',
        'delivery_tax_rate',
        'handling_tax_rate',
        'delivery_sac_code',
        'handling_sac_code',
        'bandara_credit_redeemed_points',
        'bandara_credit_redeemed_amount',
        'bandara_credit_points_redeemed',
        'bandara_credit_discount_total',
        'bandara_credit_order_total_before_redemption',
        'grand_total',
        'coupon_id',
        'gst_type',
        'supplier_gstin',
        'supplier_gst_state_code',
        'bill_to_gstin',
        'bill_to_gst_state_code',
        'ship_to_gst_state_code',
        'place_of_supply_gst_state_code',
        'gst_determination_basis',
        'is_bill_to_ship_to',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'payment_status',
        'payment_method',
        'payment_due_at',
        'payment_terms_days',
        'pay_later_approved_at',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'customer_note',
        'placed_at',
        'cancelled_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
        'delivered_by_id',
        'delivery_failed_at',
        'delivery_failure_reason',
        'delivery_note',
        'cancelled_by_id',
        'tally_reference',
        'tally_export_status',
        'printed_at',
        'printed_by_id',
    ];

    protected $casts = [
        'subtotal'        => 'float',
        'discount_total'  => 'float',
        'tax_total'       => 'float',
        'shipping_total'  => 'float',
        'delivery_status' => 'string',
        'delivery_fee' => 'float',
        'handling_fee' => 'float',
        'delivery_tax_amount' => 'float',
        'handling_tax_amount' => 'float',
        'delivery_tax_rate' => 'float',
        'handling_tax_rate' => 'float',
        'delivery_sac_code' => 'string',
        'handling_sac_code' => 'string',
        'delivery_distance_km' => 'float',
        'delivery_duration_minutes' => 'integer',
        'delivery_distance_calculated_at' => 'datetime',
        'bandara_credit_redeemed_points' => 'integer',
        'bandara_credit_redeemed_amount' => 'float',
        'bandara_credit_points_redeemed' => 'integer',
        'bandara_credit_discount_total' => 'float',
        'bandara_credit_order_total_before_redemption' => 'float',
        'grand_total'     => 'float',
        'supplier_gstin' => 'string',
        'supplier_gst_state_code' => 'string',
        'bill_to_gstin' => 'string',
        'bill_to_gst_state_code' => 'string',
        'ship_to_gst_state_code' => 'string',
        'place_of_supply_gst_state_code' => 'string',
        'gst_determination_basis' => 'string',
        'is_bill_to_ship_to' => 'boolean',
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
        'out_for_delivery_at' => 'datetime',
        'delivered_at'    => 'datetime',
        'delivery_failed_at' => 'datetime',
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

    public function deliveryAgent()
    {
        return $this->belongsTo(User::class, 'delivery_agent_id');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by_id');
    }

    public function deliveryEvents()
    {
        return $this->hasMany(OrderDeliveryEvent::class)->latest();
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

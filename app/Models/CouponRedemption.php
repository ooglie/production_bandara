<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    // If your table name is "coupons_redemption" (as you wrote earlier),
    // uncomment and adjust this:
    protected $table = 'coupon_redemptions';

    // If it's "coupon_redemptions", use:
    // protected $table = 'coupon_redemptions';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount',
        'redeemed_at',
        // add any other fields you actually have
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_limit_per_user',
        'usage_count',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'discount_value'       => 'float',
        'max_discount_amount'  => 'float',
        'min_order_amount'     => 'float',
        'usage_limit'          => 'int',
        'usage_limit_per_user' => 'int',
        'usage_count'          => 'int',
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'is_active'            => 'bool',
    ];

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Keep compatibility with code that expects per_user_limit.
     */
    public function getPerUserLimitAttribute(): ?int
    {
        return $this->usage_limit_per_user;
    }

    /**
     * Keep compatibility with code that expects a one-time-per-user flag.
     * If your DB does not have is_one_time, per-user limit of 1 behaves like one-time.
     */
    public function getIsOneTimeAttribute(): bool
    {
        if (array_key_exists('is_one_time', $this->attributes)) {
            return (bool) $this->attributes['is_one_time'];
        }

        return (int) ($this->usage_limit_per_user ?? 0) === 1;
    }

    /**
     * Used count via coupon_redemptions table.
     */
    public function getUsedCountAttribute(): int
    {
        if (array_key_exists('redemptions_count', $this->attributes)) {
            return (int) $this->attributes['redemptions_count'];
        }

        return $this->redemptions()->count();
    }

    /**
     * Whether coupon is currently usable (global checks only).
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
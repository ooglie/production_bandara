<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryChargeRule extends Model
{
    protected $fillable = [
        'delivery_zone_id',
        'customer_type',
        'min_order_value',
        'delivery_fee',
        'included_distance_km',
        'free_delivery_above',
        'tax_rate',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'min_order_value' => 'float',
        'delivery_fee' => 'float',
        'included_distance_km' => 'float',
        'free_delivery_above' => 'float',
        'tax_rate' => 'float',
        'is_active' => 'bool',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}

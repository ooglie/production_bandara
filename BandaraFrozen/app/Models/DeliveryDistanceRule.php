<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDistanceRule extends Model
{
    protected $fillable = [
        'customer_type',
        'min_order_value',
        'min_distance_km',
        'max_distance_km',
        'delivery_fee',
        'included_distance_km',
        'per_km_fee',
        'free_delivery_above',
        'tax_rate',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'min_order_value' => 'float',
        'min_distance_km' => 'float',
        'max_distance_km' => 'float',
        'delivery_fee' => 'float',
        'included_distance_km' => 'float',
        'per_km_fee' => 'float',
        'free_delivery_above' => 'float',
        'tax_rate' => 'float',
        'is_active' => 'bool',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandlingChargeRule extends Model
{
    protected $fillable = [
        'customer_type',
        'temperature_mode',
        'min_order_value',
        'handling_fee',
        'free_handling_above',
        'tax_rate',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'min_order_value' => 'float',
        'handling_fee' => 'float',
        'free_handling_above' => 'float',
        'tax_rate' => 'float',
        'is_active' => 'bool',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}

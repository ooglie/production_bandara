<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandaraCreditTier extends Model
{
    protected $fillable = [
        'key',
        'name',
        'threshold_min',
        'threshold_max',
        'reward_rate_percent',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'threshold_min' => 'integer',
        'threshold_max' => 'integer',
        'reward_rate_percent' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

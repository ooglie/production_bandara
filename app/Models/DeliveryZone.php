<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'sort_order' => 'integer',
    ];

    public function pincodes(): HasMany
    {
        return $this->hasMany(DeliveryZonePincode::class);
    }

    public function deliveryChargeRules(): HasMany
    {
        return $this->hasMany(DeliveryChargeRule::class);
    }
}

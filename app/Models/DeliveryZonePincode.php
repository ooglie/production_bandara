<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZonePincode extends Model
{
    protected $fillable = [
        'delivery_zone_id',
        'pincode',
        'city',
        'area_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}

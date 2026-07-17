<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    protected $table = 'order_addresses';

    protected $fillable = [
        'order_id',
        'type',
        'full_name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'state_code',
        'country',
        'pincode',
        'latitude',
        'longitude',
        'geocoding_provider',
        'geocoding_quality',
        'gstin',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

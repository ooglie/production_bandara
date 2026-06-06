<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDeliveryEvent extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'event_type',
        'old_status',
        'new_status',
        'note',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

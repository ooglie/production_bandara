<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    protected $table = 'product_offers';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'offer_type',
        'offer_value',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by_id',
    ];

    protected $casts = [
        'offer_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

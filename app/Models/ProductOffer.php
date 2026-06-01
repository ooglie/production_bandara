<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    protected $table = 'product_offers';

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

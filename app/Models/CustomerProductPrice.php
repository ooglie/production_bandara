<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProductPrice extends Model
{
    protected $table = 'customer_product_prices';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_sell_unit_id',
        'product_variant_id',
        'price',
        'currency',
        'valid_from',
        'valid_to',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'is_active'  => 'boolean',
        'valid_from' => 'date',
        'valid_to'   => 'date',
        'product_sell_unit_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sellUnit()
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}

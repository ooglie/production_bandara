<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'product_sell_unit_id',
        'b2b_order_mode',
        'requested_piece_count',
        'requested_weight_kg',
        'quantity',
        'unit_price',
        'total',
        'item_weight',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
        'item_weight' => 'float',
        'requested_piece_count' => 'integer',
        'requested_weight_kg' => 'decimal:3',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function sellUnit()
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }
}

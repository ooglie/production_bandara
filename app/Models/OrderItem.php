<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'sku',
        'attributes_snapshot',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'item_weight',
        'sell_unit',
        'pricing_unit',
    ];

    protected $casts = [
        'attributes_snapshot' => 'array',
        'quantity'            => 'float',
        'unit_price'          => 'float',
        'subtotal'            => 'float',
        'discount_amount'     => 'float',
        'tax_amount'          => 'float',
        'total'               => 'float',
        'cgst_amount'         => 'float',
        'sgst_amount'         => 'float',
        'igst_amount'         => 'float',
        'item_weight'         => 'decimal:3',
        'sell_unit'           => 'string',
        'pricing_unit'        => 'string',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

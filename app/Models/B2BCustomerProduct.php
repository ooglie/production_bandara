<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class B2BCustomerProduct extends Model
{
    protected $table = 'b2b_customer_products';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_sell_unit_id',
        'min_order_quantity',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'min_order_quantity' => 'decimal:2',
        'product_sell_unit_id' => 'integer',
        'is_active'          => 'boolean',
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

    public function getAssignmentLabelAttribute(): string
    {
        $product = $this->product?->name ?? ('Product #' . $this->product_id);

        if ($this->sellUnit) {
            return $product . ' — ' . $this->sellUnit->display_label;
        }

        return $product . ' — product-level';
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

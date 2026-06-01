<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class B2BProductRequest extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_product_requests';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_sell_unit_id',
        'product_variant_id',
        'status',
        'requested_quantity',
        'message',
        'admin_note',
        'resolved_by_id',
        'resolved_at',
    ];

    protected $casts = [
        'product_sell_unit_id' => 'integer',
        'requested_quantity' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productSellUnit()
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

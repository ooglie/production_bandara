<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attribute_id',
        'name',
        'value',
        'meta',
        'position',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_values', 'product_variant_id', 'attribute_value_id')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }
}

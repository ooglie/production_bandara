<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'barcode',
        'sku',
        'name',
        'pack_type',
        'manage_stock',
        'stock_quantity',
        'low_stock_threshold',
        'min_order_quantity',
        'product_weight',
        'pieces_per_pack',
        'price',
        'mrp_price',
        'standard_b2b_price',
        'standard_b2b_min_order_quantity',
        'customer_visibility',
        'inventory_can_repack',
        'pricing_unit',
        'is_active',
    ];

    protected $casts = [
        'manage_stock' => 'boolean',
        'is_active' => 'boolean',
        'inventory_can_repack' => 'boolean',
        'stock_quantity' => 'float',
        'low_stock_threshold' => 'float',
        'min_order_quantity' => 'float',
        'product_weight' => 'float',
        'pieces_per_pack' => 'float',
        'price' => 'float',
        'mrp_price' => 'float',
        'standard_b2b_price' => 'float',
        'standard_b2b_min_order_quantity' => 'float',
        'customer_visibility' => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isVisibleToCustomerType(?string $customerType): bool
    {
        $visibility = (string) ($this->customer_visibility ?: 'all');
        $customerType = $customerType === 'b2b' ? 'b2b' : 'b2c';

        return $visibility === 'all' || $visibility === $customerType;
    }


    /**
     * Selected product_attribute_values assigned to this variant.
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_attribute_values',
            'product_variant_id',
            'product_attribute_value_id'
        );
    }

    /**
     * Alias kept for compatibility with admin code.
     */
    public function variantAttributes(): BelongsToMany
    {
        return $this->attributeValues();
    }

    public static function hasVariantAttributePivotTable(): bool
    {
        return Schema::hasTable('product_variant_attribute_values');
    }
}
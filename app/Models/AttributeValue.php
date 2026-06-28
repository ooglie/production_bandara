<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_value_id');
    }

    /**
     * Count current catalogue usage before deleting or editing option values.
     *
     * @return array{products: int, variants: int}
     */
    public function usageCounts(): array
    {
        $productCount = 0;
        $variantCount = 0;

        if (Schema::hasTable('product_attribute_values')) {
            $productCount = (int) DB::table('product_attribute_values')
                ->where('attribute_value_id', $this->id)
                ->distinct()
                ->count('product_id');
        }

        if (Schema::hasTable('product_attribute_values')
            && Schema::hasTable('product_variant_attribute_values')
            && Schema::hasColumn('product_variant_attribute_values', 'product_attribute_value_id')) {
            $variantCount = (int) DB::table('product_variant_attribute_values as pvav')
                ->join('product_attribute_values as pav', 'pav.id', '=', 'pvav.product_attribute_value_id')
                ->where('pav.attribute_value_id', $this->id)
                ->distinct()
                ->count('pvav.product_variant_id');
        }

        return [
            'products' => $productCount,
            'variants' => $variantCount,
        ];
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_values', 'product_variant_id', 'attribute_value_id')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }
}

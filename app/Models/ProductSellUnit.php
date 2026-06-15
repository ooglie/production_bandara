<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSellUnit extends Model
{
    use SoftDeletes;

    public const UNIT_TYPES = [
        'piece',
        'pack',
        'box',
        'kg',
        'request_piece',
        'request_weight',
    ];

    public const SALE_TYPES = [
        'variable_weight',
        'fixed_weight_pack',
        'fixed_piece_pack',
    ];

    public const PRICING_UNITS = [
        'unit',
        'piece',
        'pack',
        'box',
        'kg',
    ];

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'unit_type',
        'pricing_unit',
        'sale_type',
        'pieces_per_unit',
        'weight_per_unit_kg',
        'base_price',
        'mrp_price',
        'b2c_price_includes_gst',
        'standard_b2b_price',
        'standard_b2b_min_order_quantity',
        'sort_order',
        'is_retail_visible',
        'is_b2b_visible',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'pieces_per_unit' => 'decimal:3',
        'weight_per_unit_kg' => 'decimal:3',
        'base_price' => 'decimal:2',
        'mrp_price' => 'decimal:2',
        'b2c_price_includes_gst' => 'boolean',
        'standard_b2b_price' => 'decimal:2',
        'standard_b2b_min_order_quantity' => 'decimal:3',
        'sort_order' => 'integer',
        'is_retail_visible' => 'boolean',
        'is_b2b_visible' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_sell_unit_id');
    }

    public function inventoryPacks(): HasMany
    {
        return $this->hasMany(InventoryPack::class, 'product_sell_unit_id');
    }

    public function b2bAssignments(): HasMany
    {
        return $this->hasMany(B2BCustomerProduct::class, 'product_sell_unit_id');
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerProductPrice::class, 'product_sell_unit_id');
    }

    public function getSaleTypeLabelAttribute(): string
    {
        return match ((string) ($this->sale_type ?? 'fixed_piece_pack')) {
            'variable_weight' => 'Variable weight',
            'fixed_weight_pack' => 'Fixed weight pack',
            default => 'Fixed piece pack',
        };
    }

    public function isFixedWeightPack(): bool
    {
        return (string) ($this->sale_type ?? '') === 'fixed_weight_pack';
    }

    public function isFixedPiecePack(): bool
    {
        return (string) ($this->sale_type ?? 'fixed_piece_pack') === 'fixed_piece_pack';
    }

    public function isVariableWeight(): bool
    {
        return (string) ($this->sale_type ?? '') === 'variable_weight';
    }

    public function getDisplayLabelAttribute(): string
    {
        $parts = [$this->name];

        if ($this->pieces_per_unit !== null) {
            $parts[] = rtrim(rtrim((string) $this->pieces_per_unit, '0'), '.') . ' pcs';
        }

        if ($this->weight_per_unit_kg !== null) {
            $parts[] = rtrim(rtrim((string) $this->weight_per_unit_kg, '0'), '.') . ' kg';
        }

        return implode(' · ', array_filter($parts));
    }
}

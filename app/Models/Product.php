<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public const DEFAULT_STORAGE_GUIDANCE = [
        'Keep frozen at or below -18°C.',
        'Once thawed, keep refrigerated and consume promptly.',
        'Do not refreeze after complete thawing.',
        'Cook thoroughly before serving where applicable.',
    ];

    public const DEFAULT_DELIVERY_SUPPORT = [
        'Delivered in cold-chain conditions where available.',
        'Please inspect the package promptly on delivery.',
        'Perishable and frozen items may have limited return eligibility.',
        'Contact support quickly if you receive a damaged or incorrect item.',
    ];

    public static function defaultStorageGuidanceText(): string
    {
        return implode(PHP_EOL, self::DEFAULT_STORAGE_GUIDANCE);
    }

    public static function defaultDeliverySupportText(): string
    {
        return implode(PHP_EOL, self::DEFAULT_DELIVERY_SUPPORT);
    }

    protected $fillable = [
        'name',
        'slug',
        'barcode',
        'sku',
        'type',
        'inventory_role',
        'short_description',
        'description',
        'storage_guidance',
        'delivery_support',
        'primary_image',
        'manage_stock',
        'stock_quantity',
        'low_stock_threshold',
        'min_order_quantity',
        'b2c_price_includes_gst',
        'b2b_price_includes_gst',
        'gst_rate',
        'base_price',
        'mrp_price',
        'dynamic_pricing_enabled',
        'is_active',
        'is_featured',
        'is_new',
        'is_special',
        'special_audience',
        'special_price',
        'standard_b2b_price',
        'standard_b2b_min_order_quantity',
        'special_starts_at',
        'special_ends_at',
        'vendor_id',
        'meta_title',
        'meta_description',
        'created_by_id',
        'updated_by_id',
        'sell_unit',
        'pack_type',
        'hsn_code_id',
        'product_weight',
        'pieces_per_pack',
        'pricing_unit',
        'country_of_origin',
        'lot_stage_default',
        'inventory_is_saleable',
        'inventory_can_repack',
    ];


    protected $casts = [
        'manage_stock'            => 'bool',
        'dynamic_pricing_enabled' => 'bool',
        'is_active'               => 'bool',
        'is_featured'             => 'bool',
        'is_new'                  => 'bool',
        'is_special'              => 'bool',
        'stock_quantity'          => 'float',
        'low_stock_threshold'     => 'float',
        'min_order_quantity'      => 'float',
        'b2c_price_includes_gst' => 'boolean',
        'b2b_price_includes_gst' => 'boolean',
        'gst_rate' => 'decimal:2',
        'base_price'              => 'float',
        'mrp_price'               => 'float',
        'special_price'           => 'float',
        'standard_b2b_price'      => 'float',
        'standard_b2b_min_order_quantity' => 'float',
        'special_audience'        => 'string',
        'special_starts_at'       => 'datetime',
        'special_ends_at'         => 'datetime',
        'product_weight'          => 'decimal:3',
        'pieces_per_pack'         => 'decimal:3',
        'sell_unit'               => 'string',
        'pack_type'               => 'string',
        'inventory_role'          => 'string',
        'pricing_unit'            => 'string',
        'lot_stage_default'        => 'string',
        'inventory_is_saleable'    => 'bool',
        'inventory_can_repack'     => 'bool',
    ];


    public function storageGuidanceLines(): array
    {
        return $this->multilineListFromAttribute('storage_guidance', self::DEFAULT_STORAGE_GUIDANCE);
    }

    public function deliverySupportLines(): array
    {
        return $this->multilineListFromAttribute('delivery_support', self::DEFAULT_DELIVERY_SUPPORT);
    }

    protected function multilineListFromAttribute(string $attribute, array $fallback): array
    {
        $raw = trim((string) ($this->{$attribute} ?? ''));

        if ($raw === '') {
            return $fallback;
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        $items = collect($lines)
            ->map(function ($line) {
                $line = trim((string) $line);
                $line = preg_replace('/^\s*(?:[-*•]|\d+[.)])\s*/u', '', $line) ?? $line;

                return trim($line);
            })
            ->filter()
            ->values()
            ->all();

        return $items ?: $fallback;
    }


    public function categories()
    {
        // category_product pivot
        return $this->belongsToMany(Category::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function primaryVendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function vendorInvoices()
    {
        return $this->hasManyThrough(
            VendorInvoice::class,     // final model
            VendorInvoiceItem::class, // intermediate
            'product_id',             // FK on vendor_invoice_items -> product
            'id',                     // local key on vendor_invoices
            'id',                     // local key on products
            'vendor_invoice_id'       // FK on vendor_invoice_items -> vendor_invoices
        );
    }

    public function vendorInvoiceItems()
    {
        return $this->hasMany(VendorInvoiceItem::class, 'product_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function sellUnits()
    {
        return $this->hasMany(ProductSellUnit::class)->orderBy('sort_order')->orderBy('name');
    }

        public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'product_recipe')->withTimestamps();
    }

    public function activeRecipes()
    {
        return $this->belongsToMany(Recipe::class, 'product_recipe')
            ->where('recipes.is_active', true)
            ->orderBy('recipes.sort_order')
            ->orderBy('recipes.title')
            ->withTimestamps();
    }
    
    public function supplierVendors()
    {
        $vendorIds = $this->vendorInvoices()
            ->pluck('vendor_id')
            ->filter()
            ->unique()
            ->values();

        if ($vendorIds->isEmpty()) {
            return collect();
        }

        return Vendor::whereIn('id', $vendorIds)->orderBy('name')->get();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values', 'product_id', 'attribute_value_id')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute_values', 'product_id', 'attribute_id')
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    // Query scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->active()->where('is_featured', true);
    }

    public function scopeNew($query)
    {
        return $query->active()
            ->where('is_new', true)
            ->latest();
    }

    public function scopeSpecial($query)
    {
        $now = now();

        return $query->active()
            ->where('is_special', true)
            ->whereIn('special_audience', ['b2c', 'all'])
            ->where(function ($q) use ($now) {
                $q->whereNull('special_starts_at')
                ->orWhere('special_starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('special_ends_at')
                ->orWhere('special_ends_at', '>=', $now);
            });
    }

    // Helper: current effective price (special if active)
    public function getEffectivePriceAttribute(): float
    {
        // Determine active price
        $price = (float) $this->base_price;

        if ($this->is_special && $this->special_price !== null) {
            $now = now();
            $audience = (string) ($this->special_audience ?? 'b2c');

            $active =
                in_array($audience, ['b2c', 'all'], true) &&
                ($this->special_starts_at === null || $this->special_starts_at->lte($now)) &&
                ($this->special_ends_at === null || $this->special_ends_at->gte($now));

            if ($active) {
                $price = (float) $this->special_price;
            }
        }

        // Product/base prices are stored ex-GST. B2C display is GST-inclusive
        // by default, while B2B display remains ex-GST unless configured otherwise.
        if (($this->b2c_price_includes_gst ?? true) && $this->gst_rate > 0) {
            $price += $price * ($this->gst_rate / 100);
        }

        return round($price, 2);
    }

    public function getPriceWithGstAttribute(): float
    {
        $base = (float) ($this->base_price ?? 0);
        $rate = (float) $this->effective_gst_rate;

        return round($base * (1 + ($rate / 100)), 2);
    }

    public function getGstAmountOnBasePriceAttribute(): float
    {
        return round($this->price_with_gst - (float)$this->base_price, 2);
    }

    public function hsnCode()
    {
        return $this->belongsTo(\App\Models\HsnCode::class);
    }

    public function getEffectiveGstRateAttribute(): float
    {
        return app(\App\Services\GstRateService::class)->rateForProduct($this);
    }

    public function b2bAssignments()
    {
        return $this->hasMany(\App\Models\B2BCustomerProduct::class, 'product_id');
    }

    public function scopeVisibleTo($query, ?\App\Models\User $user)
    {
        // B2B customers can discover the full active catalog. Purchase access is
        // enforced separately by B2BTermsService::canBuy() in cart/checkout.
        return $query;
    }

    public function unitLabel(): string
    {
        return match ($this->sell_unit) {
            'kg'   => 'kg',
            'pack' => 'pack',
            default => 'pc',
        };
    }
    
    // public function translations()
    // {
    //     return $this->hasMany(ProductTranslation::class);
    // }

    public function collections()
    {
        return $this->belongsToMany(\App\Models\ProductCollection::class, 'product_collection_product')
            ->withPivot(['sort_order', 'is_featured'])
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::created(function (Product $product) {
            app(\App\Services\ProductCodeService::class)->assignMissingCodes($product);
        });
    }



}

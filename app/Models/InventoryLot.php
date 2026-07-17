<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class InventoryLot extends Model
{
    protected $fillable = [
        'lot_code',
        'product_id',
        'product_variant_id',
        'vendor_id',
        'vendor_invoice_id',
        'vendor_invoice_item_id',
        'production_run_id',
        'parent_inventory_lot_id',
        'root_inventory_lot_id',
        'lot_stage',
        'inward_mode',
        'is_saleable',
        'can_repack',
        'lot_status',
        'batch_code',
        'mfg_date',
        'packed_date',
        'expiry_date',
        'received_date',
        'received_quantity',
        'available_quantity',
        'unit_weight_kg',
        'total_weight_kg',
        'available_weight_kg',
        'piece_count',
        'available_piece_count',
        'pack_size_kg',
        'pack_count',
        'available_pack_count',
        'pieces_per_pack',
        'unit_cost',
        'cost_per_kg',
        'total_cost',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_saleable' => 'boolean',
        'can_repack' => 'boolean',
        'mfg_date' => 'date',
        'packed_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'received_quantity' => 'decimal:3',
        'available_quantity' => 'decimal:3',
        'unit_weight_kg' => 'decimal:3',
        'total_weight_kg' => 'decimal:3',
        'available_weight_kg' => 'decimal:3',
        'pack_size_kg' => 'decimal:3',
        'pack_count' => 'integer',
        'available_pack_count' => 'integer',
        'pieces_per_pack' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }


    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorInvoice()
    {
        return $this->belongsTo(VendorInvoice::class);
    }

    public function vendorInvoiceItem()
    {
        return $this->belongsTo(VendorInvoiceItem::class);
    }

    public function productionRun()
    {
        return $this->belongsTo(ProductionRun::class);
    }

    public function parentLot()
    {
        return $this->belongsTo(self::class, 'parent_inventory_lot_id');
    }

    public function rootLot()
    {
        return $this->belongsTo(self::class, 'root_inventory_lot_id');
    }

    public function pieces()
    {
        return $this->hasMany(InventoryPiece::class);
    }

    public function packs()
    {
        return $this->hasMany(InventoryPack::class, 'source_inventory_lot_id');
    }

    public function scopeAvailableForRepack($query)
    {
        return $query->where('lot_status', 'available')
            ->where(function ($eligibility) {
                // Product-level stock may inherit the product's repack setting.
                $eligibility->where(function ($productLevel) {
                    $productLevel->whereNull('product_variant_id')
                        ->where(function ($allowed) {
                            $allowed->where('can_repack', true)
                                ->orWhereHas('product', function ($products) {
                                    $products->where('inventory_can_repack', true)
                                        ->orWhere('inventory_role', 'internal');
                                });
                        });
                });

                // Once a lot belongs to a variant, that variant is authoritative.
                // This prevents finished 10pc/20pc output variants from appearing as
                // source cartons merely because their parent product can be repacked.
                if (Schema::hasColumn('product_variants', 'inventory_can_repack')) {
                    $eligibility->orWhere(function ($variantLevel) {
                        $variantLevel->whereNotNull('product_variant_id')
                            ->where(function ($allowed) {
                                $allowed->whereHas('productVariant', function ($variants) {
                                    $variants->where('inventory_can_repack', true);
                                })->orWhereHas('product', function ($products) {
                                    $products->where('inventory_role', 'internal');
                                })->orWhere(function ($rawStock) {
                                    $rawStock->where('can_repack', true)
                                        ->whereIn('inward_mode', ['pieces', 'pieces_weight', 'bulk_weight']);
                                });
                            });
                    });
                } else {
                    $eligibility->orWhere(function ($legacyVariantLevel) {
                        $legacyVariantLevel->whereNotNull('product_variant_id')
                            ->where(function ($allowed) {
                                $allowed->where('can_repack', true)
                                    ->orWhereHas('product', function ($products) {
                                        $products->where('inventory_can_repack', true)
                                            ->orWhere('inventory_role', 'internal');
                                    });
                            });
                    });
                }
            })
            ->where(function ($q) {
                $q->where('available_weight_kg', '>', 0)
                  ->orWhere('available_quantity', '>', 0)
                  ->orWhere('available_piece_count', '>', 0);
            });
    }

    public function isRepackable(): bool
    {
        $product = $this->relationLoaded('product') ? $this->product : $this->product()->first();

        if ($this->product_variant_id !== null && Schema::hasColumn('product_variants', 'inventory_can_repack')) {
            $variant = $this->relationLoaded('productVariant') ? $this->productVariant : $this->productVariant()->first();

            if ((bool) ($variant?->inventory_can_repack ?? false)) {
                return true;
            }

            if ((string) ($product?->inventory_role ?? '') === 'internal') {
                return true;
            }

            return (bool) $this->can_repack
                && in_array((string) ($this->inward_mode ?? ''), ['pieces', 'pieces_weight', 'bulk_weight'], true);
        }

        if ((bool) $this->can_repack) {
            return true;
        }

        return $product
            && ((bool) ($product->inventory_can_repack ?? false)
                || (string) ($product->inventory_role ?? '') === 'internal');
    }
}

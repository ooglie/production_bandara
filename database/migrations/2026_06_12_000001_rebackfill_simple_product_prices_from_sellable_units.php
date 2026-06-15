<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('product_sell_units')) {
            return;
        }

        foreach (['product_id', 'base_price', 'mrp_price', 'weight_per_unit_kg'] as $column) {
            if (! Schema::hasColumn('product_sell_units', $column)) {
                return;
            }
        }

        $sellUnitsByProduct = DB::table('product_sell_units')
            ->when(Schema::hasColumn('product_sell_units', 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        foreach ($sellUnitsByProduct as $productId => $sellUnits) {
            // The unchanged storefront can display one product-level price only.
            // Auto-copy only when there is exactly one sellable format.
            if ($sellUnits->count() !== 1) {
                continue;
            }

            $sellUnit = $sellUnits->first();
            $product = DB::table('products')->where('id', $productId)->first();

            if (! $product) {
                continue;
            }

            $updates = [];

            if (Schema::hasColumn('products', 'base_price')
                && (float) ($product->base_price ?? 0) <= 0
                && (float) ($sellUnit->base_price ?? 0) > 0) {
                $updates['base_price'] = (float) $sellUnit->base_price;
            }

            if (Schema::hasColumn('products', 'mrp_price')
                && (float) ($product->mrp_price ?? 0) <= 0
                && (float) ($sellUnit->mrp_price ?? 0) > 0) {
                $updates['mrp_price'] = (float) $sellUnit->mrp_price;
            }

            if (($updates['base_price'] ?? null) !== null || ($updates['mrp_price'] ?? null) !== null) {
                if (Schema::hasColumn('products', 'b2c_price_includes_gst') && Schema::hasColumn('product_sell_units', 'b2c_price_includes_gst')) {
                    $updates['b2c_price_includes_gst'] = (bool) ($sellUnit->b2c_price_includes_gst ?? true);
                }
            }

            if (Schema::hasColumn('products', 'standard_b2b_price')
                && (float) ($product->standard_b2b_price ?? 0) <= 0
                && (float) ($sellUnit->standard_b2b_price ?? 0) > 0) {
                $updates['standard_b2b_price'] = (float) $sellUnit->standard_b2b_price;
            }

            if (Schema::hasColumn('products', 'standard_b2b_min_order_quantity')
                && (float) ($product->standard_b2b_min_order_quantity ?? 0) <= 0
                && (float) ($sellUnit->standard_b2b_min_order_quantity ?? 0) > 0) {
                $updates['standard_b2b_min_order_quantity'] = (float) $sellUnit->standard_b2b_min_order_quantity;
            }

            if (Schema::hasColumn('products', 'product_weight')
                && (float) ($product->product_weight ?? 0) <= 0
                && (float) ($sellUnit->weight_per_unit_kg ?? 0) > 0) {
                $updates['product_weight'] = (float) $sellUnit->weight_per_unit_kg;
            }

            if (Schema::hasColumn('products', 'sell_unit')) {
                $currentSellUnit = (string) ($product->sell_unit ?? '');
                $suggestedSellUnit = (string) ($sellUnit->sale_type ?? '') === 'variable_weight' ? 'kg' : 'pack';

                if (in_array($currentSellUnit, ['', 'piece'], true)) {
                    $updates['sell_unit'] = $suggestedSellUnit;
                }
            }

            if (Schema::hasColumn('products', 'type') && (string) ($product->type ?? 'simple') === 'variable') {
                $hasVisibleVariants = false;

                if (Schema::hasTable('product_variants')) {
                    $variantQuery = DB::table('product_variants')
                        ->where('product_id', $productId);

                    if (Schema::hasColumn('product_variants', 'deleted_at')) {
                        $variantQuery->whereNull('deleted_at');
                    }

                    if (Schema::hasColumn('product_variants', 'is_active')) {
                        $variantQuery->where('is_active', true);
                    }

                    $hasVisibleVariants = $variantQuery->exists();
                }

                if (! $hasVisibleVariants) {
                    $updates['type'] = 'simple';
                }
            }

            if ($updates !== []) {
                if (Schema::hasColumn('products', 'updated_at')) {
                    $updates['updated_at'] = now();
                }

                DB::table('products')
                    ->where('id', $productId)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // No-op. This is an idempotent repair pass that fills blank product-level
        // prices from a product's one sellable format so the unchanged frontend
        // can keep displaying product prices normally.
    }
};

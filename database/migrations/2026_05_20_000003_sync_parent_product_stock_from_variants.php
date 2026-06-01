<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('products')
            || ! Schema::hasTable('product_variants')
            || ! Schema::hasColumn('products', 'stock_quantity')
            || ! Schema::hasColumn('product_variants', 'stock_quantity')
        ) {
            return;
        }

        $hasVariantDeletedAt = Schema::hasColumn('product_variants', 'deleted_at');
        $hasProductUpdatedAt = Schema::hasColumn('products', 'updated_at');

        $productIds = DB::table('products')
            ->select('products.id')
            ->whereExists(function ($query) use ($hasVariantDeletedAt) {
                $query->selectRaw('1')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id');

                if ($hasVariantDeletedAt) {
                    $query->whereNull('product_variants.deleted_at');
                }
            })
            ->orderBy('products.id')
            ->pluck('products.id');

        foreach ($productIds as $productId) {
            $variantQuery = DB::table('product_variants')
                ->where('product_id', $productId);

            if ($hasVariantDeletedAt) {
                $variantQuery->whereNull('deleted_at');
            }

            $variantStock = round((float) $variantQuery->sum('stock_quantity'), 3);

            $update = ['stock_quantity' => $variantStock];

            if ($hasProductUpdatedAt) {
                $update['updated_at'] = now();
            }

            DB::table('products')
                ->where('id', $productId)
                ->update($update);
        }
    }

    public function down(): void
    {
        // No rollback. This is a data correction to keep parent product stock
        // aligned to the sum of its non-deleted variants.
    }
};

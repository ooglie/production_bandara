<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repairOrders();
        $this->repairLineItemColumns();
        $this->repairCoupons();
        $this->repairMissingTables();
    }

    private function repairOrders(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'printed_at')) {
                $table->timestamp('printed_at')->nullable()->index();
            }
            if (! Schema::hasColumn('orders', 'printed_by_id')) {
                $table->unsignedBigInteger('printed_by_id')->nullable()->index();
            }
        });
    }

    private function repairLineItemColumns(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'sell_unit')) {
                    $table->string('sell_unit', 20)->default('piece');
                }
                if (! Schema::hasColumn('products', 'product_weight')) {
                    $table->decimal('product_weight', 10, 3)->nullable();
                }
            });
        }

        if (Schema::hasTable('cart_items') && ! Schema::hasColumn('cart_items', 'item_weight')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->decimal('item_weight', 10, 3)->nullable();
            });
        }

        foreach (['order_items', 'invoice_items'] as $lineTable) {
            if (! Schema::hasTable($lineTable)) {
                continue;
            }

            Schema::table($lineTable, function (Blueprint $table) use ($lineTable) {
                if (! Schema::hasColumn($lineTable, 'sell_unit')) {
                    $table->string('sell_unit', 20)->nullable();
                }
                if (! Schema::hasColumn($lineTable, 'item_weight')) {
                    $table->decimal('item_weight', 10, 3)->nullable();
                }
                if (! Schema::hasColumn($lineTable, 'pricing_unit')) {
                    $table->string('pricing_unit', 20)->default('pack');
                }
            });
        }
    }

    private function repairCoupons(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0);
            }
            if (! Schema::hasColumn('coupons', 'usage_limit_per_user')) {
                $table->unsignedInteger('usage_limit_per_user')->nullable();
            }
            if (! Schema::hasColumn('coupons', 'ends_at')) {
                $table->dateTime('ends_at')->nullable();
            }
        });

        if (Schema::hasColumn('coupons', 'used_count') && Schema::hasColumn('coupons', 'usage_count')) {
            DB::statement('UPDATE coupons SET usage_count = used_count WHERE usage_count = 0 AND used_count IS NOT NULL');
        }
    }

    private function repairMissingTables(): void
    {
        if (! Schema::hasTable('inventory_packs')
            && Schema::hasTable('production_runs')
            && Schema::hasTable('products')
            && Schema::hasTable('inventory_lots')
            && Schema::hasTable('inventory_pieces')) {
            Schema::create('inventory_packs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('production_run_id');
                $table->unsignedBigInteger('source_inventory_lot_id')->nullable();
                $table->unsignedBigInteger('source_inventory_piece_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('product_variant_id')->nullable();
                $table->decimal('sold_weight_kg', 10, 3)->nullable();
                $table->decimal('actual_weight_kg', 10, 3)->nullable();
                $table->date('packed_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('batch_code', 80)->nullable();
                $table->string('status', 30)->default('available');
                $table->dateTime('reserved_until')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'product_variant_id']);
                $table->index('status');
                $table->index('expiry_date');
            });
        }

        if (! Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->unique();
                $table->string('name');
                $table->string('native_name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('auto_translate')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            DB::table('languages')->insertOrIgnore([
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'is_default' => true,
                'auto_translate' => false,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->json('title');
                $table->json('slug')->nullable();
                $table->json('excerpt')->nullable();
                $table->json('content')->nullable();
                $table->json('meta_title')->nullable();
                $table->json('meta_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('product_translations') && Schema::hasTable('products')) {
            Schema::create('product_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->string('locale', 10);
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['product_id', 'locale']);
                $table->index('locale');
            });
        }
    }

    public function down(): void
    {
        // Intentionally no-op. This migration repairs imported handoff databases
        // where migration metadata and SQL dump schema do not perfectly align.
    }
};

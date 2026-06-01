<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureInventoryLotsColumns();
        $this->ensureInventoryPacksTable();
        $this->ensureLanguageAndPageTables();
        $this->ensureProductTranslationsTable();
        $this->normalizeCouponsTable();
    }

    public function down(): void
    {
        // Intentionally no destructive rollback: this migration repairs schema gaps in imported handoff dumps.
    }

    private function ensureInventoryLotsColumns(): void
    {
        if (! Schema::hasTable('inventory_lots')) {
            return;
        }

        Schema::table('inventory_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_lots', 'consumed_quantity')) {
                $table->decimal('consumed_quantity', 12, 3)->default(0)->after('received_quantity');
            }

            if (! Schema::hasColumn('inventory_lots', 'consumed_weight_kg')) {
                $table->decimal('consumed_weight_kg', 12, 3)->default(0)->after('consumed_quantity');
            }
        });
    }

    private function ensureInventoryPacksTable(): void
    {
        if (Schema::hasTable('inventory_packs')) {
            return;
        }

        Schema::create('inventory_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_run_id')->constrained('production_runs')->cascadeOnDelete();
            $table->foreignId('source_inventory_lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('source_inventory_piece_id')->nullable()->constrained('inventory_pieces')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
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

    private function ensureLanguageAndPageTables(): void
    {
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
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function ensureProductTranslationsTable(): void
    {
        if (Schema::hasTable('product_translations')) {
            return;
        }

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale']);
        });
    }

    private function normalizeCouponsTable(): void
    {
        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->enum('discount_type', ['flat', 'percent']);
                $table->decimal('discount_value', 10, 2);
                $table->decimal('max_discount_amount', 10, 2)->nullable();
                $table->decimal('min_order_amount', 10, 2)->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('usage_limit_per_user')->nullable();
                $table->unsignedInteger('usage_count')->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'name')) {
                $table->string('name')->nullable()->after('code');
            }

            if (! Schema::hasColumn('coupons', 'usage_limit_per_user')) {
                $table->unsignedInteger('usage_limit_per_user')->nullable()->after('usage_limit');
            }

            if (! Schema::hasColumn('coupons', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0)->after('usage_limit_per_user');
            }

            if (! Schema::hasColumn('coupons', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            }

            if (! Schema::hasColumn('coupons', 'updated_by_id')) {
                $table->foreignId('updated_by_id')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('coupons', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (Schema::hasColumn('coupons', 'per_user_limit') && Schema::hasColumn('coupons', 'usage_limit_per_user')) {
            DB::table('coupons')
                ->whereNull('usage_limit_per_user')
                ->whereNotNull('per_user_limit')
                ->update(['usage_limit_per_user' => DB::raw('per_user_limit')]);
        }

        if (Schema::hasColumn('coupons', 'used_count') && Schema::hasColumn('coupons', 'usage_count')) {
            DB::table('coupons')
                ->where('usage_count', 0)
                ->where('used_count', '>', 0)
                ->update(['usage_count' => DB::raw('used_count')]);
        }

        if (Schema::hasColumn('coupons', 'expires_at') && Schema::hasColumn('coupons', 'ends_at')) {
            DB::table('coupons')
                ->whereNull('ends_at')
                ->whereNotNull('expires_at')
                ->update(['ends_at' => DB::raw('expires_at')]);
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE coupons MODIFY discount_type ENUM('flat','fixed','percent') NOT NULL");
            DB::table('coupons')->where('discount_type', 'fixed')->update(['discount_type' => 'flat']);
            DB::statement("ALTER TABLE coupons MODIFY discount_type ENUM('flat','percent') NOT NULL");
            DB::statement('ALTER TABLE coupons MODIFY updated_by_id BIGINT UNSIGNED NULL');
        }
    }
};

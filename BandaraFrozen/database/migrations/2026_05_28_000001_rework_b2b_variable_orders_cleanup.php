<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove the temporary B2B request/allocation module tables. The new flow
        // uses normal B2B cart/order records and a pending-weight finalization queue.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('b2b_order_item_allocations');
        Schema::dropIfExists('b2b_order_request_items');
        Schema::dropIfExists('b2b_order_requests');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                if (! Schema::hasColumn('cart_items', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')->nullable()->after('product_variant_id')->constrained('product_sell_units')->nullOnDelete();
                }
                if (! Schema::hasColumn('cart_items', 'b2b_order_mode')) {
                    $table->string('b2b_order_mode', 32)->nullable()->after('item_weight')->index();
                }
                if (! Schema::hasColumn('cart_items', 'requested_piece_count')) {
                    $table->unsignedInteger('requested_piece_count')->nullable()->after('b2b_order_mode');
                }
                if (! Schema::hasColumn('cart_items', 'requested_weight_kg')) {
                    $table->decimal('requested_weight_kg', 10, 3)->nullable()->after('requested_piece_count');
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')->nullable()->after('product_variant_id')->constrained('product_sell_units')->nullOnDelete();
                }
                if (! Schema::hasColumn('order_items', 'b2b_order_mode')) {
                    $table->string('b2b_order_mode', 32)->nullable()->after('attributes_snapshot')->index();
                }
                if (! Schema::hasColumn('order_items', 'requested_piece_count')) {
                    $table->unsignedInteger('requested_piece_count')->nullable()->after('b2b_order_mode');
                }
                if (! Schema::hasColumn('order_items', 'requested_weight_kg')) {
                    $table->decimal('requested_weight_kg', 10, 3)->nullable()->after('requested_piece_count');
                }
                if (! Schema::hasColumn('order_items', 'actual_weight_kg')) {
                    $table->decimal('actual_weight_kg', 10, 3)->nullable()->after('requested_weight_kg');
                }
                if (! Schema::hasColumn('order_items', 'weight_finalized_by_id')) {
                    $table->foreignId('weight_finalized_by_id')->nullable()->after('actual_weight_kg')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('order_items', 'weight_finalized_at')) {
                    $table->timestamp('weight_finalized_at')->nullable()->after('weight_finalized_by_id');
                }
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('invoice_items', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')->nullable()->after('order_item_id')->constrained('product_sell_units')->nullOnDelete();
                }
                if (! Schema::hasColumn('invoice_items', 'b2b_order_mode')) {
                    $table->string('b2b_order_mode', 32)->nullable()->after('product_sell_unit_id')->index();
                }
                if (! Schema::hasColumn('invoice_items', 'requested_piece_count')) {
                    $table->unsignedInteger('requested_piece_count')->nullable()->after('b2b_order_mode');
                }
                if (! Schema::hasColumn('invoice_items', 'requested_weight_kg')) {
                    $table->decimal('requested_weight_kg', 10, 3)->nullable()->after('requested_piece_count');
                }
                if (! Schema::hasColumn('invoice_items', 'actual_weight_kg')) {
                    $table->decimal('actual_weight_kg', 10, 3)->nullable()->after('requested_weight_kg');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'requires_weight_finalization')) {
                    $table->boolean('requires_weight_finalization')->default(false)->after('status')->index();
                }
                if (! Schema::hasColumn('invoices', 'weight_finalized_by_id')) {
                    $table->foreignId('weight_finalized_by_id')->nullable()->after('requires_weight_finalization')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('invoices', 'weight_finalized_at')) {
                    $table->timestamp('weight_finalized_at')->nullable()->after('weight_finalized_by_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['invoices' => ['weight_finalized_at', 'weight_finalized_by_id', 'requires_weight_finalization']] as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                    foreach ($columns as $column) {
                        if (! Schema::hasColumn($tableName, $column)) {
                            continue;
                        }
                        if (str_ends_with($column, '_id')) {
                            $table->dropConstrainedForeignId($column);
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                });
            }
        }

        foreach (['invoice_items', 'order_items', 'cart_items'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach (['actual_weight_kg', 'requested_weight_kg', 'requested_piece_count', 'b2b_order_mode'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }

                if (Schema::hasColumn($tableName, 'weight_finalized_at')) {
                    $table->dropColumn('weight_finalized_at');
                }
                if (Schema::hasColumn($tableName, 'weight_finalized_by_id')) {
                    $table->dropConstrainedForeignId('weight_finalized_by_id');
                }
                if (Schema::hasColumn($tableName, 'product_sell_unit_id')) {
                    $table->dropConstrainedForeignId('product_sell_unit_id');
                }
            });
        }
    }
};

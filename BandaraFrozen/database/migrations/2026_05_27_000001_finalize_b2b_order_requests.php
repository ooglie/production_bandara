<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('b2b_order_requests')) {
            Schema::table('b2b_order_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('b2b_order_requests', 'finalized_order_id')) {
                    $table->foreignId('finalized_order_id')->nullable()->after('allocated_at')->constrained('orders')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_requests', 'finalized_invoice_id')) {
                    $table->foreignId('finalized_invoice_id')->nullable()->after('finalized_order_id')->constrained('invoices')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_requests', 'finalized_by_id')) {
                    $table->foreignId('finalized_by_id')->nullable()->after('finalized_invoice_id')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_requests', 'finalized_at')) {
                    $table->timestamp('finalized_at')->nullable()->after('finalized_by_id');
                }
            });
        }

        if (Schema::hasTable('b2b_order_request_items')) {
            Schema::table('b2b_order_request_items', function (Blueprint $table) {
                if (! Schema::hasColumn('b2b_order_request_items', 'finalized_order_item_id')) {
                    $table->foreignId('finalized_order_item_id')->nullable()->after('allocated_at')->constrained('order_items')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_request_items', 'finalized_invoice_item_id')) {
                    $table->foreignId('finalized_invoice_item_id')->nullable()->after('finalized_order_item_id')->constrained('invoice_items')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_request_items', 'finalized_at')) {
                    $table->timestamp('finalized_at')->nullable()->after('finalized_invoice_item_id');
                }
            });
        }

        if (Schema::hasTable('b2b_order_item_allocations')) {
            Schema::table('b2b_order_item_allocations', function (Blueprint $table) {
                if (! Schema::hasColumn('b2b_order_item_allocations', 'sold_order_id')) {
                    $table->foreignId('sold_order_id')->nullable()->after('notes')->constrained('orders')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_item_allocations', 'sold_order_item_id')) {
                    $table->foreignId('sold_order_item_id')->nullable()->after('sold_order_id')->constrained('order_items')->nullOnDelete();
                }
                if (! Schema::hasColumn('b2b_order_item_allocations', 'sold_at')) {
                    $table->timestamp('sold_at')->nullable()->after('sold_order_item_id');
                }
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')->nullable()->after('product_variant_id')->constrained('product_sell_units')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (! Schema::hasColumn('invoice_items', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')->nullable()->after('order_item_id')->constrained('product_sell_units')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'product_sell_unit_id')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_sell_unit_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }

        if (Schema::hasTable('b2b_order_item_allocations')) {
            Schema::table('b2b_order_item_allocations', function (Blueprint $table) {
                if (Schema::hasColumn('b2b_order_item_allocations', 'sold_at')) {
                    $table->dropColumn('sold_at');
                }
                if (Schema::hasColumn('b2b_order_item_allocations', 'sold_order_item_id')) {
                    $table->dropConstrainedForeignId('sold_order_item_id');
                }
                if (Schema::hasColumn('b2b_order_item_allocations', 'sold_order_id')) {
                    $table->dropConstrainedForeignId('sold_order_id');
                }
            });
        }

        if (Schema::hasTable('b2b_order_request_items')) {
            Schema::table('b2b_order_request_items', function (Blueprint $table) {
                if (Schema::hasColumn('b2b_order_request_items', 'finalized_at')) {
                    $table->dropColumn('finalized_at');
                }
                if (Schema::hasColumn('b2b_order_request_items', 'finalized_invoice_item_id')) {
                    $table->dropConstrainedForeignId('finalized_invoice_item_id');
                }
                if (Schema::hasColumn('b2b_order_request_items', 'finalized_order_item_id')) {
                    $table->dropConstrainedForeignId('finalized_order_item_id');
                }
            });
        }

        if (Schema::hasTable('b2b_order_requests')) {
            Schema::table('b2b_order_requests', function (Blueprint $table) {
                if (Schema::hasColumn('b2b_order_requests', 'finalized_at')) {
                    $table->dropColumn('finalized_at');
                }
                if (Schema::hasColumn('b2b_order_requests', 'finalized_by_id')) {
                    $table->dropConstrainedForeignId('finalized_by_id');
                }
                if (Schema::hasColumn('b2b_order_requests', 'finalized_invoice_id')) {
                    $table->dropConstrainedForeignId('finalized_invoice_id');
                }
                if (Schema::hasColumn('b2b_order_requests', 'finalized_order_id')) {
                    $table->dropConstrainedForeignId('finalized_order_id');
                }
            });
        }
    }
};

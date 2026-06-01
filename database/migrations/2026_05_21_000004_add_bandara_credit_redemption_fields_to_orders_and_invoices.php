<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'bandara_credit_redeemed_points')) {
                    $table->unsignedInteger('bandara_credit_redeemed_points')->default(0)->after('discount_total');
                }

                if (! Schema::hasColumn('orders', 'bandara_credit_redeemed_amount')) {
                    $table->decimal('bandara_credit_redeemed_amount', 12, 2)->default(0)->after('bandara_credit_redeemed_points');
                }

                // Compatibility names used by older admin order views.
                if (! Schema::hasColumn('orders', 'bandara_credit_points_redeemed')) {
                    $table->unsignedInteger('bandara_credit_points_redeemed')->default(0)->after('bandara_credit_redeemed_amount');
                }

                if (! Schema::hasColumn('orders', 'bandara_credit_discount_total')) {
                    $table->decimal('bandara_credit_discount_total', 12, 2)->default(0)->after('bandara_credit_points_redeemed');
                }

                if (! Schema::hasColumn('orders', 'bandara_credit_order_total_before_redemption')) {
                    $table->decimal('bandara_credit_order_total_before_redemption', 12, 2)->nullable()->after('bandara_credit_discount_total');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'bandara_credit_redeemed_points')) {
                    $table->unsignedInteger('bandara_credit_redeemed_points')->default(0)->after('discount_total');
                }

                if (! Schema::hasColumn('invoices', 'bandara_credit_redeemed_amount')) {
                    $table->decimal('bandara_credit_redeemed_amount', 12, 2)->default(0)->after('bandara_credit_redeemed_points');
                }

                if (! Schema::hasColumn('invoices', 'bandara_credit_points_redeemed')) {
                    $table->unsignedInteger('bandara_credit_points_redeemed')->default(0)->after('bandara_credit_redeemed_amount');
                }

                if (! Schema::hasColumn('invoices', 'bandara_credit_discount_total')) {
                    $table->decimal('bandara_credit_discount_total', 12, 2)->default(0)->after('bandara_credit_points_redeemed');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                foreach ([
                    'bandara_credit_discount_total',
                    'bandara_credit_points_redeemed',
                    'bandara_credit_redeemed_amount',
                    'bandara_credit_redeemed_points',
                ] as $column) {
                    if (Schema::hasColumn('invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach ([
                    'bandara_credit_order_total_before_redemption',
                    'bandara_credit_discount_total',
                    'bandara_credit_points_redeemed',
                    'bandara_credit_redeemed_amount',
                    'bandara_credit_redeemed_points',
                ] as $column) {
                    if (Schema::hasColumn('orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

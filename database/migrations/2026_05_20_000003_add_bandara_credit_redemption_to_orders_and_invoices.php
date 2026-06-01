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
                    $table->unsignedInteger('bandara_credit_redeemed_points')->default(0)->after('shipping_total');
                }

                if (! Schema::hasColumn('orders', 'bandara_credit_redeemed_amount')) {
                    $table->decimal('bandara_credit_redeemed_amount', 10, 2)->default(0)->after('bandara_credit_redeemed_points');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'bandara_credit_redeemed_points')) {
                    $table->unsignedInteger('bandara_credit_redeemed_points')->default(0)->after('discount_total');
                }

                if (! Schema::hasColumn('invoices', 'bandara_credit_redeemed_amount')) {
                    $table->decimal('bandara_credit_redeemed_amount', 10, 2)->default(0)->after('bandara_credit_redeemed_points');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'bandara_credit_redeemed_amount')) {
                    $table->dropColumn('bandara_credit_redeemed_amount');
                }

                if (Schema::hasColumn('invoices', 'bandara_credit_redeemed_points')) {
                    $table->dropColumn('bandara_credit_redeemed_points');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'bandara_credit_redeemed_amount')) {
                    $table->dropColumn('bandara_credit_redeemed_amount');
                }

                if (Schema::hasColumn('orders', 'bandara_credit_redeemed_points')) {
                    $table->dropColumn('bandara_credit_redeemed_points');
                }
            });
        }
    }
};

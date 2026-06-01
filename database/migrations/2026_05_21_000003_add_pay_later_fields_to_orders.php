<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 32)->default('razorpay')->after('payment_status')->index();
            }

            if (! Schema::hasColumn('orders', 'payment_due_at')) {
                $table->dateTime('payment_due_at')->nullable()->after('payment_method')->index();
            }

            if (! Schema::hasColumn('orders', 'payment_terms_days')) {
                $table->unsignedSmallInteger('payment_terms_days')->nullable()->after('payment_due_at');
            }

            if (! Schema::hasColumn('orders', 'pay_later_approved_at')) {
                $table->timestamp('pay_later_approved_at')->nullable()->after('payment_terms_days');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            foreach (['pay_later_approved_at', 'payment_terms_days', 'payment_due_at', 'payment_method'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

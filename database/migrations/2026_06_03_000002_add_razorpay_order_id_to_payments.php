<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'razorpay_order_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->string('razorpay_order_id')
                ->nullable()
                ->after('transaction_id')
                ->index('payments_razorpay_order_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'razorpay_order_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_razorpay_order_id_index');
            $table->dropColumn('razorpay_order_id');
        });
    }
};

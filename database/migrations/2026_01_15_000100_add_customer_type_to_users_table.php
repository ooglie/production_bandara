<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Default is B2C so existing storefront behavior remains unchanged.
            if (!Schema::hasColumn('users', 'customer_type')) {
                $table->enum('customer_type', ['b2c', 'b2b'])
                    ->default('b2c')
                    ->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'customer_type')) {
                $table->dropColumn('customer_type');
            }
        });
    }
};

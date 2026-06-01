<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Admin input mode: was the entered price inclusive?
            $table->boolean('price_includes_gst')->default(false)->after('base_price');

            // GST % for the product (e.g. 5, 12, 18)
            $table->decimal('gst_rate', 5, 2)->default(5.00)->after('price_includes_gst');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_includes_gst', 'gst_rate']);
        });
    }
};

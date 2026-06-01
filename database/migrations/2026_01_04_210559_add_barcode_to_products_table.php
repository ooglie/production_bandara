<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // EAN-13 is 13 digits, UPC-A is 12, but sometimes you want internal codes.
            $table->string('barcode', 64)
                ->nullable()
                ->unique(); // or drop unique if you need duplicates across products
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }

};

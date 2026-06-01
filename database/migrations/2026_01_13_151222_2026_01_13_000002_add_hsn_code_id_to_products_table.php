<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('hsn_code_id')
                ->nullable()
                ->after('vendor_id')
                ->constrained('hsn_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['hsn_code_id']);
            $table->dropColumn('hsn_code_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Drop existing unique index on code if present
            // Adjust the index name to match your schema (often 'vendors_code_unique')
            $table->dropUnique('vendors_code_unique');

            // Add composite unique index
            $table->unique(['code', 'deleted_at'], 'vendors_code_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique('vendors_code_deleted_at_unique');
            $table->unique('code', 'vendors_code_unique');
        });
    }
};


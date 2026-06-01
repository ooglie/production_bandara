<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop existing unique index on slug if present
            // Adjust the index name to match your schema (often 'products_slug_unique')
            $table->dropUnique('products_slug_unique');

            // Add composite unique index
            $table->unique(['slug', 'deleted_at'], 'products_slug_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_deleted_at_unique');
            $table->unique('slug', 'products_slug_unique');
        });
    }
};


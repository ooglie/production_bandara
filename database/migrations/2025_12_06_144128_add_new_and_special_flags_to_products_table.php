<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Flags
            $table->boolean('is_new')->default(false)->after('is_featured');
            $table->boolean('is_special')->default(false)->after('is_new');

            // Special pricing
            $table->decimal('special_price', 10, 2)->nullable()->after('base_price');
            $table->dateTime('special_starts_at')->nullable()->after('special_price');
            $table->dateTime('special_ends_at')->nullable()->after('special_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_new',
                'is_special',
                'special_price',
                'special_starts_at',
                'special_ends_at',
            ]);
        });
    }
};

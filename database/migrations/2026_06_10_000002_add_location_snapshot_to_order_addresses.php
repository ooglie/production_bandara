<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('order_addresses')) {
            return;
        }

        Schema::table('order_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('order_addresses', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('pincode');
            }

            if (! Schema::hasColumn('order_addresses', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('order_addresses', 'geocoding_provider')) {
                $table->string('geocoding_provider', 60)->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('order_addresses', 'geocoding_quality')) {
                $table->string('geocoding_quality', 80)->nullable()->after('geocoding_provider');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_addresses')) {
            return;
        }

        Schema::table('order_addresses', function (Blueprint $table) {
            foreach (['geocoding_quality', 'geocoding_provider', 'longitude', 'latitude'] as $column) {
                if (Schema::hasColumn('order_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('delivery_distance_rules')) {
            Schema::create('delivery_distance_rules', function (Blueprint $table) {
                $table->id();
                $table->enum('customer_type', ['all', 'guest', 'b2c', 'b2b'])->default('all');
                $table->decimal('min_order_value', 12, 2)->default(0);
                $table->decimal('min_distance_km', 8, 2)->default(0);
                $table->decimal('max_distance_km', 8, 2)->nullable();
                $table->decimal('delivery_fee', 12, 2)->default(0);
                $table->decimal('per_km_fee', 12, 2)->nullable();
                $table->decimal('free_delivery_above', 12, 2)->nullable();
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['customer_type', 'is_active'], 'ddr_type_active_idx');
                $table->index(['min_distance_km', 'max_distance_km'], 'ddr_distance_idx');
            });
        }

        Schema::table('customer_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_addresses', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('pincode');
            }
            if (! Schema::hasColumn('customer_addresses', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('customer_addresses', 'geocoded_at')) {
                $table->timestamp('geocoded_at')->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('customer_addresses', 'geocoding_provider')) {
                $table->string('geocoding_provider', 60)->nullable()->after('geocoded_at');
            }
            if (! Schema::hasColumn('customer_addresses', 'geocoding_quality')) {
                $table->string('geocoding_quality', 80)->nullable()->after('geocoding_provider');
            }
        });

        foreach (['orders', 'invoices'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'delivery_distance_km')) {
                    $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_pincode');
                }
                if (! Schema::hasColumn($tableName, 'delivery_duration_minutes')) {
                    $table->unsignedInteger('delivery_duration_minutes')->nullable()->after('delivery_distance_km');
                }
                if (! Schema::hasColumn($tableName, 'delivery_distance_provider')) {
                    $table->string('delivery_distance_provider', 60)->nullable()->after('delivery_duration_minutes');
                }
                if (! Schema::hasColumn($tableName, 'delivery_distance_calculated_at')) {
                    $table->timestamp('delivery_distance_calculated_at')->nullable()->after('delivery_distance_provider');
                }
                if (! Schema::hasColumn($tableName, 'delivery_fee_source')) {
                    $table->string('delivery_fee_source', 30)->nullable()->after('delivery_distance_calculated_at');
                }
            });
        }

        $this->seedDefaultDistanceRules();
    }

    public function down(): void
    {
        foreach (['invoices', 'orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach (['delivery_fee_source', 'delivery_distance_calculated_at', 'delivery_distance_provider', 'delivery_duration_minutes', 'delivery_distance_km'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::table('customer_addresses', function (Blueprint $table) {
            foreach (['geocoding_quality', 'geocoding_provider', 'geocoded_at', 'longitude', 'latitude'] as $column) {
                if (Schema::hasColumn('customer_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('delivery_distance_rules');
    }

    private function seedDefaultDistanceRules(): void
    {
        if (! Schema::hasTable('delivery_distance_rules') || DB::table('delivery_distance_rules')->exists()) {
            return;
        }

        $now = now();
        $b2cRules = [
            ['min' => 0, 'max' => 3, 'fee' => 49, 'free' => 1499],
            ['min' => 3.01, 'max' => 6, 'fee' => 69, 'free' => 1999],
            ['min' => 6.01, 'max' => 10, 'fee' => 89, 'free' => 1999],
            ['min' => 10.01, 'max' => 15, 'fee' => 129, 'free' => 2999],
            ['min' => 15.01, 'max' => 20, 'fee' => 179, 'free' => 3999],
        ];

        foreach ($b2cRules as $rule) {
            DB::table('delivery_distance_rules')->insert([
                'customer_type' => 'b2c',
                'min_order_value' => 0,
                'min_distance_km' => $rule['min'],
                'max_distance_km' => $rule['max'],
                'delivery_fee' => $rule['fee'],
                'per_km_fee' => null,
                'free_delivery_above' => $rule['free'],
                'tax_rate' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('delivery_distance_rules')->insert([
            'customer_type' => 'b2b',
            'min_order_value' => 0,
            'min_distance_km' => 0,
            'max_distance_km' => null,
            'delivery_fee' => 0,
            'per_km_fee' => null,
            'free_delivery_above' => 0,
            'tax_rate' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};

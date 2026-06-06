<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If a previous attempt failed before this migration was recorded,
        // MySQL may have left some of these new tables behind. Recreate only
        // this feature's fresh tables so the migration can be rerun safely.
        Schema::dropIfExists('handling_charge_rules');
        Schema::dropIfExists('delivery_charge_rules');
        Schema::dropIfExists('delivery_zone_pincodes');
        Schema::dropIfExists('delivery_zones');

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_zone_pincodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained('delivery_zones')->cascadeOnDelete();
            $table->string('pincode', 10);
            $table->string('city')->nullable();
            $table->string('area_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['pincode'], 'dzp_pincode_uq');
            $table->index(['delivery_zone_id', 'is_active'], 'dzp_zone_active_idx');
        });

        Schema::create('delivery_charge_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained('delivery_zones')->cascadeOnDelete();
            $table->enum('customer_type', ['all', 'guest', 'b2c', 'b2b'])->default('all');
            $table->decimal('min_order_value', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('free_delivery_above', 12, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['delivery_zone_id', 'customer_type', 'is_active'], 'dcr_zone_type_active_idx');
        });

        Schema::create('handling_charge_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('customer_type', ['all', 'guest', 'b2c', 'b2b'])->default('all');
            $table->enum('temperature_mode', ['all', 'frozen', 'chilled', 'ambient'])->default('all');
            $table->decimal('min_order_value', 12, 2)->default(0);
            $table->decimal('handling_fee', 12, 2)->default(0);
            $table->decimal('free_handling_above', 12, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['customer_type', 'temperature_mode', 'is_active'], 'hcr_type_temp_active_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'delivery_zone_id')) {
                $table->foreignId('delivery_zone_id')->nullable()->after('shipping_total')->constrained('delivery_zones')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'delivery_pincode')) {
                $table->string('delivery_pincode', 10)->nullable()->after('delivery_zone_id');
            }
            if (! Schema::hasColumn('orders', 'delivery_fee')) {
                $table->decimal('delivery_fee', 12, 2)->default(0)->after('delivery_pincode');
            }
            if (! Schema::hasColumn('orders', 'handling_fee')) {
                $table->decimal('handling_fee', 12, 2)->default(0)->after('delivery_fee');
            }
            if (! Schema::hasColumn('orders', 'delivery_tax_amount')) {
                $table->decimal('delivery_tax_amount', 12, 2)->default(0)->after('handling_fee');
            }
            if (! Schema::hasColumn('orders', 'handling_tax_amount')) {
                $table->decimal('handling_tax_amount', 12, 2)->default(0)->after('delivery_tax_amount');
            }
            if (! Schema::hasColumn('orders', 'delivery_tax_rate')) {
                $table->decimal('delivery_tax_rate', 5, 2)->default(0)->after('handling_tax_amount');
            }
            if (! Schema::hasColumn('orders', 'handling_tax_rate')) {
                $table->decimal('handling_tax_rate', 5, 2)->default(0)->after('delivery_tax_rate');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'delivery_zone_id')) {
                $table->foreignId('delivery_zone_id')->nullable()->after('discount_total')->constrained('delivery_zones')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'delivery_pincode')) {
                $table->string('delivery_pincode', 10)->nullable()->after('delivery_zone_id');
            }
            if (! Schema::hasColumn('invoices', 'delivery_fee')) {
                $table->decimal('delivery_fee', 12, 2)->default(0)->after('delivery_pincode');
            }
            if (! Schema::hasColumn('invoices', 'handling_fee')) {
                $table->decimal('handling_fee', 12, 2)->default(0)->after('delivery_fee');
            }
            if (! Schema::hasColumn('invoices', 'delivery_tax_amount')) {
                $table->decimal('delivery_tax_amount', 12, 2)->default(0)->after('handling_fee');
            }
            if (! Schema::hasColumn('invoices', 'handling_tax_amount')) {
                $table->decimal('handling_tax_amount', 12, 2)->default(0)->after('delivery_tax_amount');
            }
            if (! Schema::hasColumn('invoices', 'delivery_tax_rate')) {
                $table->decimal('delivery_tax_rate', 5, 2)->default(0)->after('handling_tax_amount');
            }
            if (! Schema::hasColumn('invoices', 'handling_tax_rate')) {
                $table->decimal('handling_tax_rate', 5, 2)->default(0)->after('delivery_tax_rate');
            }
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['delivery_tax_rate', 'handling_tax_rate', 'delivery_tax_amount', 'handling_tax_amount', 'handling_fee', 'delivery_fee', 'delivery_pincode'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('invoices', 'delivery_zone_id')) {
                $table->dropConstrainedForeignId('delivery_zone_id');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['delivery_tax_rate', 'handling_tax_rate', 'delivery_tax_amount', 'handling_tax_amount', 'handling_fee', 'delivery_fee', 'delivery_pincode'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('orders', 'delivery_zone_id')) {
                $table->dropConstrainedForeignId('delivery_zone_id');
            }
        });

        Schema::dropIfExists('handling_charge_rules');
        Schema::dropIfExists('delivery_charge_rules');
        Schema::dropIfExists('delivery_zone_pincodes');
        Schema::dropIfExists('delivery_zones');
    }

    private function seedDefaults(): void
    {
        $now = now();

        $zones = [
            ['name' => 'Zone A - Central Pune', 'code' => 'PUNE_CENTRAL', 'description' => 'Central / nearby Pune service from store pincode 411001.', 'sort_order' => 10],
            ['name' => 'Zone B - Pune City', 'code' => 'PUNE_CITY', 'description' => 'Standard Pune city delivery zone.', 'sort_order' => 20],
            ['name' => 'Zone C - Outer Pune', 'code' => 'PUNE_OUTER', 'description' => 'Outer Pune / longer route delivery zone.', 'sort_order' => 30],
            ['name' => 'Zone D - Extended Pune', 'code' => 'PUNE_EXTENDED', 'description' => 'Extended serviceable area / manual approval zone.', 'sort_order' => 40],
        ];

        foreach ($zones as $zone) {
            DB::table('delivery_zones')->updateOrInsert(
                ['code' => $zone['code']],
                $zone + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $centralId = DB::table('delivery_zones')->where('code', 'PUNE_CENTRAL')->value('id');
        if ($centralId) {
            foreach (['411001', '411002'] as $pincode) {
                DB::table('delivery_zone_pincodes')->updateOrInsert(
                    ['pincode' => $pincode],
                    [
                        'delivery_zone_id' => $centralId,
                        'city' => 'Pune',
                        'area_name' => $pincode === '411001' ? 'Pune City / store base' : 'Pune City',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $ruleSeeds = [
            'PUNE_CENTRAL' => ['delivery_fee' => 49, 'free_delivery_above' => 1499],
            'PUNE_CITY' => ['delivery_fee' => 79, 'free_delivery_above' => 1999],
            'PUNE_OUTER' => ['delivery_fee' => 129, 'free_delivery_above' => 2999],
            'PUNE_EXTENDED' => ['delivery_fee' => 199, 'free_delivery_above' => null],
        ];

        foreach ($ruleSeeds as $code => $seed) {
            $zoneId = DB::table('delivery_zones')->where('code', $code)->value('id');
            if (! $zoneId) {
                continue;
            }

            DB::table('delivery_charge_rules')->insert([
                'delivery_zone_id' => $zoneId,
                'customer_type' => 'b2c',
                'min_order_value' => 0,
                'delivery_fee' => $seed['delivery_fee'],
                'free_delivery_above' => $seed['free_delivery_above'],
                'tax_rate' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('delivery_charge_rules')->insert([
                'delivery_zone_id' => $zoneId,
                'customer_type' => 'b2b',
                'min_order_value' => 0,
                'delivery_fee' => 0,
                'free_delivery_above' => 0,
                'tax_rate' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('handling_charge_rules')->insert([
            'customer_type' => 'b2c',
            'temperature_mode' => 'all',
            'min_order_value' => 0,
            'handling_fee' => 39,
            'free_handling_above' => 999,
            'tax_rate' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('handling_charge_rules')->insert([
            'customer_type' => 'b2b',
            'temperature_mode' => 'all',
            'min_order_value' => 0,
            'handling_fee' => 0,
            'free_handling_above' => 0,
            'tax_rate' => 0,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('inventory_packs')) {
            Schema::create('inventory_packs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('production_run_id')->nullable()->index();
                $table->unsignedBigInteger('source_inventory_lot_id')->nullable()->index();
                $table->unsignedBigInteger('source_inventory_piece_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                $table->unsignedBigInteger('product_sell_unit_id')->nullable()->index();
                $table->string('pack_code')->nullable()->index();
                $table->unsignedInteger('pack_no')->nullable();
                $table->decimal('pack_quantity', 12, 3)->default(0);
                $table->decimal('available_pack_quantity', 12, 3)->default(0);
                $table->decimal('pieces_per_pack', 12, 3)->nullable();
                $table->decimal('total_pieces', 12, 3)->nullable();
                $table->decimal('available_pieces', 12, 3)->nullable();
                $table->decimal('source_pieces_per_unit', 12, 3)->nullable();
                $table->decimal('source_quantity_consumed', 12, 3)->nullable();
                $table->decimal('source_weight_kg_consumed', 12, 3)->nullable();
                $table->decimal('unit_weight_kg', 12, 3)->nullable();
                $table->decimal('total_weight_kg', 12, 3)->nullable();
                $table->decimal('unit_cost', 12, 2)->nullable();
                $table->decimal('total_cost', 12, 2)->nullable();
                $table->decimal('sold_weight_kg', 12, 3)->nullable();
                $table->decimal('actual_weight_kg', 12, 3)->nullable();
                $table->date('packed_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('batch_code', 120)->nullable()->index();
                $table->string('status', 40)->default('available')->index();
                $table->dateTime('reserved_until')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable()->index();
                $table->unsignedBigInteger('updated_by_id')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('inventory_packs', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_packs', 'product_sell_unit_id')) {
                $table->unsignedBigInteger('product_sell_unit_id')->nullable()->after('product_variant_id')->index();
            }
            if (! Schema::hasColumn('inventory_packs', 'pack_code')) {
                $table->string('pack_code')->nullable()->after('product_sell_unit_id')->index();
            }
            if (! Schema::hasColumn('inventory_packs', 'pack_no')) {
                $table->unsignedInteger('pack_no')->nullable()->after('pack_code');
            }
            if (! Schema::hasColumn('inventory_packs', 'pack_quantity')) {
                $table->decimal('pack_quantity', 12, 3)->default(0)->after('pack_no');
            }
            if (! Schema::hasColumn('inventory_packs', 'available_pack_quantity')) {
                $table->decimal('available_pack_quantity', 12, 3)->default(0)->after('pack_quantity');
            }
            if (! Schema::hasColumn('inventory_packs', 'pieces_per_pack')) {
                $table->decimal('pieces_per_pack', 12, 3)->nullable()->after('available_pack_quantity');
            }
            if (! Schema::hasColumn('inventory_packs', 'total_pieces')) {
                $table->decimal('total_pieces', 12, 3)->nullable()->after('pieces_per_pack');
            }
            if (! Schema::hasColumn('inventory_packs', 'available_pieces')) {
                $table->decimal('available_pieces', 12, 3)->nullable()->after('total_pieces');
            }
            if (! Schema::hasColumn('inventory_packs', 'source_pieces_per_unit')) {
                $table->decimal('source_pieces_per_unit', 12, 3)->nullable()->after('available_pieces');
            }
            if (! Schema::hasColumn('inventory_packs', 'source_quantity_consumed')) {
                $table->decimal('source_quantity_consumed', 12, 3)->nullable()->after('source_pieces_per_unit');
            }
            if (! Schema::hasColumn('inventory_packs', 'source_weight_kg_consumed')) {
                $table->decimal('source_weight_kg_consumed', 12, 3)->nullable()->after('source_quantity_consumed');
            }
            if (! Schema::hasColumn('inventory_packs', 'unit_weight_kg')) {
                $table->decimal('unit_weight_kg', 12, 3)->nullable()->after('source_weight_kg_consumed');
            }
            if (! Schema::hasColumn('inventory_packs', 'total_weight_kg')) {
                $table->decimal('total_weight_kg', 12, 3)->nullable()->after('unit_weight_kg');
            }
            if (! Schema::hasColumn('inventory_packs', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->nullable()->after('actual_weight_kg');
            }
            if (! Schema::hasColumn('inventory_packs', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->nullable()->after('unit_cost');
            }
            if (! Schema::hasColumn('inventory_packs', 'notes')) {
                $table->text('notes')->nullable()->after('reserved_until');
            }
            if (! Schema::hasColumn('inventory_packs', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('notes')->index();
            }
            if (! Schema::hasColumn('inventory_packs', 'updated_by_id')) {
                $table->unsignedBigInteger('updated_by_id')->nullable()->after('created_by_id')->index();
            }
            if (! Schema::hasColumn('inventory_packs', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        // Non-destructive by design. Some installs already had inventory_packs
        // from earlier stabilization work.
    }
};

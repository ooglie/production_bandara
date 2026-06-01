<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'b2c_price_includes_gst')) {
                $table->boolean('b2c_price_includes_gst')
                    ->default(true)
                    ->after(Schema::hasColumn('products', 'price_includes_gst') ? 'price_includes_gst' : 'mrp_price');
            }

            if (! Schema::hasColumn('products', 'b2b_price_includes_gst')) {
                $table->boolean('b2b_price_includes_gst')
                    ->default(false)
                    ->after('b2c_price_includes_gst');
            }
        });

        // Preserve legacy product price mode for existing retail prices if present.
        // New/empty databases will simply use the required defaults: B2C inclusive,
        // B2B exclusive.
        if (Schema::hasColumn('products', 'price_includes_gst')) {
            DB::statement('UPDATE products SET b2c_price_includes_gst = price_includes_gst');

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('price_includes_gst');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'price_includes_gst')) {
                $table->boolean('price_includes_gst')->default(true)->after('mrp_price');
            }
        });

        if (Schema::hasColumn('products', 'b2c_price_includes_gst')) {
            DB::statement('UPDATE products SET price_includes_gst = b2c_price_includes_gst');
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'b2b_price_includes_gst')) {
                $table->dropColumn('b2b_price_includes_gst');
            }

            if (Schema::hasColumn('products', 'b2c_price_includes_gst')) {
                $table->dropColumn('b2c_price_includes_gst');
            }
        });
    }
};

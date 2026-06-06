<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'storage_guidance')) {
                $table->text('storage_guidance')->nullable()->after('description');
            }

            if (! Schema::hasColumn('products', 'delivery_support')) {
                $table->text('delivery_support')->nullable()->after('storage_guidance');
            }
        });

        $defaultStorage = implode("\n", [
            'Keep frozen at or below -18°C.',
            'Once thawed, keep refrigerated and consume promptly.',
            'Do not refreeze after complete thawing.',
            'Cook thoroughly before serving where applicable.',
        ]);

        $defaultDelivery = implode("\n", [
            'Delivered in cold-chain conditions where available.',
            'Please inspect the package promptly on delivery.',
            'Perishable and frozen items may have limited return eligibility.',
            'Contact support quickly if you receive a damaged or incorrect item.',
        ]);

        if (Schema::hasColumn('products', 'storage_guidance')) {
            DB::table('products')
                ->whereNull('storage_guidance')
                ->update(['storage_guidance' => $defaultStorage]);
        }

        if (Schema::hasColumn('products', 'delivery_support')) {
            DB::table('products')
                ->whereNull('delivery_support')
                ->update(['delivery_support' => $defaultDelivery]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'delivery_support')) {
                $table->dropColumn('delivery_support');
            }

            if (Schema::hasColumn('products', 'storage_guidance')) {
                $table->dropColumn('storage_guidance');
            }
        });
    }
};

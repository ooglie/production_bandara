<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_STORAGE_PROFILE = 'frozen';

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'storage_profile')) {
                $table->string('storage_profile', 32)
                    ->default(self::DEFAULT_STORAGE_PROFILE)
                    ->after('delivery_support');
            }
        });

        if (Schema::hasColumn('products', 'storage_profile')) {
            DB::table('products')
                ->where(function ($query) {
                    $query->whereNull('storage_profile')
                        ->orWhere('storage_profile', '');
                })
                ->update(['storage_profile' => self::DEFAULT_STORAGE_PROFILE]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'storage_profile')) {
                $table->dropColumn('storage_profile');
            }
        });
    }
};

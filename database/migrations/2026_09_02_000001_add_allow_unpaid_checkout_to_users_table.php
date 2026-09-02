<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'allow_unpaid_checkout')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('allow_unpaid_checkout')
                ->default(false)
                ->after('customer_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'allow_unpaid_checkout')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('allow_unpaid_checkout');
        });
    }
};

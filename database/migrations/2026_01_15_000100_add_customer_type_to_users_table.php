<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Include staff from the first creation so fresh SQLite test databases
            // do not need a MySQL-specific ALTER TABLE ... MODIFY statement later.
            if (! Schema::hasColumn('users', 'customer_type')) {
                $table->enum('customer_type', ['b2c', 'b2b', 'staff'])
                    ->default('b2c')
                    ->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'customer_type')) {
                $table->dropColumn('customer_type');
            }
        });
    }
};

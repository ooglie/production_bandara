<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Print tracking is stored on the existing orders table.
        // This migration is idempotent for imported handoff DBs where the
        // columns may already exist and the migration row may already be present.
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'printed_at')) {
                $table->timestamp('printed_at')->nullable()->index();
            }

            if (! Schema::hasColumn('orders', 'printed_by_id')) {
                $table->foreignId('printed_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'printed_by_id')) {
                try {
                    $table->dropForeign(['printed_by_id']);
                } catch (Throwable $e) {
                    // Imported DBs may have the column without the named FK constraint.
                }

                $table->dropColumn('printed_by_id');
            }

            if (Schema::hasColumn('orders', 'printed_at')) {
                $table->dropColumn('printed_at');
            }
        });
    }
};

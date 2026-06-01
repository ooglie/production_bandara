<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy duplicate migration kept as a no-op.
        // Print tracking is handled by the idempotent 2026_01_15 migration and
        // lives on the existing orders table, not in a separate table.
    }

    public function down(): void
    {
        // No-op by design.
    }
};

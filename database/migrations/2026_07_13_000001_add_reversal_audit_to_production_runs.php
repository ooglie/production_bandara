<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_runs', function (Blueprint $table) {
            if (! Schema::hasColumn('production_runs', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->index();
            }

            if (! Schema::hasColumn('production_runs', 'reversed_by_id')) {
                $table->foreignId('reversed_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('production_runs', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable();
            }

            if (! Schema::hasColumn('production_runs', 'reversal_snapshot_json')) {
                $table->json('reversal_snapshot_json')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_runs', function (Blueprint $table) {
            if (Schema::hasColumn('production_runs', 'reversed_by_id')) {
                $table->dropConstrainedForeignId('reversed_by_id');
            }

            $columns = collect([
                'reversed_at',
                'reversal_reason',
                'reversal_snapshot_json',
            ])->filter(fn (string $column) => Schema::hasColumn('production_runs', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

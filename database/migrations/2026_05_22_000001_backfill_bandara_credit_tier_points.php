<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('bandara_credit_transactions') || ! Schema::hasColumn('bandara_credit_transactions', 'tier_points')) {
            return;
        }

        // Backfill legacy rows created before tier_points existed. Only normal
        // earning rows count toward tier progress by default. Promotional,
        // welcome, repeat, and redemption rows remain excluded unless a later
        // transaction explicitly carries tier_points.
        DB::table('bandara_credit_transactions')
            ->where('status', 'posted')
            ->where('amount', '>', 0)
            ->whereIn('type', ['base_earned', 'tier_bonus', 'order_reward', 'order_credit', 'earn', 'earned', 'credit'])
            ->where(function ($query) {
                $query->whereNull('tier_points')->orWhere('tier_points', 0);
            })
            ->update(['tier_points' => DB::raw('amount')]);

        DB::table('bandara_credit_transactions')
            ->where('status', 'posted')
            ->where('amount', '<', 0)
            ->whereIn('type', ['earn_reversal', 'reversal', 'partial_refund', 'partial_cancellation'])
            ->where(function ($query) {
                $query->whereNull('tier_points')->orWhere('tier_points', 0);
            })
            ->update(['tier_points' => DB::raw('amount')]);
    }

    public function down(): void
    {
        // No destructive rollback. Tier point backfill is corrective accounting data.
    }
};

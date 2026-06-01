<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bandara_credit_tiers')) {
            $now = now();

            foreach ([
                ['key' => 'silver', 'name' => 'Silver', 'threshold_min' => 0, 'threshold_max' => 999, 'reward_rate_percent' => 1.00, 'sort_order' => 1],
                ['key' => 'gold', 'name' => 'Gold', 'threshold_min' => 1000, 'threshold_max' => 3499, 'reward_rate_percent' => 2.00, 'sort_order' => 2],
                ['key' => 'platinum', 'name' => 'Platinum', 'threshold_min' => 3500, 'threshold_max' => null, 'reward_rate_percent' => 4.00, 'sort_order' => 3],
            ] as $tier) {
                DB::table('bandara_credit_tiers')->updateOrInsert(
                    ['key' => $tier['key']],
                    array_merge($tier, [
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ])
                );
            }
        }

        if (Schema::hasTable('bandara_credit_tier_histories')) {
            $expiredAt = Carbon::yesterday()->toDateString();
            $now = now();

            // Earlier mixed reward patches could retain Gold/Platinum status
            // with too few qualification points. Expire only invalid retained
            // histories; valid achievements stay untouched.
            DB::table('bandara_credit_tier_histories')
                ->where('tier', 'gold')
                ->where('tier_points_at_qualification', '<', 1000)
                ->whereDate('valid_until', '>=', now()->toDateString())
                ->update(['valid_until' => $expiredAt, 'updated_at' => $now]);

            DB::table('bandara_credit_tier_histories')
                ->where('tier', 'platinum')
                ->where('tier_points_at_qualification', '<', 3500)
                ->whereDate('valid_until', '>=', now()->toDateString())
                ->update(['valid_until' => $expiredAt, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // No destructive rollback. Admins can edit tier thresholds from the
        // rewards tier management screen if business rules change later.
    }
};

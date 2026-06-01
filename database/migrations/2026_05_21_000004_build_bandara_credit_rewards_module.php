<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTierTable();
        $this->createTierHistoryTable();
        $this->createCampaignTables();
        $this->upgradeTransactionLedger();
        $this->seedDefaultTiers();
    }

    private function createTierTable(): void
    {
        if (! Schema::hasTable('bandara_credit_tiers')) {
            Schema::create('bandara_credit_tiers', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique();
                $table->string('name', 80);
                $table->unsignedInteger('threshold_min')->default(0);
                $table->unsignedInteger('threshold_max')->nullable();
                $table->decimal('reward_rate_percent', 6, 2)->default(1);
                $table->unsignedSmallInteger('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
                $table->index(['threshold_min', 'threshold_max']);
            });
        }
    }


    private function createTierHistoryTable(): void
    {
        if (! Schema::hasTable('bandara_credit_tier_histories')) {
            Schema::create('bandara_credit_tier_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('tier', 40)->index();
                $table->unsignedSmallInteger('qualified_year')->index();
                $table->unsignedInteger('tier_points_at_qualification')->default(0);
                $table->date('valid_from')->index();
                $table->date('valid_until')->index();
                $table->timestamp('achieved_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'tier', 'qualified_year'], 'bc_tier_history_unique');
                $table->index(['user_id', 'valid_until']);
            });
        }
    }

    private function createCampaignTables(): void
    {
        if (! Schema::hasTable('bandara_credit_campaigns')) {
            Schema::create('bandara_credit_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('status', 20)->default('draft'); // draft | active | paused | expired
                $table->string('type', 30)->default('order'); // order | product | category | fixed_bonus
                $table->timestamp('starts_at')->nullable()->index();
                $table->timestamp('ends_at')->nullable()->index();
                $table->decimal('min_order_amount', 12, 2)->nullable();
                $table->json('eligible_tiers')->nullable();
                $table->decimal('multiplier', 8, 3)->default(1.000);
                $table->unsignedInteger('fixed_bonus_points')->nullable();
                $table->unsignedInteger('max_bonus_per_order')->nullable();
                $table->unsignedInteger('max_bonus_per_customer')->nullable();
                $table->unsignedInteger('budget_points')->nullable();
                $table->unsignedInteger('used_budget_points')->default(0);
                $table->boolean('counts_toward_tier')->default(false);
                $table->string('stacking_rule', 30)->default('best_wins');
                $table->unsignedBigInteger('created_by_id')->nullable()->index();
                $table->unsignedBigInteger('updated_by_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'starts_at', 'ends_at']);
                $table->index(['type', 'status']);
            });
        }

        if (! Schema::hasTable('bandara_credit_campaign_products')) {
            Schema::create('bandara_credit_campaign_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('campaign_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();

                $table->unique(['campaign_id', 'product_id'], 'bc_campaign_product_unique');
                $table->index('product_id');
            });
        }

        if (! Schema::hasTable('bandara_credit_campaign_categories')) {
            Schema::create('bandara_credit_campaign_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('campaign_id');
                $table->unsignedBigInteger('category_id');
                $table->timestamps();

                $table->unique(['campaign_id', 'category_id'], 'bc_campaign_category_unique');
                $table->index('category_id');
            });
        }
    }

    private function upgradeTransactionLedger(): void
    {
        if (! Schema::hasTable('bandara_credit_transactions')) {
            return;
        }

        Schema::table('bandara_credit_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('bandara_credit_transactions', 'campaign_id')) {
                $table->unsignedBigInteger('campaign_id')->nullable()->index()->after('order_id');
            }

            if (! Schema::hasColumn('bandara_credit_transactions', 'tier_points')) {
                $table->integer('tier_points')->default(0)->after('amount');
            }

            if (! Schema::hasColumn('bandara_credit_transactions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index()->after('note');
            }

            if (! Schema::hasColumn('bandara_credit_transactions', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->index()->after('expires_at');
            }
        });

        DB::table('bandara_credit_transactions')
            ->whereNull('tier_points')
            ->update(['tier_points' => 0]);
    }

    private function seedDefaultTiers(): void
    {
        if (! Schema::hasTable('bandara_credit_tiers')) {
            return;
        }

        $now = now();
        $tiers = [
            ['key' => 'silver', 'name' => 'Silver', 'threshold_min' => 0, 'threshold_max' => 999, 'reward_rate_percent' => 1.00, 'sort_order' => 1],
            ['key' => 'gold', 'name' => 'Gold', 'threshold_min' => 1000, 'threshold_max' => 3499, 'reward_rate_percent' => 2.00, 'sort_order' => 2],
            ['key' => 'platinum', 'name' => 'Platinum', 'threshold_min' => 3500, 'threshold_max' => null, 'reward_rate_percent' => 4.00, 'sort_order' => 3],
        ];

        foreach ($tiers as $tier) {
            DB::table('bandara_credit_tiers')->updateOrInsert(
                ['key' => $tier['key']],
                $tier + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bandara_credit_transactions')) {
            Schema::table('bandara_credit_transactions', function (Blueprint $table) {
                foreach (['created_by_id', 'expires_at', 'tier_points', 'campaign_id'] as $column) {
                    if (Schema::hasColumn('bandara_credit_transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('bandara_credit_campaign_categories');
        Schema::dropIfExists('bandara_credit_campaign_products');
        Schema::dropIfExists('bandara_credit_campaigns');
        Schema::dropIfExists('bandara_credit_tier_histories');
        Schema::dropIfExists('bandara_credit_tiers');
    }
};

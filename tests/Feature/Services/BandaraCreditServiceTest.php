<?php

namespace Tests\Feature\Services;

use App\Models\BandaraCreditWallet;
use App\Models\User;
use App\Services\BandaraCreditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class BandaraCreditServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-10 12:00:00'));

        $this->rebuildTestSchema();

        config([
            'bandara_credit.enabled' => true,
            'bandara_credit.shadow_mode' => true,

            'bandara_credit.earn_enabled' => false,
            'bandara_credit.redeem_enabled' => false,

            'bandara_credit.repeat_bonus_enabled' => true,
            'bandara_credit.welcome_bonus_enabled' => true,
            'bandara_credit.birthday_bonus_enabled' => true,
            'bandara_credit.tiers_enabled' => true,

            'bandara_credit.earning.per_amount_spent' => 100,
            'bandara_credit.earning.credit_amount' => 1,
            'bandara_credit.earning.repeat_window_days' => 10,
            'bandara_credit.earning.welcome_credit' => 100,
            'bandara_credit.earning.welcome_min_order_value' => 999,

            'bandara_credit.tiers.silver.threshold' => 0,
            'bandara_credit.tiers.silver.birthday_credit' => 100,
            'bandara_credit.tiers.gold.threshold' => 10000,
            'bandara_credit.tiers.gold.birthday_credit' => 150,
            'bandara_credit.tiers.platinum.threshold' => 25000,
            'bandara_credit.tiers.platinum.birthday_credit' => 200,

            'bandara_credit.order_model' => BandaraCreditTestOrder::class,
            'bandara_credit.order_mapping' => [
                'user_id' => 'user_id',
                'status' => 'status',
                'placed_at' => 'created_at',
                'eligible_spend' => 'subtotal',
            ],
            'bandara_credit.successful_statuses' => [
                'delivered',
                'completed',
            ],
            'bandara_credit.cancelled_statuses' => [
                'cancelled',
            ],
            'bandara_credit.eligibility.mode' => 'all',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('test_bandara_orders');
        Schema::dropIfExists('bandara_credit_wallets');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_it_creates_wallet_with_default_values(): void
    {
        $user = $this->createUser();

        $wallet = $this->service()->getOrCreateWallet($user);

        $this->assertSame($user->id, $wallet->user_id);
        $this->assertSame(0, $wallet->balance);
        $this->assertSame('silver', $wallet->tier);

        $this->assertDatabaseHas('bandara_credit_wallets', [
            'user_id' => $user->id,
            'balance' => 0,
            'tier' => 'silver',
        ]);
    }

    public function test_current_balance_reads_from_wallet(): void
    {
        $user = $this->createUser();

        BandaraCreditWallet::create([
            'user_id' => $user->id,
            'balance' => 125,
            'tier' => 'silver',
        ]);

        $this->assertSame(125, $this->service()->currentBalance($user));
    }

    public function test_preview_earn_for_raw_order_data_calculates_base_credit(): void
    {
        $preview = $this->service()->previewEarnForOrder([
            'eligible_spend' => 1499,
            'placed_at' => now(),
        ]);

        $this->assertSame(1499, $preview['eligible_spend']);
        $this->assertSame(14, $preview['base_credit']);
        $this->assertSame(0, $preview['repeat_bonus']);
        $this->assertSame(0, $preview['welcome_bonus']);
        $this->assertSame(14, $preview['total_credit_preview']);
        $this->assertFalse($preview['qualifies_repeat_bonus']);
        $this->assertFalse($preview['qualifies_welcome_bonus']);
    }

    public function test_preview_earn_applies_welcome_bonus_for_first_qualifying_order(): void
    {
        $user = $this->createUser();

        $preview = $this->service()->previewEarnForOrder([
            'user_id' => $user->id,
            'eligible_spend' => 999,
            'placed_at' => now(),
        ]);

        $this->assertSame(9, $preview['base_credit']);
        $this->assertSame(0, $preview['repeat_bonus']);
        $this->assertSame(100, $preview['welcome_bonus']);
        $this->assertSame(109, $preview['total_credit_preview']);
        $this->assertFalse($preview['qualifies_repeat_bonus']);
        $this->assertTrue($preview['qualifies_welcome_bonus']);
    }

    public function test_preview_earn_applies_repeat_bonus_when_previous_successful_order_is_within_10_days(): void
    {
        $user = $this->createUser();

        $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 1200.00,
            createdAt: now()->subDays(8),
        );

        $preview = $this->service()->previewEarnForOrder([
            'user_id' => $user->id,
            'eligible_spend' => 1300,
            'placed_at' => now(),
        ]);

        $this->assertSame(13, $preview['base_credit']);
        $this->assertSame(13, $preview['repeat_bonus']);
        $this->assertSame(0, $preview['welcome_bonus']);
        $this->assertSame(26, $preview['total_credit_preview']);
        $this->assertTrue($preview['qualifies_repeat_bonus']);
        $this->assertFalse($preview['qualifies_welcome_bonus']);
    }

    public function test_preview_tier_for_spend_uses_whole_rupees_for_progress(): void
    {
        $preview = $this->service()->previewTierForSpend(1904.76);

        $this->assertSame('silver', $preview['tier']);
        $this->assertSame(1904, $preview['rolling_spend']);
        $this->assertSame(100, $preview['birthday_credit']);
        $this->assertSame('gold', $preview['next_tier']);
        $this->assertSame(10000, $preview['next_tier_threshold']);
        $this->assertSame(8096, $preview['amount_to_next_tier']);
        $this->assertSame(19.04, $preview['progress_percentage']);
    }

    public function test_preview_tier_for_user_counts_only_successful_orders_in_last_12_months(): void
    {
        $user = $this->createUser();

        $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 1000.40,
            createdAt: now()->subDays(20),
        );

        $this->createTestOrder(
            user: $user,
            status: 'completed',
            subtotal: 904.36,
            createdAt: now()->subDays(30),
        );

        $this->createTestOrder(
            user: $user,
            status: 'pending',
            subtotal: 5000.00,
            createdAt: now()->subDays(5),
        );

        $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 7000.00,
            createdAt: now()->subDays(370),
        );

        $preview = $this->service()->previewTierForUser($user);

        $this->assertSame('silver', $preview['tier']);
        $this->assertSame(1904, $preview['rolling_spend']);
        $this->assertSame(100, $preview['birthday_credit']);
        $this->assertSame('gold', $preview['next_tier']);
        $this->assertSame(10000, $preview['next_tier_threshold']);
        $this->assertSame(8096, $preview['amount_to_next_tier']);
        $this->assertSame(19.04, $preview['progress_percentage']);
    }

    protected function rebuildTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('test_bandara_orders');
        Schema::dropIfExists('bandara_credit_wallets');
        Schema::dropIfExists('users');

        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

        Schema::create('bandara_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->string('tier', 20)->default('silver');
            $table->timestamps();
        });

        Schema::create('test_bandara_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    protected function createUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    protected function createTestOrder(
        User $user,
        string $status,
        float $subtotal,
        Carbon $createdAt
    ): BandaraCreditTestOrder {
        return BandaraCreditTestOrder::query()->create([
            'user_id' => $user->id,
            'status' => $status,
            'subtotal' => $subtotal,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    protected function service(): BandaraCreditService
    {
        return app(BandaraCreditService::class);
    }
}

class BandaraCreditTestOrder extends Model
{
    protected $table = 'test_bandara_orders';

    public $timestamps = false;

    protected $guarded = [];
}
<?php

namespace Tests\Feature\Services;

use App\Models\BandaraCreditWallet;
use App\Models\User;
use App\Services\BandaraCreditLedgerService;
use App\Services\BandaraCreditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class BandaraCreditPostingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-04-10 12:00:00'));

        $this->rebuildTestSchema();

        config([
            'bandara_credit.enabled' => true,
            'bandara_credit.shadow_mode' => false,
            'bandara_credit.earn_enabled' => true,
            'bandara_credit.redeem_enabled' => false,
            'bandara_credit.auto_post_enabled' => true,
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

            'bandara_credit.order_model' => BandaraCreditPostingTestOrder::class,
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
        Schema::dropIfExists('bandara_credit_transactions');
        Schema::dropIfExists('bandara_credit_wallets');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_it_posts_base_and_welcome_credit_for_first_successful_order(): void
    {
        $user = $this->createUser();

        $order = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 999.00,
            createdAt: now(),
        );

        $result = $this->service()->postEarnForSuccessfulOrder($order);

        $this->assertTrue($result['posted']);
        $this->assertSame(109, $result['total_posted']);
        $this->assertSame(109, $result['wallet_balance']);
        $this->assertSame('silver', $result['tier']);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'base_earned',
            'amount' => 9,
        ]);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'welcome_bonus',
            'amount' => 100,
        ]);

        $this->assertDatabaseCount('bandara_credit_transactions', 2);
    }

    public function test_it_posts_repeat_bonus_for_repeat_order_within_10_days(): void
    {
        $user = $this->createUser();

        $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 1200.00,
            createdAt: now()->subDays(8),
        );

        $currentOrder = $this->createTestOrder(
            user: $user,
            status: 'completed',
            subtotal: 1300.00,
            createdAt: now(),
        );

        $result = $this->service()->postEarnForSuccessfulOrder($currentOrder);

        $this->assertTrue($result['posted']);
        $this->assertSame(26, $result['total_posted']);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $currentOrder->id,
            'type' => 'base_earned',
            'amount' => 13,
        ]);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $currentOrder->id,
            'type' => 'repeat_bonus',
            'amount' => 13,
        ]);
    }

    public function test_it_is_idempotent_for_the_same_order(): void
    {
        $user = $this->createUser();

        $order = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 999.00,
            createdAt: now(),
        );

        $first = $this->service()->postEarnForSuccessfulOrder($order);
        $second = $this->service()->postEarnForSuccessfulOrder($order);

        $this->assertTrue($first['posted']);
        $this->assertFalse($second['posted']);
        $this->assertSame('already_posted_or_nothing_to_post', $second['reason']);

        $wallet = BandaraCreditWallet::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame(109, (int) $wallet->balance);
        $this->assertDatabaseCount('bandara_credit_transactions', 2);
    }

    public function test_it_skips_non_successful_orders(): void
    {
        $user = $this->createUser();

        $order = $this->createTestOrder(
            user: $user,
            status: 'pending',
            subtotal: 999.00,
            createdAt: now(),
        );

        $result = $this->service()->postEarnForSuccessfulOrder($order);

        $this->assertFalse($result['posted']);
        $this->assertSame('order_not_successful', $result['reason']);

        $this->assertDatabaseCount('bandara_credit_transactions', 0);
        $this->assertDatabaseMissing('bandara_credit_wallets', [
            'user_id' => $user->id,
        ]);
    }

    protected function rebuildTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('test_bandara_orders');
        Schema::dropIfExists('bandara_credit_transactions');
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

        Schema::create('bandara_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->integer('amount');
            $table->string('type', 40);
            $table->string('status', 20)->default('posted');
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->json('meta')->nullable();
            $table->text('note')->nullable();
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
    ): BandaraCreditPostingTestOrder {
        return BandaraCreditPostingTestOrder::query()->create([
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

    public function test_it_posts_welcome_bonus_for_true_first_order_even_if_later_successful_orders_exist(): void
    {
        $user = $this->createUser();

        $firstOrder = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 1200.00,
            createdAt: now()->subDays(30),
        );

        $this->createTestOrder(
            user: $user,
            status: 'completed',
            subtotal: 800.00,
            createdAt: now()->subDays(5),
        );

        $result = $this->service()->postEarnForSuccessfulOrder($firstOrder);

        $this->assertTrue($result['posted']);
        $this->assertSame(112, $result['total_posted']);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $firstOrder->id,
            'type' => 'base_earned',
            'amount' => 12,
        ]);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $firstOrder->id,
            'type' => 'welcome_bonus',
            'amount' => 100,
        ]);
    }
    public function test_shadow_mode_blocks_writes(): void
    {
        config(['bandara_credit.shadow_mode' => true]);

        $user = $this->createUser();
        $order = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 999.00,
            createdAt: now(),
        );

        $result = $this->service()->postEarnForSuccessfulOrder($order);

        $this->assertFalse($result['posted']);
        $this->assertSame('shadow_mode', $result['reason']);
        $this->assertDatabaseCount('bandara_credit_transactions', 0);
        $this->assertDatabaseMissing('bandara_credit_wallets', [
            'user_id' => $user->id,
        ]);
    }

    public function test_lifecycle_queues_pending_rows_without_changing_wallet_balance(): void
    {
        $user = $this->createUser();
        $order = $this->createTestOrder(
            user: $user,
            status: 'processing',
            subtotal: 999.00,
            createdAt: now(),
        );

        $result = app(BandaraCreditLedgerService::class)->queueOrderReward($order);

        $this->assertSame('queued', $result['action']);
        $this->assertSame(109, $result['total_queued']);
        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'base_earned',
            'amount' => 9,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'welcome_bonus',
            'amount' => 100,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('bandara_credit_wallets', [
            'user_id' => $user->id,
        ]);
    }

    public function test_lifecycle_posts_previously_pending_rows_and_syncs_wallet(): void
    {
        $user = $this->createUser();
        $order = $this->createTestOrder(
            user: $user,
            status: 'processing',
            subtotal: 999.00,
            createdAt: now(),
        );

        app(BandaraCreditLedgerService::class)->queueOrderReward($order);

        $order->forceFill(['status' => 'delivered'])->save();
        $result = app(BandaraCreditLedgerService::class)->postOrderReward($order->fresh());

        $this->assertSame('posted', $result['action']);
        $this->assertSame(109, $result['total_posted']);
        $this->assertSame(109, $result['wallet_balance']);
        $this->assertDatabaseCount('bandara_credit_transactions', 2);
        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'base_earned',
            'status' => 'posted',
        ]);
        $this->assertDatabaseHas('bandara_credit_wallets', [
            'user_id' => $user->id,
            'balance' => 109,
        ]);
    }

    public function test_cancelling_posted_reward_reverses_without_double_subtracting_unrelated_credit(): void
    {
        $user = $this->createUser();

        DB::table('bandara_credit_transactions')->insert([
            'user_id' => $user->id,
            'order_id' => null,
            'amount' => 100,
            'type' => 'admin_credit',
            'status' => 'posted',
            'idempotency_key' => 'test:admin-credit:'.$user->id,
            'meta' => null,
            'note' => 'Existing credit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 999.00,
            createdAt: now(),
        );

        $this->service()->postEarnForSuccessfulOrder($order);
        $this->assertSame(209, (int) BandaraCreditWallet::where('user_id', $user->id)->value('balance'));

        $order->forceFill(['status' => 'cancelled'])->save();
        $result = app(BandaraCreditLedgerService::class)->cancelOrderReward($order->fresh());

        $this->assertSame('cancelled', $result['action']);
        $this->assertSame(109, $result['total_reversed']);
        $this->assertSame(100, $result['wallet_balance']);
        $this->assertSame(100, (int) BandaraCreditWallet::where('user_id', $user->id)->value('balance'));

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'base_earned',
            'status' => 'posted',
        ]);
        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'earn_reversal',
            'amount' => -9,
            'status' => 'posted',
        ]);

        $this->assertDatabaseHas('bandara_credit_transactions', [
            'order_id' => $order->id,
            'type' => 'earn_reversal',
            'amount' => -100,
            'status' => 'posted',
        ]);
    }

    public function test_welcome_bonus_is_not_awarded_twice_when_later_order_posts_first(): void
    {
        $user = $this->createUser();

        $laterOrder = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 1200.00,
            createdAt: now(),
        );

        $this->service()->postEarnForSuccessfulOrder($laterOrder);

        $earlierOrder = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 1200.00,
            createdAt: now()->subDays(3),
        );

        $result = $this->service()->postEarnForSuccessfulOrder($earlierOrder);

        $this->assertTrue($result['posted']);
        $this->assertSame(12, $result['total_posted']);
        $this->assertDatabaseMissing('bandara_credit_transactions', [
            'order_id' => $earlierOrder->id,
            'type' => 'welcome_bonus',
        ]);
    }

    public function test_auto_post_flag_blocks_lifecycle_but_not_manual_cli_service_posting(): void
    {
        config(['bandara_credit.auto_post_enabled' => false]);

        $user = $this->createUser();
        $order = $this->createTestOrder(
            user: $user,
            status: 'delivered',
            subtotal: 999.00,
            createdAt: now(),
        );

        $lifecycleResult = app(BandaraCreditLedgerService::class)->postOrderReward($order);

        $this->assertSame('skipped', $lifecycleResult['action']);
        $this->assertSame('auto_post_disabled', $lifecycleResult['reason']);
        $this->assertDatabaseCount('bandara_credit_transactions', 0);

        $manualResult = $this->service()->postEarnForSuccessfulOrder($order);

        $this->assertSame('posted', $manualResult['action']);
        $this->assertSame(109, $manualResult['wallet_balance']);
    }

}

class BandaraCreditPostingTestOrder extends Model
{
    protected $table = 'test_bandara_orders';

    public $timestamps = false;

    protected $guarded = [];
}


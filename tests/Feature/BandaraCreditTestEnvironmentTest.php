<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BandaraCreditService;
use Tests\TestCase;

class BandaraCreditTestEnvironmentTest extends TestCase
{
    public function test_bandara_credit_tests_use_live_b2c_reward_configuration(): void
    {
        $this->assertTrue((bool) config('bandara_credit.enabled'));
        $this->assertFalse((bool) config('bandara_credit.shadow_mode'));
        $this->assertTrue((bool) config('bandara_credit.earn_enabled'));
        $this->assertTrue((bool) config('bandara_credit.auto_post_enabled'));
        $this->assertTrue((bool) config('bandara_credit.repeat_bonus_enabled'));
        $this->assertSame('b2c', config('bandara_credit.eligibility.mode'));
    }

    public function test_factory_users_are_bandara_credit_eligible_b2c_users(): void
    {
        $user = User::factory()->make([
            'customer_type' => 'b2c',
        ]);

        $this->assertTrue(app(BandaraCreditService::class)->isEligibleUserForBandaraCredit($user));
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBandaraCreditTestDefaults();
    }

    /**
     * Keep reward tests deterministic and independent of the developer's local
     * .env / .env.testing values. Individual tests can still override any of
     * these values with config([...]) after setUp runs.
     */
    protected function configureBandaraCreditTestDefaults(): void
    {
        config([
            'bandara_credit.enabled' => true,
            'bandara_credit.shadow_mode' => false,
            'bandara_credit.earn_enabled' => true,
            'bandara_credit.redeem_enabled' => true,
            'bandara_credit.auto_post_enabled' => true,
            'bandara_credit.repeat_bonus_enabled' => true,
            'bandara_credit.welcome_bonus_enabled' => true,
            'bandara_credit.birthday_bonus_enabled' => true,
            'bandara_credit.tiers_enabled' => true,
            'bandara_credit.eligibility.mode' => 'b2c',
            'bandara_credit.eligibility.column' => 'customer_type',
            'bandara_credit.eligibility.b2c_value' => 'b2c',
            'bandara_credit.earning.per_amount_spent' => 100,
            'bandara_credit.earning.credit_amount' => 1,
            'bandara_credit.earning.repeat_window_days' => 10,
            'bandara_credit.earning.welcome_credit' => 100,
            'bandara_credit.earning.welcome_min_order_value' => 999,
            'bandara_credit.successful_statuses' => ['delivered', 'completed'],
            'bandara_credit.pending_statuses' => ['processing', 'shipped'],
            'bandara_credit.cancelled_statuses' => ['cancelled'],
        ]);
    }
}

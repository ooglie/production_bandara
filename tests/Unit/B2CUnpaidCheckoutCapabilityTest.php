<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class B2CUnpaidCheckoutCapabilityTest extends TestCase
{
    public function test_only_flagged_b2c_accounts_can_checkout_without_online_payment(): void
    {
        $authorisedB2c = new User();
        $authorisedB2c->customer_type = 'b2c';
        $authorisedB2c->allow_unpaid_checkout = true;

        $normalB2c = new User();
        $normalB2c->customer_type = 'b2c';
        $normalB2c->allow_unpaid_checkout = false;

        $b2b = new User();
        $b2b->customer_type = 'b2b';
        $b2b->allow_unpaid_checkout = true;

        $staff = new User();
        $staff->customer_type = 'staff';
        $staff->allow_unpaid_checkout = true;

        self::assertTrue($authorisedB2c->canCheckoutWithoutOnlinePayment());
        self::assertFalse($normalB2c->canCheckoutWithoutOnlinePayment());
        self::assertFalse($b2b->canCheckoutWithoutOnlinePayment());
        self::assertFalse($staff->canCheckoutWithoutOnlinePayment());
    }
}

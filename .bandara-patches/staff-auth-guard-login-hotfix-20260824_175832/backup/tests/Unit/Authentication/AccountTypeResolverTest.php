<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Support\Authentication\AccountTypeResolver;
use PHPUnit\Framework\TestCase;

final class AccountTypeResolverTest extends TestCase
{
    public function test_it_recognises_staff_role_variants(): void
    {
        $resolver = new AccountTypeResolver();

        self::assertTrue($resolver->isStaff((object) ['role' => 'Admin']));
        self::assertTrue($resolver->isStaff((object) ['role' => 'CA Accountant']));
        self::assertTrue($resolver->isStaff((object) ['role' => 'Delivery-Boy']));
    }

    public function test_it_keeps_non_staff_accounts_in_customer_authentication(): void
    {
        $resolver = new AccountTypeResolver();

        self::assertTrue($resolver->isCustomer((object) ['role' => 'B2C Customer']));
        self::assertTrue($resolver->isCustomer((object) ['role' => 'B2B Customer']));
        self::assertTrue($resolver->isCustomer((object) []));
        self::assertFalse($resolver->isCustomer((object) ['role' => 'Manager']));
    }
}

<?php

namespace Tests\Unit;

use App\Support\FinanceAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FinanceAccessMatrixTest extends TestCase
{
    public static function managerCapabilities(): array
    {
        return [
            [FinanceAccess::SUMMARY, true],
            [FinanceAccess::EXPENSES_VIEW, true],
            [FinanceAccess::EXPENSES_MANAGE, true],
            [FinanceAccess::EXPENSES_POST, false],
            [FinanceAccess::EXPENSE_SETTINGS_MANAGE, false],
            [FinanceAccess::SALARY_AGGREGATE, true],
            [FinanceAccess::SALARY_VIEW, false],
            [FinanceAccess::SALARY_MANAGE, false],
        ];
    }

    #[DataProvider('managerCapabilities')]
    public function test_manager_matrix(string $capability, bool $expected): void
    {
        $user = new FinanceAccessFakeUser(['Manager']);

        self::assertSame($expected, FinanceAccess::allows($user, $capability));
    }

    public function test_admin_and_accountant_have_full_access(): void
    {
        foreach (['Admin', 'Accountant'] as $role) {
            $user = new FinanceAccessFakeUser([$role]);

            foreach ($this->allCapabilities() as $capability) {
                self::assertTrue(FinanceAccess::allows($user, $capability), "{$role} should have {$capability}");
            }
        }
    }

    public function test_ca_accountant_is_read_only_and_permission_driven(): void
    {
        $user = new FinanceAccessFakeUser(['CAAccountant'], [
            'view finance summary',
            'view business expenses',
            'view salary records',
        ]);

        self::assertTrue(FinanceAccess::allows($user, FinanceAccess::SUMMARY));
        self::assertTrue(FinanceAccess::allows($user, FinanceAccess::EXPENSES_VIEW));
        self::assertTrue(FinanceAccess::allows($user, FinanceAccess::SALARY_VIEW));
        self::assertFalse(FinanceAccess::allows($user, FinanceAccess::SALARY_AGGREGATE));
        self::assertFalse(FinanceAccess::allows($user, FinanceAccess::EXPENSES_MANAGE));
        self::assertFalse(FinanceAccess::allows($user, FinanceAccess::EXPENSES_POST));
        self::assertFalse(FinanceAccess::allows($user, FinanceAccess::SALARY_MANAGE));
    }

    public function test_non_finance_roles_are_denied_even_with_permission_names(): void
    {
        foreach (['Stores', 'Support', 'DeliveryAgent', 'Customer'] as $role) {
            $user = new FinanceAccessFakeUser([$role], FinanceAccess::permissions());

            foreach ($this->allCapabilities() as $capability) {
                self::assertFalse(FinanceAccess::allows($user, $capability), "{$role} must not receive {$capability}");
            }
        }
    }

    private function allCapabilities(): array
    {
        return [
            FinanceAccess::SUMMARY,
            FinanceAccess::EXPENSES_VIEW,
            FinanceAccess::EXPENSES_MANAGE,
            FinanceAccess::EXPENSES_POST,
            FinanceAccess::EXPENSE_SETTINGS_MANAGE,
            FinanceAccess::SALARY_AGGREGATE,
            FinanceAccess::SALARY_VIEW,
            FinanceAccess::SALARY_MANAGE,
        ];
    }
}

class FinanceAccessFakeUser
{
    public function __construct(private array $roles, private array $permissions = [])
    {
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function hasAnyRole(array $roles): bool
    {
        return array_intersect($roles, $this->roles) !== [];
    }

    public function hasPermissionTo(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}

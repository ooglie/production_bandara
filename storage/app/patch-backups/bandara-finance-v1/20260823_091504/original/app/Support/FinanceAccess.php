<?php

namespace App\Support;

final class FinanceAccess
{
    public const SUMMARY = 'summary';
    public const EXPENSES_VIEW = 'expenses.view';
    public const EXPENSES_MANAGE = 'expenses.manage';
    public const EXPENSES_POST = 'expenses.post';
    public const EXPENSE_SETTINGS_MANAGE = 'expense-settings.manage';
    public const SALARY_AGGREGATE = 'salary.aggregate';
    public const SALARY_VIEW = 'salary.view';
    public const SALARY_MANAGE = 'salary.manage';

    private const PERMISSION_MAP = [
        self::SUMMARY => 'view finance summary',
        self::EXPENSES_VIEW => 'view business expenses',
        self::EXPENSES_MANAGE => 'manage business expenses',
        self::EXPENSES_POST => 'post business expenses',
        self::EXPENSE_SETTINGS_MANAGE => 'manage expense settings',
        self::SALARY_AGGREGATE => 'view salary aggregate',
        self::SALARY_VIEW => 'view salary records',
        self::SALARY_MANAGE => 'manage salary records',
    ];

    private const MANAGER_CAPABILITIES = [
        self::SUMMARY,
        self::EXPENSES_VIEW,
        self::EXPENSES_MANAGE,
        self::SALARY_AGGREGATE,
    ];

    private const CA_READ_ONLY_CAPABILITIES = [
        self::SUMMARY,
        self::EXPENSES_VIEW,
        self::SALARY_AGGREGATE,
        self::SALARY_VIEW,
    ];

    public static function allows(?object $user, string $capability): bool
    {
        if ($user === null || ! array_key_exists($capability, self::PERMISSION_MAP)) {
            return false;
        }

        if (self::hasAnyRole($user, ['Admin', 'Accountant'])) {
            return true;
        }

        if (self::hasRole($user, 'Manager')) {
            return in_array($capability, self::MANAGER_CAPABILITIES, true);
        }

        if (self::hasRole($user, 'CAAccountant')) {
            if (! in_array($capability, self::CA_READ_ONLY_CAPABILITIES, true)) {
                return false;
            }

            return self::hasPermission($user, self::PERMISSION_MAP[$capability]);
        }

        // Stores, Support, DeliveryAgent, Customer, and unknown roles receive
        // no finance access in the first release.
        return false;
    }

    public static function authorize(?object $user, string $capability): void
    {
        abort_unless(self::allows($user, $capability), 403, 'You do not have access to this finance function.');
    }

    public static function canSeeModule(?object $user): bool
    {
        foreach ([self::SUMMARY, self::EXPENSES_VIEW, self::SALARY_AGGREGATE, self::SALARY_VIEW] as $capability) {
            if (self::allows($user, $capability)) {
                return true;
            }
        }

        return false;
    }

    public static function canSeeIndividualSalary(?object $user): bool
    {
        return self::allows($user, self::SALARY_VIEW) || self::allows($user, self::SALARY_MANAGE);
    }

    public static function canSeeSalaryAggregate(?object $user): bool
    {
        return self::allows($user, self::SALARY_AGGREGATE)
            || self::allows($user, self::SALARY_VIEW)
            || self::allows($user, self::SALARY_MANAGE);
    }

    public static function landingRouteName(?object $user): ?string
    {
        if (self::allows($user, self::SUMMARY)) {
            return 'admin.finance.index';
        }

        if (self::allows($user, self::EXPENSES_VIEW)) {
            return 'admin.finance.expenses.index';
        }

        if (self::allows($user, self::SALARY_VIEW)) {
            return 'admin.finance.salary-entries.index';
        }

        return null;
    }

    public static function permissionFor(string $capability): ?string
    {
        return self::PERMISSION_MAP[$capability] ?? null;
    }

    public static function permissions(): array
    {
        return array_values(self::PERMISSION_MAP);
    }

    private static function hasRole(object $user, string $role): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        return (bool) $user->hasRole($role);
    }

    private static function hasAnyRole(object $user, array $roles): bool
    {
        if (method_exists($user, 'hasAnyRole')) {
            return (bool) $user->hasAnyRole($roles);
        }

        foreach ($roles as $role) {
            if (self::hasRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    private static function hasPermission(object $user, string $permission): bool
    {
        try {
            if (method_exists($user, 'hasPermissionTo')) {
                return (bool) $user->hasPermissionTo($permission);
            }

            if (method_exists($user, 'can')) {
                return (bool) $user->can($permission);
            }
        } catch (\Throwable) {
            // A permission may not exist yet while migrations are pending.
            return false;
        }

        return false;
    }
}

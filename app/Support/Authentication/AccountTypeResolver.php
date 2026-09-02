<?php

declare(strict_types=1);

namespace App\Support\Authentication;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionMethod;
use Throwable;

final class AccountTypeResolver
{
    public function isStaff(mixed $user): bool
    {
        if (! is_object($user)) {
            return false;
        }

        foreach ([
            'isStaff',
            'isAdmin',
            'isSuperAdmin',
            'isManager',
            'isAccountant',
            'isSupport',
            'isDeliveryBoy',
        ] as $method) {
            if ($this->truthyZeroArgumentMethod($user, $method)) {
                return true;
            }
        }

        $configured = $this->normalisedConfiguredRoles('staff_roles');

        foreach ($this->normalisedRoleNames($user) as $role) {
            if (in_array($role, $configured, true)) {
                return true;
            }
        }

        return false;
    }

    public function isCustomer(mixed $user): bool
    {
        if (! is_object($user) || $this->isStaff($user)) {
            return false;
        }

        $configured = $this->normalisedConfiguredRoles('customer_roles');
        $roles = $this->normalisedRoleNames($user);

        if (array_intersect($configured, $roles) !== []) {
            return true;
        }

        return (bool) config('staff-auth.allow_non_staff_customer_login', true);
    }

    public function hasAnyRole(mixed $user, array $roles): bool
    {
        if (! is_object($user)) {
            return false;
        }

        $expected = array_map($this->normalise(...), $roles);

        return array_intersect($expected, $this->normalisedRoleNames($user)) !== [];
    }

    /**
     * @return list<string>
     */
    public function normalisedRoleNames(mixed $user): array
    {
        if (! is_object($user)) {
            return [];
        }

        $roles = [];

        if (method_exists($user, 'getRoleNames')) {
            try {
                $roles = array_merge($roles, Arr::wrap($user->getRoleNames()->all()));
            } catch (Throwable) {
                // Fall through to the non-package role sources below.
            }
        }

        foreach (['role', 'user_role', 'account_role', 'type', 'user_type', 'account_type'] as $attribute) {
            try {
                $value = data_get($user, $attribute);

                if (is_string($value) || is_numeric($value)) {
                    $roles[] = (string) $value;
                } elseif (is_array($value)) {
                    $roles = array_merge($roles, $value);
                }
            } catch (Throwable) {
                // An accessor is allowed to be unavailable in this context.
            }
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $role): string => $this->normalise((string) $role),
            $roles
        ))));
    }

    /**
     * @return list<string>
     */
    private function normalisedConfiguredRoles(string $key): array
    {
        return array_values(array_unique(array_filter(array_map(
            $this->normalise(...),
            Arr::wrap(config("staff-auth.{$key}", []))
        ))));
    }

    private function normalise(string $value): string
    {
        $ascii = Str::lower(Str::ascii(trim($value)));

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $ascii), '_');
    }

    private function truthyZeroArgumentMethod(object $user, string $method): bool
    {
        if (! method_exists($user, $method)) {
            return false;
        }

        try {
            $reflection = new ReflectionMethod($user, $method);

            if ($reflection->getNumberOfRequiredParameters() !== 0) {
                return false;
            }

            return (bool) $user->{$method}();
        } catch (Throwable) {
            return false;
        }
    }
}

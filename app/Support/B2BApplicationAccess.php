<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Throwable;

final class B2BApplicationAccess
{
    public static function adminCan(User $user, string $ability = 'view'): bool
    {
        $roles = (array) config('b2b_application.admin_roles', ['Admin', 'Manager']);

        try {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles)) {
                return true;
            }
        } catch (Throwable) {
            // Continue to permission fallback.
        }

        $permission = config("b2b_application.permissions.{$ability}");

        if (! is_string($permission) || $permission === '') {
            return false;
        }

        try {
            return method_exists($user, 'can') && $user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }

    public static function rawCustomerType(User $user): string
    {
        $raw = $user->getRawOriginal('customer_type');

        if (is_scalar($raw)) {
            return strtolower((string) $raw);
        }

        $value = $user->getAttribute('customer_type');

        if ($value instanceof \BackedEnum) {
            return strtolower((string) $value->value);
        }

        return strtolower((string) $value);
    }

    public static function isB2B(User $user): bool
    {
        return self::rawCustomerType($user) === strtolower((string) config('b2b_application.customer_type.b2b', 'b2b'));
    }
}

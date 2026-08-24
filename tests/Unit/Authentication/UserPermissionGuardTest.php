<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Models\User;
use ReflectionProperty;
use Tests\TestCase;

final class UserPermissionGuardTest extends TestCase
{
    public function test_user_roles_and_permissions_remain_on_the_existing_web_guard(): void
    {
        $property = new ReflectionProperty(User::class, 'guard_name');

        self::assertSame('web', $property->getValue(new User()));
    }
}

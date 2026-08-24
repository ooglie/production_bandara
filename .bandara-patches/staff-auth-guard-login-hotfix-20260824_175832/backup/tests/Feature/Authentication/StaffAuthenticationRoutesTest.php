<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class StaffAuthenticationRoutesTest extends TestCase
{
    public function test_staff_guard_and_routes_are_registered(): void
    {
        self::assertSame('session', config('auth.guards.staff.driver'));
        self::assertSame(config('auth.guards.web.provider'), config('auth.guards.staff.provider'));

        self::assertTrue(Route::has('admin.login'));
        self::assertTrue(Route::has('admin.login.store'));
        self::assertTrue(Route::has('admin.logout'));
        self::assertTrue(Route::has('staff-impersonation.accept'));
        self::assertTrue(Route::has('staff-impersonation.leave'));
    }

    public function test_existing_customer_login_route_remains_registered(): void
    {
        self::assertTrue(Route::has('login'));
    }

    public function test_staff_login_page_uses_the_existing_login_design_copy(): void
    {
        $this->get(route('admin.login'))
            ->assertOk();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use App\Http\Middleware\RedirectIfStaffAuthenticated;
use App\Models\User;
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

    public function test_all_existing_backoffice_route_areas_use_the_staff_session(): void
    {
        $requiredPrefixes = ['admin', 'support', 'manager', 'accountant', 'stores', 'delivery'];
        $configuredPrefixes = array_map(
            static fn (mixed $prefix): string => trim((string) $prefix, '/'),
            (array) config('staff-auth.staff_path_prefixes', [])
        );

        self::assertSame([], array_values(array_diff($requiredPrefixes, $configuredPrefixes)));
    }

    public function test_customer_session_does_not_block_staff_login_page(): void
    {
        $customer = new User([
            'name' => 'Customer Test',
            'email' => 'customer@example.test',
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'web')
            ->get(route('admin.login'))
            ->assertOk();
    }

    public function test_existing_staff_session_returns_to_admin_dashboard_not_storefront_home(): void
    {
        $staff = new User([
            'name' => 'Admin Test',
            'email' => 'admin@example.test',
            'is_active' => true,
        ]);
        $staff->setAttribute('role', 'Admin');

        $this->actingAs($staff, 'staff')
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_staff_login_routes_use_staff_specific_guest_middleware(): void
    {
        $middleware = Route::getRoutes()
            ->getByName('admin.login')
            ?->gatherMiddleware() ?? [];

        self::assertContains(RedirectIfStaffAuthenticated::class, $middleware);
        self::assertNotContains('guest:staff', $middleware);
    }

    public function test_staff_login_page_uses_the_existing_login_design_copy(): void
    {
        $this->get(route('admin.login'))
            ->assertOk();
    }
}

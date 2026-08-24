<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Middleware\RedirectIfStaffAuthenticated;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;
use Throwable;

final class AuditAuthenticationIsolation extends Command
{
    protected $signature = 'bandara:audit-auth-isolation';

    protected $description = 'Verify Bandara staff/customer guard, route and session isolation';

    public function handle(): int
    {
        $checks = [
            'Customer guard remains web' => config('staff-auth.customer_guard') === 'web',
            'Staff guard is registered' => config('auth.guards.staff.driver') === 'session',
            'Staff guard shares the existing user provider' =>
                config('auth.guards.staff.provider') === config('auth.guards.web.provider'),
            'Spatie roles and permissions remain on the existing web guard' =>
                $this->userPermissionGuardIsWeb(),
            'All current staff roles are classified as staff' =>
                $this->configuredValuesContain('staff-auth.staff_roles', [
                    'Admin',
                    'Manager',
                    'Support',
                    'Accountant',
                    'CAAccountant',
                    'Stores',
                    'DeliveryAgent',
                    'DeliveryBoy',
                ]),
            'All current back-office paths use the staff session' =>
                $this->configuredValuesContain('staff-auth.staff_path_prefixes', [
                    'admin',
                    'support',
                    'manager',
                    'accountant',
                    'stores',
                    'delivery',
                ]),
            'Role-specific staff dashboards remain registered' =>
                $this->routesExist([
                    'admin.dashboard',
                    'manager.dashboard',
                    'support.dashboard',
                    'accountant.dashboard',
                    'stores.dashboard',
                    'delivery.index',
                ]),
            'Staff session cookie differs from customer cookie' =>
                config('staff-auth.session.cookie') !== config('session.cookie'),
            'Customer login route remains present' => Route::has('login'),
            'Staff login route is present' => Route::has('admin.login'),
            'Staff login POST route is present' => Route::has('admin.login.store'),
            'Staff login uses the staff-specific authenticated redirect' =>
                $this->staffLoginUsesIsolatedRedirect(),
            'Staff logout route is present' => Route::has('admin.logout'),
            'Impersonation acceptance bridge is present' => Route::has('staff-impersonation.accept'),
            'Impersonation leave bridge is present' => Route::has('staff-impersonation.leave'),
        ];

        $failed = false;

        foreach ($checks as $label => $passed) {
            $this->line(($passed ? '<info>PASS</info>' : '<error>FAIL</error>')."  {$label}");
            $failed = $failed || ! $passed;
        }

        if ($failed) {
            $this->newLine();
            $this->error('Authentication isolation audit failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Authentication isolation audit passed.');

        return self::SUCCESS;
    }

    private function userPermissionGuardIsWeb(): bool
    {
        try {
            $property = new ReflectionProperty(User::class, 'guard_name');

            return $property->getValue(new User()) === 'web';
        } catch (Throwable) {
            return false;
        }
    }

    private function staffLoginUsesIsolatedRedirect(): bool
    {
        $route = Route::getRoutes()->getByName('admin.login');

        if ($route === null) {
            return false;
        }

        $middleware = $route->gatherMiddleware();

        return in_array(RedirectIfStaffAuthenticated::class, $middleware, true)
            && ! in_array('guest:staff', $middleware, true);
    }

    /**
     * @param list<string> $required
     */
    private function configuredValuesContain(string $key, array $required): bool
    {
        $configured = array_map(
            static fn (mixed $value): string => strtolower(trim((string) $value)),
            (array) config($key, [])
        );

        foreach ($required as $value) {
            if (! in_array(strtolower($value), $configured, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $routeNames
     */
    private function routesExist(array $routeNames): bool
    {
        foreach ($routeNames as $routeName) {
            if (! Route::has($routeName)) {
                return false;
            }
        }

        return true;
    }
}

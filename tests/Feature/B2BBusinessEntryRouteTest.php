<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class B2BBusinessEntryRouteTest extends TestCase
{
    #[Test]
    public function introduction_is_public_but_application_pages_remain_authenticated(): void
    {
        $public = $this->namedRoute('business-account.index');
        $application = $this->namedRoute('account.business-application.show');

        $this->assertNotNull($public);
        $this->assertNotNull($application);
        $this->assertFalse($this->containsAuth($public->gatherMiddleware()));
        $this->assertTrue($this->containsAuth($application->gatherMiddleware()));
    }

    #[Test]
    public function dedicated_business_entry_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('business-account.login'));
        $this->assertTrue(Route::has('business-account.register'));
        $this->assertTrue(Route::has('business-account.continue'));
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('register'));
    }

    private function namedRoute(string $name): ?IlluminateRoute
    {
        foreach (Route::getRoutes() as $route) {
            if ($route instanceof IlluminateRoute && $route->getName() === $name) {
                return $route;
            }
        }

        return null;
    }

    /** @param array<int, string> $middleware */
    private function containsAuth(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            if ($entry === 'auth' || str_starts_with($entry, 'auth:') || str_contains($entry, 'Authenticate')) {
                return true;
            }
        }

        return false;
    }
}

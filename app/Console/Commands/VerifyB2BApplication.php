<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\B2B\B2BLocationService;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class VerifyB2BApplication extends Command
{
    protected $signature = 'bandara:b2b-applications:verify {--json : Return machine-readable JSON}';
    protected $description = 'Verify the B2B business-account application module and corrective integration.';

    public function handle(B2BLocationService $locations): int
    {
        $diagnostics = $locations->diagnostics();
        $states = $locations->states();
        $sampleState = null;
        $sampleCityCount = 0;

        foreach ($states->take(100) as $state) {
            $cities = $locations->citiesForState($state->id);

            if ($cities->isNotEmpty()) {
                $sampleState = $state;
                $sampleCityCount = $cities->count();
                break;
            }
        }

        $publicMiddleware = $this->routeMiddleware('business-account.index');
        $applicationMiddleware = $this->routeMiddleware('account.business-application.show');
        $customerLayout = (string) config('b2b_application_corrective.view.customer_layout', '');
        $adminLayout = (string) config('b2b_application_corrective.view.admin_layout', '');

        $checks = [
            'table.b2b_applications' => Schema::hasTable('b2b_applications'),
            'table.b2b_application_histories' => Schema::hasTable('b2b_application_histories'),
            'table.b2b_customer_profiles' => Schema::hasTable('b2b_customer_profiles'),
            'column.users.customer_type' => Schema::hasTable('users') && Schema::hasColumn('users', 'customer_type'),

            'route.business-account.index' => Route::has('business-account.index'),
            'route.business-account.login' => Route::has('business-account.login'),
            'route.business-account.register' => Route::has('business-account.register'),
            'route.business-account.continue' => Route::has('business-account.continue'),
            'route.account.business-application.show' => Route::has('account.business-application.show'),
            'route.admin.b2b-applications.index' => Route::has('admin.b2b-applications.index'),
            'route.existing-login' => Route::has('login'),
            'route.existing-register' => Route::has('register'),
            'route.public-introduction-has-no-auth' => ! $this->containsAuth($publicMiddleware),
            'route.application-remains-authenticated' => $this->containsAuth($applicationMiddleware),

            'location.relation-ready' => (bool) ($diagnostics['ready'] ?? false),
            'location.state-relation-key-code' => ($diagnostics['state_relation_key'] ?? null) === 'code',
            'location.city-state-key-state-code' => ($diagnostics['city_state_key'] ?? null) === 'state_code',
            'location.states-available' => $states->isNotEmpty(),
            'location.cities-load-for-real-state' => $sampleState !== null && $sampleCityCount > 0,

            'view.customer-layout-selected' => $customerLayout !== '',
            'view.admin-layout-selected' => $adminLayout !== '',
            'view.customer-layout-exists' => $customerLayout !== '' && View::exists($customerLayout),
            'view.admin-layout-exists' => $adminLayout !== '' && View::exists($adminLayout),
            'view.public-uses-existing-layout' => $this->viewDoesNotUseStandaloneLayout('business-account/index.blade.php'),
            'view.step-one-uses-existing-layout' => $this->viewDoesNotUseStandaloneLayout('account/business-application/step-one.blade.php'),
            'view.admin-uses-existing-layout' => $this->viewDoesNotUseStandaloneLayout('admin/b2b-applications/index.blade.php'),
        ];

        $payload = [
            'ok' => ! in_array(false, $checks, true),
            'checks' => $checks,
            'location' => $diagnostics + [
                'state_count' => $states->count(),
                'sample_state_id' => $sampleState?->id,
                'sample_state_name' => $sampleState?->name,
                'sample_city_count' => $sampleCityCount,
            ],
            'layouts' => [
                'customer' => $customerLayout,
                'admin' => $adminLayout,
            ],
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
        } else {
            foreach ($checks as $name => $passed) {
                $passed
                    ? $this->components->info($name)
                    : $this->components->error($name);
            }

            if ($sampleState !== null) {
                $this->line(sprintf(
                    'City lookup sample: %s (%s) returned %d cities.',
                    (string) $sampleState->name,
                    (string) $sampleState->id,
                    $sampleCityCount,
                ));
            }
        }

        return $payload['ok'] ? self::SUCCESS : self::FAILURE;
    }

    /** @return list<string> */
    private function routeMiddleware(string $name): array
    {
        foreach (Route::getRoutes() as $route) {
            if ($route instanceof IlluminateRoute && $route->getName() === $name) {
                return array_values(array_map('strval', $route->gatherMiddleware()));
            }
        }

        return [];
    }

    /** @param list<string> $middleware */
    private function containsAuth(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            if ($entry === 'auth'
                || str_starts_with($entry, 'auth:')
                || str_contains($entry, 'Authenticate')) {
                return true;
            }
        }

        return false;
    }

    private function viewDoesNotUseStandaloneLayout(string $relative): bool
    {
        $path = resource_path('views/'.$relative);

        if (! is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        return is_string($contents)
            && ! str_contains($contents, 'x-layouts.business-account')
            && ! str_contains($contents, 'layouts.business-account');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Authentication\AccountTypeResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class StaffImpersonationBridgeController extends Controller
{
    public function start(
        Request $request,
        AccountTypeResolver $accounts,
    ): RedirectResponse {
        abort_unless((bool) config('staff-auth.impersonation.enabled', true), 404);

        $staff = Auth::guard((string) config('staff-auth.staff_guard', 'staff'))->user();

        abort_unless($staff !== null && $accounts->isStaff($staff), 403);

        $allowedRoles = Arr::wrap(config('staff-auth.impersonation.allowed_staff_roles', []));

        if ($allowedRoles !== [] && ! $accounts->hasAnyRole($staff, $allowedRoles)) {
            abort(403);
        }

        $target = $this->resolveTarget($request);

        abort_unless($target !== null, 404);
        abort_if($accounts->isStaff($target), 403);
        abort_if((string) $target->getAuthIdentifier() === (string) $staff->getAuthIdentifier(), 403);

        if (Gate::has('impersonate') && Gate::denies('impersonate', $target)) {
            abort(403);
        }

        $token = Str::random(64);
        $ttl = max(30, (int) config('staff-auth.impersonation.ttl_seconds', 120));

        Cache::put(
            $this->cacheKey($token),
            [
                'staff_id' => (string) $staff->getAuthIdentifier(),
                'customer_id' => (string) $target->getAuthIdentifier(),
                'redirect' => $this->safeLocalPath(
                    (string) $request->input(
                        'redirect',
                        config('staff-auth.impersonation.after_start_path', '/account')
                    )
                ),
            ],
            now()->addSeconds($ttl)
        );

        return redirect()->to(URL::temporarySignedRoute(
            'staff-impersonation.accept',
            now()->addSeconds($ttl),
            ['token' => $token]
        ));
    }

    public function accept(
        Request $request,
        AccountTypeResolver $accounts,
    ): RedirectResponse {
        $token = (string) $request->route('token');
        $payload = Cache::pull($this->cacheKey($token));

        abort_unless(is_array($payload), 410);

        $target = $this->findUser((string) ($payload['customer_id'] ?? ''));

        abort_unless($target !== null && $accounts->isCustomer($target), 403);

        Auth::guard((string) config('staff-auth.customer_guard', 'web'))->login($target);
        $request->session()->regenerate();

        $staffId = (string) ($payload['staff_id'] ?? '');

        /*
         * Bandara keys are authoritative. The additional conventional keys
         * preserve compatibility with existing impersonation banners/helpers.
         */
        $request->session()->put([
            'bandara.impersonation.staff_id' => $staffId,
            'bandara.impersonation.started_at' => now()->toIso8601String(),
            'impersonated_by' => $staffId,
            'impersonator_id' => $staffId,
            'impersonator_guard' => (string) config('staff-auth.staff_guard', 'staff'),
        ]);

        return redirect()->to($this->safeLocalPath(
            (string) ($payload['redirect'] ?? config('staff-auth.impersonation.after_start_path', '/account'))
        ));
    }

    public function leave(Request $request): RedirectResponse
    {
        /*
         * An old leave route can live under /admin and therefore have the
         * staff session selected. Bridge once more to a signed public route so
         * the separate customer session is the one that is terminated.
         */
        if (
            (string) $request->attributes->get('bandara.authentication_context') === 'staff'
            && ! Auth::guard((string) config('staff-auth.customer_guard', 'web'))->check()
        ) {
            return redirect()->to(URL::temporarySignedRoute(
                'staff-impersonation.finish',
                now()->addMinute()
            ));
        }

        return $this->finishCustomerSession($request);
    }

    public function finish(Request $request): RedirectResponse
    {
        return $this->finishCustomerSession($request);
    }

    private function finishCustomerSession(Request $request): RedirectResponse
    {
        Auth::guard((string) config('staff-auth.customer_guard', 'web'))->logout();

        $request->session()->forget([
            'bandara.impersonation.staff_id',
            'bandara.impersonation.started_at',
            'impersonated_by',
            'impersonator_id',
            'impersonator_guard',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $route = Route::has('admin.dashboard')
            ? route('admin.dashboard')
            : url((string) config('staff-auth.impersonation.after_leave_path', '/admin'));

        return redirect()->to($route);
    }

    private function resolveTarget(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model && method_exists($parameter, 'getAuthIdentifier')) {
                return $parameter;
            }
        }

        foreach ([
            'user',
            'customer',
            'customer_id',
            'user_id',
            'id',
            'impersonate',
        ] as $key) {
            $value = $request->route($key) ?? $request->input($key);

            if ($value instanceof Model && method_exists($value, 'getAuthIdentifier')) {
                return $value;
            }

            if (is_scalar($value) && (string) $value !== '') {
                $user = $this->findUser((string) $value);

                if ($user !== null) {
                    return $user;
                }
            }
        }

        return null;
    }

    private function findUser(string $id): ?Model
    {
        if ($id === '') {
            return null;
        }

        $guard = (string) config('staff-auth.customer_guard', 'web');
        $provider = (string) config("auth.guards.{$guard}.provider", 'users');
        $modelClass = config("auth.providers.{$provider}.model");

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return null;
        }

        try {
            $user = $modelClass::query()->find($id);

            return $user instanceof Model ? $user : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function cacheKey(string $token): string
    {
        return 'bandara:staff-impersonation:'.hash('sha256', $token);
    }

    private function safeLocalPath(string $path): string
    {
        $path = trim($path);

        if (
            $path === ''
            || Str::startsWith($path, ['//', 'http://', 'https://'])
            || ! Str::startsWith($path, '/')
        ) {
            return '/';
        }

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\Authentication\AccountTypeResolver;
use App\Support\Authentication\StaffDashboardResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class StaffAuthenticatedSessionController extends Controller
{
    public function create(StaffDashboardResolver $dashboards): View|RedirectResponse
    {
        $user = Auth::guard($this->guard())->user();

        if ($user !== null) {
            return redirect()->to($dashboards->url($user));
        }

        return view('admin.auth.login', [
            'authArea' => 'staff',
            'isStaffLogin' => true,
            'loginAction' => route('admin.login.store'),
        ]);
    }

    public function store(
        Request $request,
        AccountTypeResolver $accounts,
        StaffDashboardResolver $dashboards,
    ): RedirectResponse {
        $request->validate([
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $errorField = $this->loginField($request);
        $guard = Auth::guard($this->guard());
        $authenticated = false;

        foreach ($this->credentialCandidates($request) as [$column, $identity]) {
            if (! $this->userTableHasColumn($column)) {
                continue;
            }

            try {
                if ($guard->attempt(
                    [$column => $identity, 'password' => (string) $request->input('password')],
                    $request->boolean('remember')
                )) {
                    $authenticated = true;
                    break;
                }
            } catch (Throwable) {
                // Try the next configured login identifier.
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                $errorField => trans('auth.failed'),
            ]);
        }

        $user = $guard->user();

        if ($user === null || ! $accounts->isStaff($user)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $errorField => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->to($dashboards->destinationAfterLogin($request, $user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard($this->guard())->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    private function credentialCandidates(Request $request): array
    {
        $candidates = [];

        foreach (Arr::wrap(config('staff-auth.login_columns', ['email'])) as $column) {
            $column = (string) $column;
            $value = trim((string) $request->input($column, ''));

            if ($value !== '') {
                $candidates[] = [$column, $value];
            }
        }

        $generic = trim((string) $request->input('login', ''));

        if ($generic !== '') {
            foreach (Arr::wrap(config('staff-auth.login_columns', ['email'])) as $column) {
                $candidates[] = [(string) $column, $generic];
            }
        }

        return array_values(array_unique($candidates, SORT_REGULAR));
    }

    private function loginField(Request $request): string
    {
        foreach (Arr::wrap(config('staff-auth.login_columns', ['email'])) as $field) {
            if ($request->exists((string) $field)) {
                return (string) $field;
            }
        }

        return $request->exists('login') ? 'login' : 'email';
    }

    private function userTableHasColumn(string $column): bool
    {
        $modelClass = $this->userModelClass();

        if ($modelClass === null || ! class_exists($modelClass)) {
            return false;
        }

        try {
            /** @var Model $model */
            $model = new $modelClass();

            return Schema::hasColumn($model->getTable(), $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function userModelClass(): ?string
    {
        $provider = (string) config("auth.guards.{$this->guard()}.provider", 'users');
        $model = config("auth.providers.{$provider}.model");

        return is_string($model) && $model !== '' ? $model : null;
    }

    private function guard(): string
    {
        return (string) config('staff-auth.staff_guard', 'staff');
    }

}

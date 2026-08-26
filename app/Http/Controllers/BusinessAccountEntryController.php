<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\B2BApplication;
use App\Support\B2BApplicationAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BusinessAccountEntryController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('business-account.continue');
        }

        $this->rememberIntent($request, 'business_login');

        return redirect()->route('login');
    }

    public function register(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('business-account.continue');
        }

        $this->rememberIntent($request, 'business_registration');

        return redirect()->route('register');
    }

    public function resume(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->clearIntent($request);

        if (B2BApplicationAccess::isB2B($user)) {
            return redirect()->route('account.business-application.show');
        }

        $application = B2BApplication::query()
            ->where('user_id', $user->getKey())
            ->first();

        if (! $application) {
            return redirect()->route('account.business-application.step-one');
        }

        if ($application->status->customerCanEdit()) {
            $businessDetailsComplete = filled($application->legal_business_name)
                && filled($application->address_line_1)
                && filled($application->state_id)
                && filled($application->city_id);

            return redirect()->route($businessDetailsComplete
                ? 'account.business-application.step-two'
                : 'account.business-application.step-one');
        }

        return redirect()->route('account.business-application.show');
    }

    private function rememberIntent(Request $request, string $source): void
    {
        $key = (string) config(
            'b2b_application_corrective.entry_intent.session_key',
            'bandara.business_account_intent',
        );

        $request->session()->put($key, [
            'source' => $source,
            'started_at' => now()->timestamp,
        ]);

        if (Route::has('business-account.continue')) {
            $request->session()->put('url.intended', route('business-account.continue'));
        }
    }

    private function clearIntent(Request $request): void
    {
        $request->session()->forget((string) config(
            'b2b_application_corrective.entry_intent.session_key',
            'bandara.business_account_intent',
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterBusinessAccountRequest;
use App\Models\User;
use App\Services\B2B\B2BApplicationWorkflowService;
use App\Services\B2B\B2BLocationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BusinessAccountRegistrationController extends Controller
{
    public function __construct(
        private readonly B2BApplicationWorkflowService $workflow,
        private readonly B2BLocationService $locations,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('business-account.continue');
        }

        $this->clearLegacyRegistrationIntent($request);

        $states = $this->locations->states();
        $selectedStateId = old('state_id');
        $cities = $this->locations->citiesForState(
            is_numeric($selectedStateId) ? (int) $selectedStateId : null,
        );

        return view('business-account.register', compact('states', 'cities'));
    }

    public function store(RegisterBusinessAccountRequest $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('business-account.continue');
        }

        $this->clearLegacyRegistrationIntent($request);
        $data = $request->validated();
        $stateName = $this->locations->stateName((int) $data['state_id']);
        $cityName = $this->locations->cityName((int) $data['city_id']);

        if ($stateName === null || $cityName === null) {
            throw ValidationException::withMessages([
                'city_id' => 'The selected state or city is no longer available. Please select it again.',
            ]);
        }

        try {
            /** @var User $user */
            $user = DB::transaction(function () use ($data, $stateName, $cityName): User {
                $attributes = [
                    'name' => trim($data['contact_first_name'].' '.($data['contact_last_name'] ?? '')),
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'phone' => $data['phone'],
                    'customer_type' => (string) config('b2b_application.customer_type.b2c', 'b2c'),
                ];

                if (Schema::hasColumn('users', 'date_of_birth')) {
                    $attributes['date_of_birth'] = null;
                }

                if (Schema::hasColumn('users', 'is_active')) {
                    $attributes['is_active'] = true;
                }

                $user = new User();
                $user->forceFill($attributes);
                $user->save();

                $this->assignCustomerRole($user);

                $this->workflow->saveBusinessDetails($user, [
                    'contact_first_name' => $data['contact_first_name'],
                    'contact_last_name' => $data['contact_last_name'] ?? null,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'preferred_contact_method' => $data['preferred_contact_method'],
                    'legal_business_name' => $data['legal_business_name'],
                    'trading_name' => $data['trading_name'] ?? null,
                    'business_type' => $data['business_type'],
                    'gst_registered' => (bool) $data['gst_registered'],
                    'gstin' => $data['gstin'] ?? null,
                    'pan' => $data['pan'] ?? null,
                    'fssai_number' => $data['fssai_number'] ?? null,
                    'website' => $data['website'] ?? null,
                    'address_line_1' => $data['address_line_1'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'state_id' => (int) $data['state_id'],
                    'city_id' => (int) $data['city_id'],
                    'state_name' => $stateName,
                    'city_name' => $cityName,
                    'postal_code' => $data['postal_code'],
                ]);

                return $user;
            }, 3);
        } catch (QueryException $exception) {
            if ($this->isDuplicateEmail($exception)) {
                throw ValidationException::withMessages([
                    'email' => 'An account already exists for this email. Please sign in and apply using the existing account.',
                ]);
            }

            throw $exception;
        }

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $this->clearLegacyRegistrationIntent($request);

        return redirect()
            ->route('account.business-application.step-two')
            ->with(
                'success',
                'Your business login and draft application were created. Please complete the purchase requirements.',
            );
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state_id' => ['required', 'integer'],
        ]);

        $cities = $this->locations
            ->citiesForState((int) $validated['state_id'])
            ->map(static fn (object $city): array => [
                'id' => $city->id,
                'name' => (string) $city->name,
            ])
            ->values();

        return response()->json($cities);
    }

    private function assignCustomerRole(User $user): void
    {
        if (! method_exists($user, 'assignRole')) {
            throw new RuntimeException('The User model does not provide the existing role-assignment method.');
        }

        $roleName = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereRaw('LOWER(name) = ?', ['customer'])
            ->value('name');

        if (! is_string($roleName) || $roleName === '') {
            throw new RuntimeException('The existing Customer role could not be found.');
        }

        $user->assignRole($roleName);
    }

    private function clearLegacyRegistrationIntent(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->forget((string) config(
            'b2b_application_corrective.entry_intent.session_key',
            'bandara.business_account_intent',
        ));
    }

    private function isDuplicateEmail(QueryException $exception): bool
    {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = mb_strtolower($exception->getMessage());

        return $driverCode === 1062
            || ($sqlState === '23000' && str_contains($message, 'email'))
            || ($sqlState === '19' && str_contains($message, 'email'));
    }
}

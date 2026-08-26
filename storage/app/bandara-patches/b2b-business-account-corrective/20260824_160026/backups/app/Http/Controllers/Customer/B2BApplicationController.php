<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SaveB2BApplicationBusinessRequest;
use App\Http\Requests\Customer\SaveB2BApplicationRequirementsRequest;
use App\Models\B2BApplication;
use App\Services\B2B\B2BApplicationWorkflowService;
use App\Services\B2B\B2BLocationService;
use App\Support\B2BApplicationAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class B2BApplicationController extends Controller
{
    public function __construct(
        private readonly B2BApplicationWorkflowService $workflow,
        private readonly B2BLocationService $locations,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $application = $this->applicationFor($request);
        $isB2B = B2BApplicationAccess::isB2B($request->user());

        if (! $application && ! $isB2B) {
            return redirect()->route('account.business-application.step-one');
        }

        $application?->load(['histories' => static fn ($query) => $query->where('visibility', 'customer'), 'profile']);

        return view('account.business-application.show', compact('application', 'isB2B'));
    }

    public function stepOne(Request $request): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (B2BApplicationAccess::isB2B($request->user()) || ($application && ! $application->status->customerCanEdit())) {
            return redirect()->route('account.business-application.show');
        }

        $states = $this->locations->states();
        $cities = $this->locations->citiesForState(old('state_id', $application?->state_id));
        $defaults = $this->contactDefaults($request);

        return view('account.business-application.step-one', compact('application', 'states', 'cities', 'defaults'));
    }

    public function saveStepOne(SaveB2BApplicationBusinessRequest $request): RedirectResponse
    {
        if (B2BApplicationAccess::isB2B($request->user())) {
            return redirect()->route('account.business-application.show');
        }

        $data = $request->validated();
        $data['state_name'] = $this->locations->stateName($data['state_id']) ?: 'Unknown state';
        $data['city_name'] = $this->locations->cityName($data['city_id']) ?: 'Unknown city';
        $application = $this->workflow->saveBusinessDetails($request->user(), $data);

        return redirect()
            ->route('account.business-application.step-two')
            ->with('success', 'Business details saved. Please complete your purchase requirements.');
    }

    public function stepTwo(Request $request): View|RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()->route('account.business-application.step-one');
        }

        if (B2BApplicationAccess::isB2B($request->user()) || ! $application->status->customerCanEdit()) {
            return redirect()->route('account.business-application.show');
        }

        return view('account.business-application.step-two', compact('application'));
    }

    public function saveStepTwo(SaveB2BApplicationRequirementsRequest $request): RedirectResponse
    {
        $application = $this->applicationFor($request);

        if (! $application) {
            return redirect()->route('account.business-application.step-one');
        }

        $data = $request->validated();
        unset($data['intent'], $data['terms_accepted']);

        if ($request->boolean('terms_accepted')) {
            $data['terms_accepted_at'] = now();
        }

        $application = $this->workflow->saveRequirements($request->user(), $application, $data);

        if ($request->input('intent') === 'submit') {
            $application = $this->workflow->submit($request->user(), $application);

            return redirect()
                ->route('account.business-application.show')
                ->with('success', 'Your business account application has been submitted for review.');
        }

        return redirect()
            ->route('account.business-application.show')
            ->with('success', $application->status->value === 'more_information_required'
                ? 'Your changes were saved. Submit the application when the requested information is complete.'
                : 'Your application was saved as a draft.');
    }

    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate(['state_id' => ['required', 'integer']]);

        return response()->json($this->locations->citiesForState($validated['state_id'])->values());
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $application = $this->applicationFor($request);
        abort_unless($application, 404);
        $this->workflow->withdraw($request->user(), $application);

        return redirect()->route('account.business-application.show')->with('success', 'Your application has been withdrawn.');
    }

    public function restart(Request $request): RedirectResponse
    {
        $application = $this->applicationFor($request);
        abort_unless($application, 404);
        $this->workflow->restart($request->user(), $application);

        return redirect()->route('account.business-application.step-one')->with('success', 'You can now update and resubmit your application.');
    }

    private function applicationFor(Request $request): ?B2BApplication
    {
        return B2BApplication::query()->where('user_id', $request->user()->getKey())->first();
    }

    private function contactDefaults(Request $request): array
    {
        $user = $request->user();
        $name = trim((string) ($user->name ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [];

        return [
            'contact_first_name' => $parts[0] ?? (string) ($user->first_name ?? ''),
            'contact_last_name' => $parts[1] ?? (string) ($user->last_name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'phone' => (string) ($user->phone ?? $user->mobile ?? ''),
            'whatsapp' => (string) ($user->whatsapp ?? $user->whatsapp_number ?? ''),
        ];
    }
}

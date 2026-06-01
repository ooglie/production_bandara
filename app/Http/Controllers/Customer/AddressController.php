<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $addresses = CustomerAddress::where('user_id', $user->id)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->latest()
            ->get();

        return view('customer.addresses.index', compact('addresses'));
    }

    public function create(Request $request)
    {
        $this->syncReturnToFromRequest($request);

        $address = new CustomerAddress();

        $states = $this->indiaStates();

        $mh = $states->firstWhere('code', 'MH');
        $defaultStateCode = $mh?->code ?? ($states->first()->code ?? 'MH');

        $selectedStateCode = strtoupper(trim((string) old('state_code', $defaultStateCode)));
        $cities = $this->indiaCitiesForState($selectedStateCode);

        return view('customer.addresses.create', compact('address', 'states', 'cities', 'selectedStateCode'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $this->validatedData($request);

        $stateCode = strtoupper(trim((string) $data['state_code']));
        $stateName = $this->indiaStateName($stateCode);

        if (! $stateName) {
            return back()
                ->withErrors(['state_code' => 'Please select a valid state.'])
                ->withInput();
        }

        $alreadyHasAddresses = CustomerAddress::where('user_id', $user->id)->exists();

        $isDefaultShipping = $request->boolean('is_default_shipping');
        $isDefaultBilling  = $request->boolean('is_default_billing');

        if (! $alreadyHasAddresses) {
            $isDefaultShipping = true;
            $isDefaultBilling = true;
        }

        $createdAddress = null;

        DB::transaction(function () use (
            $user,
            $data,
            $stateCode,
            $stateName,
            $isDefaultShipping,
            $isDefaultBilling,
            &$createdAddress
        ) {
            if ($isDefaultShipping) {
                CustomerAddress::where('user_id', $user->id)->update(['is_default_shipping' => false]);
            }

            if ($isDefaultBilling) {
                CustomerAddress::where('user_id', $user->id)->update(['is_default_billing' => false]);
            }

            $createdAddress = CustomerAddress::create([
                'user_id'             => $user->id,
                'full_name'           => $data['full_name'],
                'phone'               => $data['phone'],
                'address_line1'       => $data['address_line1'],
                'address_line2'       => $data['address_line2'] ?? null,
                'city'                => $data['city'],
                'state'               => $stateName,
                'state_code'          => $stateCode,
                'country'             => 'India',
                'pincode'             => $data['pincode'],
                'gstin'               => $data['gstin'] ?? null,
                'is_default_shipping' => $isDefaultShipping,
                'is_default_billing'  => $isDefaultBilling,
            ]);
        });

        return $this->redirectAfterAddressSaved(
            $request,
            $createdAddress,
            'Address added successfully.'
        );
    }

    public function edit(Request $request, CustomerAddress $address)
    {
        $this->authorizeAddress($request, $address);
        $this->syncReturnToFromRequest($request);

        $states = $this->indiaStates();

        $selectedStateCode = strtoupper(trim((string) old('state_code', $address->state_code ?? '')));

        if ($selectedStateCode === '' && ! empty($address->state)) {
            $match = $states->first(function ($s) use ($address) {
                return isset($s->name) && strcasecmp((string) $s->name, (string) $address->state) === 0;
            });

            if ($match && ! empty($match->code)) {
                $selectedStateCode = strtoupper((string) $match->code);
            }
        }

        $cities = $this->indiaCitiesForState($selectedStateCode);

        return view('customer.addresses.edit', compact('address', 'states', 'cities', 'selectedStateCode'));
    }

    public function update(Request $request, CustomerAddress $address)
    {
        $this->authorizeAddress($request, $address);

        $user = $request->user();
        $data = $this->validatedData($request);

        $stateCode = strtoupper(trim((string) $data['state_code']));
        $stateName = $this->indiaStateName($stateCode);

        if (! $stateName) {
            return back()
                ->withErrors(['state_code' => 'Please select a valid state.'])
                ->withInput();
        }

        $isDefaultShipping = $request->boolean('is_default_shipping');
        $isDefaultBilling  = $request->boolean('is_default_billing');

        DB::transaction(function () use ($user, $address, $data, $stateCode, $stateName, $isDefaultShipping, $isDefaultBilling) {
            if ($isDefaultShipping) {
                CustomerAddress::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default_shipping' => false]);
            }

            if ($isDefaultBilling) {
                CustomerAddress::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default_billing' => false]);
            }

            $address->update([
                'full_name'           => $data['full_name'],
                'phone'               => $data['phone'],
                'address_line1'       => $data['address_line1'],
                'address_line2'       => $data['address_line2'] ?? null,
                'city'                => $data['city'],
                'state'               => $stateName,
                'state_code'          => $stateCode,
                'country'             => 'India',
                'pincode'             => $data['pincode'],
                'gstin'               => $data['gstin'] ?? null,
                'is_default_shipping' => $isDefaultShipping,
                'is_default_billing'  => $isDefaultBilling,
            ]);
        });

        return $this->redirectAfterAddressSaved(
            $request,
            $address,
            'Address updated successfully.'
        );
    }

    public function destroy(Request $request, CustomerAddress $address)
    {
        $this->authorizeAddress($request, $address);

        $address->delete();

        return redirect()
            ->to($this->addressesIndexUrl())
            ->with('status', 'Address removed.');
    }

    public function cities(Request $request)
    {
        $stateCode = strtoupper(trim((string) $request->get('state_code', '')));

        if ($stateCode === '') {
            return response()->json(['ok' => true, 'cities' => []]);
        }

        $cities = DB::table('cities')
            ->where('country_code', 'IN')
            ->where('state_code', $stateCode)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json(['ok' => true, 'cities' => $cities]);
    }

    protected function authorizeAddress(Request $request, CustomerAddress $address): void
    {
        if ($address->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }
    }

    protected function validatedData(Request $request): array
    {
        $stateCode = strtoupper(trim((string) $request->input('state_code', '')));

        return $request->validate([
            'full_name'      => ['required', 'string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'address_line1'  => ['required', 'string', 'max:255'],
            'address_line2'  => ['nullable', 'string', 'max:255'],

            'state_code' => [
                'required',
                'string',
                'max:10',
                Rule::exists('states', 'code')->where(function ($q) {
                    $q->where('country_code', 'IN')
                      ->where('is_active', true);
                }),
            ],

            'city' => [
                'required',
                'string',
                'max:255',
                Rule::exists('cities', 'name')->where(function ($q) use ($stateCode) {
                    $q->where('country_code', 'IN')
                      ->where('state_code', $stateCode)
                      ->where('is_active', true);
                }),
            ],

            'pincode'        => ['required', 'string', 'max:20'],
            'gstin'          => ['nullable', 'string', 'max:20'],

            'is_default_shipping' => ['nullable', 'boolean'],
            'is_default_billing'  => ['nullable', 'boolean'],
        ]);
    }

    protected function syncReturnToFromRequest(Request $request): void
    {
        $returnTo = $this->sanitizeReturnUrl($request, $request->query('return_to'));

        if ($returnTo) {
            $request->session()->put($this->addressReturnToSessionKey(), $returnTo);
            return;
        }

        if (! $request->has('return_to')) {
            $request->session()->forget($this->addressReturnToSessionKey());
        }
    }

    protected function redirectAfterAddressSaved(Request $request, CustomerAddress $address, string $message)
    {
        $returnTo = $request->session()->pull($this->addressReturnToSessionKey());

        if (is_string($returnTo) && trim($returnTo) !== '') {
            $returnTo = $this->appendQueryParameter($returnTo, 'address_id', (string) $address->id);

            return redirect()
                ->to($returnTo)
                ->with('status', $message . ' Please review and place your order.');
        }

        return redirect()
            ->to($this->addressesIndexUrl())
            ->with('status', $message);
    }

    protected function addressReturnToSessionKey(): string
    {
        return 'customer.addresses.return_to';
    }

    protected function addressesIndexUrl(): string
    {
        if (Route::has('account.addresses.index')) {
            return route('account.addresses.index', [], false);
        }

        if (Route::has('customer.addresses.index')) {
            return route('customer.addresses.index', [], false);
        }

        return '/account/addresses';
    }

    protected function sanitizeReturnUrl(Request $request, ?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $allowedHosts = array_filter([
            parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST),
            parse_url(config('app.url'), PHP_URL_HOST),
            '127.0.0.1',
            'localhost',
        ]);

        if (! in_array($parts['host'], $allowedHosts, true)) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }

    protected function appendQueryParameter(string $url, string $key, string $value): string
    {
        $parts = parse_url($url);

        $path = $parts['path'] ?? '/';
        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query[$key] = $value;

        $queryString = http_build_query($query);
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . ($queryString !== '' ? '?' . $queryString : '') . $fragment;
    }

    private function indiaStates()
    {
        return DB::table('states')
            ->select(['code', 'name'])
            ->where('country_code', 'IN')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function indiaStateName(string $stateCode): ?string
    {
        $stateCode = strtoupper(trim($stateCode));
        if ($stateCode === '') {
            return null;
        }

        $name = DB::table('states')
            ->where('country_code', 'IN')
            ->where('is_active', true)
            ->where('code', $stateCode)
            ->value('name');

        return $name ? (string) $name : null;
    }

    private function indiaCitiesForState(?string $stateCode)
    {
        $stateCode = strtoupper(trim((string) $stateCode));
        if ($stateCode === '') {
            return collect();
        }

        return DB::table('cities')
            ->where('country_code', 'IN')
            ->where('state_code', $stateCode)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values();
    }
}
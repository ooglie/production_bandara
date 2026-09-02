<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Services\B2B\B2BLocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterBusinessAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = mb_strtolower(trim((string) $this->input('email')));
        $gstRaw = $this->input('gst_registered');
        $gstWasSelected = $this->exists('gst_registered') && $gstRaw !== '' && $gstRaw !== null;
        $gstRegistered = in_array(
            $gstRaw,
            [true, 1, '1', 'true', 'yes'],
            true,
        );

        $this->merge([
            'contact_first_name' => trim((string) $this->input('contact_first_name')),
            'contact_last_name' => $this->filled('contact_last_name')
                ? trim((string) $this->input('contact_last_name'))
                : null,
            'email' => $email,
            'phone' => trim((string) $this->input('phone')),
            'whatsapp' => $this->filled('whatsapp')
                ? trim((string) $this->input('whatsapp'))
                : null,
            'legal_business_name' => trim((string) $this->input('legal_business_name')),
            'trading_name' => $this->filled('trading_name')
                ? trim((string) $this->input('trading_name'))
                : null,
            'gst_registered' => $gstWasSelected ? ($gstRegistered ? 1 : 0) : null,
            'gstin' => $gstRegistered && $this->filled('gstin')
                ? strtoupper((string) preg_replace('/\s+/', '', (string) $this->input('gstin')))
                : null,
            'pan' => $this->filled('pan')
                ? strtoupper((string) preg_replace('/\s+/', '', (string) $this->input('pan')))
                : null,
            'fssai_number' => $this->filled('fssai_number')
                ? (string) preg_replace('/\D+/', '', (string) $this->input('fssai_number'))
                : null,
            'website' => $this->filled('website')
                ? trim((string) $this->input('website'))
                : null,
            'address_line_1' => trim((string) $this->input('address_line_1')),
            'address_line_2' => $this->filled('address_line_2')
                ? trim((string) $this->input('address_line_2'))
                : null,
            'postal_code' => trim((string) $this->input('postal_code')),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $stateRules = ['required', 'integer'];
        $cityRules = ['required', 'integer'];
        $location = (array) config('b2b_application_corrective.location', []);
        $states = (array) ($location['states'] ?? []);
        $cities = (array) ($location['cities'] ?? []);
        $stateTable = (string) ($states['table'] ?? 'states');
        $stateId = (string) ($states['id'] ?? 'id');
        $cityTable = (string) ($cities['table'] ?? 'cities');
        $cityId = (string) ($cities['id'] ?? 'id');

        if (Schema::hasTable($stateTable)) {
            $stateRules[] = Rule::exists($stateTable, $stateId);
        }

        if (Schema::hasTable($cityTable)) {
            $cityRules[] = Rule::exists($cityTable, $cityId);
        }

        return [
            'contact_first_name' => ['required', 'string', 'max:100'],
            'contact_last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'whatsapp' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'preferred_contact_method' => ['required', Rule::in(['phone', 'whatsapp', 'email'])],
            'password' => ['required', 'confirmed', Password::defaults()],

            'legal_business_name' => ['required', 'string', 'max:191'],
            'trading_name' => ['nullable', 'string', 'max:191'],
            'business_type' => [
                'required',
                Rule::in(array_keys((array) config('b2b_application.business_types', []))),
            ],
            'gst_registered' => ['required', 'boolean'],
            'gstin' => [
                Rule::requiredIf($this->boolean('gst_registered')),
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
            ],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'fssai_number' => ['nullable', 'string', 'size:14', 'regex:/^[0-9]{14}$/'],
            'website' => ['nullable', 'url:http,https', 'max:500'],

            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'state_id' => $stateRules,
            'city_id' => $cityRules,
            'postal_code' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('state_id') || $validator->errors()->has('city_id')) {
                    return;
                }

                $locations = app(B2BLocationService::class);

                if (! $locations->cityBelongsToState(
                    $this->integer('city_id'),
                    $this->integer('state_id'),
                )) {
                    $validator->errors()->add(
                        'city_id',
                        'The selected city does not belong to the selected state.',
                    );
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account already exists for this email. Please sign in and apply using the existing account.',
            'gstin.required' => 'GSTIN is required when the business is registered for GST.',
            'gstin.regex' => 'Enter a valid 15-character GSTIN.',
            'pan.regex' => 'Enter a valid PAN.',
            'fssai_number.regex' => 'Enter a valid 14-digit FSSAI number.',
            'postal_code.regex' => 'Enter a valid six-digit Indian PIN code.',
        ];
    }
}

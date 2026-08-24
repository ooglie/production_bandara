<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Services\B2B\B2BLocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveB2BApplicationBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gst_registered' => $this->boolean('gst_registered'),
            'gstin' => $this->filled('gstin') ? strtoupper(preg_replace('/\s+/', '', (string) $this->input('gstin'))) : null,
            'pan' => $this->filled('pan') ? strtoupper(preg_replace('/\s+/', '', (string) $this->input('pan'))) : null,
            'fssai_number' => $this->filled('fssai_number') ? preg_replace('/\D+/', '', (string) $this->input('fssai_number')) : null,
        ]);
    }

    public function rules(): array
    {
        $stateRules = ['required', 'integer'];
        $cityRules = ['required', 'integer'];
        $state = (array) config('b2b_application.location.states', []);
        $city = (array) config('b2b_application.location.cities', []);

        if (is_string($state['table'] ?? null) && Schema::hasTable($state['table'])) {
            $stateRules[] = Rule::exists($state['table'], (string) ($state['id'] ?? 'id'));
        }
        if (is_string($city['table'] ?? null) && Schema::hasTable($city['table'])) {
            $cityRules[] = Rule::exists($city['table'], (string) ($city['id'] ?? 'id'));
        }

        return [
            'contact_first_name' => ['required', 'string', 'max:100'],
            'contact_last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'whatsapp' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{7,32}$/'],
            'preferred_contact_method' => ['required', Rule::in(['phone', 'whatsapp', 'email'])],
            'legal_business_name' => ['required', 'string', 'max:191'],
            'trading_name' => ['nullable', 'string', 'max:191'],
            'business_type' => ['required', Rule::in(array_keys((array) config('b2b_application.business_types', [])))],
            'gst_registered' => ['required', 'boolean'],
            'gstin' => [Rule::requiredIf($this->boolean('gst_registered')), 'nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('state_id') && ! $validator->errors()->has('city_id')) {
                    $locations = app(B2BLocationService::class);
                    if (! $locations->cityBelongsToState($this->integer('city_id'), $this->integer('state_id'))) {
                        $validator->errors()->add('city_id', 'The selected city does not belong to the selected state.');
                    }
                }
            },
        ];
    }
}

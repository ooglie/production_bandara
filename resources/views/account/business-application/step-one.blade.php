@extends('layouts.customer')

@section('title', 'Business Account Application')

@section('content')
@php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $container = $ui['container'] ?? 'space-y-6';
    $panel = $ui['panel'] ?? 'border p-4';
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $subheading = $ui['subheading'] ?? 'text-lg font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $labelClass = $ui['label'] ?? 'block text-sm';
    $fieldClass = $ui['field'] ?? 'block w-full';
    $checkboxClass = $ui['checkbox'] ?? '';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center px-4 py-2';
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center px-4 py-2';
    $errorClass = $ui['alert_error'] ?? 'border p-3';
@endphp

<div class="{{ $container }}">
    <div>
        <p class="{{ $muted }}">Step 1 of 2</p>
        <h1 class="mt-1 {{ $heading }}">Business and contact details</h1>
        <p class="mt-2 {{ $text }}">You are applying from your existing Bandara customer account. B2B access is enabled only after approval.</p>
    </div>

    @if ($errors->any())
        <div class="{{ $errorClass }}" role="alert">
            <p>Please correct the following information:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($application?->status === \App\Enums\B2BApplicationStatus::MoreInformationRequired && $application->customer_message)
        <div class="{{ $ui['alert_info'] ?? $panel }}">
            <strong>Bandara requested additional information</strong>
            <p class="mt-1 {{ $text }}">{{ $application->customer_message }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('account.business-application.step-one.save') }}" class="space-y-6">
        @csrf

        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">Contact information</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="{{ $labelClass }}">First name *</span>
                    <input name="contact_first_name" value="{{ old('contact_first_name', $application?->contact_first_name ?? $defaults['contact_first_name']) }}" class="{{ $fieldClass }}" required>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Last name</span>
                    <input name="contact_last_name" value="{{ old('contact_last_name', $application?->contact_last_name ?? $defaults['contact_last_name']) }}" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Email *</span>
                    <input type="email" name="email" value="{{ old('email', $application?->email ?? $defaults['email']) }}" class="{{ $fieldClass }}" required>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Mobile number *</span>
                    <input name="phone" value="{{ old('phone', $application?->phone ?? $defaults['phone']) }}" class="{{ $fieldClass }}" required>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">WhatsApp number</span>
                    <input name="whatsapp" value="{{ old('whatsapp', $application?->whatsapp ?? $defaults['whatsapp']) }}" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Preferred contact method *</span>
                    <select name="preferred_contact_method" class="{{ $fieldClass }}" required>
                        @foreach (['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('preferred_contact_method', $application?->preferred_contact_method ?? 'phone') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">Business information</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Legal business name *</span>
                    <input name="legal_business_name" value="{{ old('legal_business_name', $application?->legal_business_name) }}" class="{{ $fieldClass }}" required>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Trading name</span>
                    <input name="trading_name" value="{{ old('trading_name', $application?->trading_name) }}" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Business type *</span>
                    <select name="business_type" class="{{ $fieldClass }}" required>
                        <option value="">Select business type</option>
                        @foreach ((array) config('b2b_application.business_types', []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('business_type', $application?->business_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="md:col-span-2">
                    <input type="hidden" name="gst_registered" value="0">
                    <label class="flex items-start gap-2">
                        <input id="gst_registered" type="checkbox" name="gst_registered" value="1" @checked(old('gst_registered', $application?->gst_registered ?? false)) class="{{ $checkboxClass }}">
                        <span class="{{ $labelClass }}">This business is registered for GST</span>
                    </label>
                </div>

                <label id="gstin_field" class="block">
                    <span class="{{ $labelClass }}">GSTIN</span>
                    <input name="gstin" maxlength="15" value="{{ old('gstin', $application?->gstin) }}" class="{{ $fieldClass }} uppercase">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">PAN</span>
                    <input name="pan" maxlength="10" value="{{ old('pan', $application?->pan) }}" class="{{ $fieldClass }} uppercase">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">FSSAI number</span>
                    <input name="fssai_number" maxlength="14" value="{{ old('fssai_number', $application?->fssai_number) }}" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">Website or social page</span>
                    <input type="url" name="website" value="{{ old('website', $application?->website) }}" placeholder="https://" class="{{ $fieldClass }}">
                </label>
            </div>
        </section>

        <section class="{{ $panel }}">
            <h2 class="{{ $subheading }}">Business address</h2>
            <p class="mt-1 {{ $muted }}">This is used to assess delivery coverage and servicing requirements.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Address line 1 *</span>
                    <input name="address_line_1" value="{{ old('address_line_1', $application?->address_line_1) }}" class="{{ $fieldClass }}" required>
                </label>
                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Address line 2</span>
                    <input name="address_line_2" value="{{ old('address_line_2', $application?->address_line_2) }}" class="{{ $fieldClass }}">
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">State *</span>
                    <select id="state_id" name="state_id" class="{{ $fieldClass }}" required>
                        <option value="">Select state</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected((string) old('state_id', $application?->state_id) === (string) $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">City *</span>
                    <select id="city_id" name="city_id" data-selected="{{ old('city_id', $application?->city_id) }}" class="{{ $fieldClass }}" required>
                        <option value="">Select city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) old('city_id', $application?->city_id) === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="{{ $labelClass }}">PIN code *</span>
                    <input name="postal_code" maxlength="6" inputmode="numeric" value="{{ old('postal_code', $application?->postal_code) }}" class="{{ $fieldClass }}" required>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('business-account.index') }}" class="{{ $secondary }}">Cancel</a>
            <button type="submit" class="{{ $primary }}">Save and continue</button>
        </div>
    </form>
</div>

<script>
(() => {
    const stateSelect = document.getElementById('state_id');
    const citySelect = document.getElementById('city_id');
    const gstCheckbox = document.getElementById('gst_registered');
    const gstinField = document.getElementById('gstin_field');
    const citiesUrl = @json(route('account.business-application.cities'));
    let requestController = null;

    const setGstinVisibility = () => {
        if (gstinField) {
            gstinField.hidden = !gstCheckbox?.checked;
        }
    };

    const resetCities = (label = 'Select city') => {
        if (!citySelect) return;
        citySelect.replaceChildren(new Option(label, ''));
    };

    const loadCities = async () => {
        if (!stateSelect || !citySelect) return;

        const stateId = stateSelect.value;
        const selectedCity = citySelect.dataset.selected || '';
        citySelect.dataset.selected = '';

        if (!stateId) {
            resetCities();
            citySelect.disabled = false;
            return;
        }

        requestController?.abort();
        requestController = new AbortController();
        resetCities('Loading cities…');
        citySelect.disabled = true;

        try {
            const url = new URL(citiesUrl, window.location.origin);
            url.searchParams.set('state_id', stateId);
            const response = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error(`City request failed with status ${response.status}`);
            }

            const payload = await response.json();
            const records = Array.isArray(payload) ? payload : (Array.isArray(payload.data) ? payload.data : []);
            resetCities();

            records.forEach((record) => {
                const option = new Option(String(record.name ?? ''), String(record.id ?? ''));
                option.selected = selectedCity !== '' && String(record.id) === String(selectedCity);
                citySelect.add(option);
            });
        } catch (error) {
            if (error?.name !== 'AbortError') {
                console.error('Bandara business city lookup failed.', error);
                resetCities('Could not load cities');
            }
        } finally {
            citySelect.disabled = false;
        }
    };

    gstCheckbox?.addEventListener('change', setGstinVisibility);
    setGstinVisibility();

    stateSelect?.addEventListener('change', () => {
        if (citySelect) citySelect.dataset.selected = '';
        loadCities();
    });
})();
</script>
@endsection

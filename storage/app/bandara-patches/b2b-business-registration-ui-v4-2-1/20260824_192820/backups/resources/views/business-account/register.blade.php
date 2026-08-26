@extends('layouts.customer')
@section('title', 'Create a Business Account')
@section('content')

@php
    $ui = (array) config('b2b_application_corrective.ui', []);
    $heading = $ui['heading'] ?? 'text-2xl font-medium';
    $subheading = $ui['subheading'] ?? 'text-lg font-medium';
    $text = $ui['text'] ?? 'text-sm';
    $muted = $ui['muted'] ?? 'text-sm opacity-75';
    $labelClass = $ui['label'] ?? 'block text-sm';
    $fieldClass = $ui['field'] ?? 'block w-full';
    $primary = $ui['button_primary'] ?? 'inline-flex items-center justify-center px-4 py-2';
    $linkClass = $ui['link'] ?? '';
    $errorClass = $ui['alert_error'] ?? 'border p-3';
    $selectedCityId = (string) old('city_id', '');
    $gstRegistered = old('gst_registered', '');
@endphp

<div class="space-y-6">
    <div>
        <p class="{{ $muted }}">Bandara for business</p>
        <h1 class="mt-1 {{ $heading }}">Create a Business Account</h1>
        <p class="mt-2 {{ $text }}">
            For restaurants, hotels, cafés, caterers, retailers and other professional buyers. Your login is created now, while B2B pricing and payment terms remain disabled until Bandara approves the application.
        </p>
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

    <form method="POST" action="{{ route('business-account.register.store') }}" class="space-y-7">
        @csrf

        <section class="space-y-4">
            <div>
                <h2 class="{{ $subheading }}">Contact person and login</h2>
                <p class="mt-1 {{ $muted }}">These details are used to sign in and to contact the person managing the business account.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="{{ $labelClass }}">First name</span>
                    <input type="text" name="contact_first_name" value="{{ old('contact_first_name') }}" required maxlength="100" autocomplete="given-name" class="{{ $fieldClass }}">
                    @error('contact_first_name')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Last name</span>
                    <input type="text" name="contact_last_name" value="{{ old('contact_last_name') }}" maxlength="100" autocomplete="family-name" class="{{ $fieldClass }}">
                    @error('contact_last_name')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Email address</span>
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="{{ $fieldClass }}">
                    @error('email')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Mobile number</span>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required maxlength="32" autocomplete="tel" class="{{ $fieldClass }}">
                    @error('phone')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">WhatsApp number <span class="{{ $muted }}">(optional)</span></span>
                    <input type="tel" name="whatsapp" value="{{ old('whatsapp') }}" maxlength="32" autocomplete="tel" class="{{ $fieldClass }}">
                    @error('whatsapp')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Preferred contact method</span>
                    <select name="preferred_contact_method" required class="{{ $fieldClass }}">
                        <option value="phone" @selected(old('preferred_contact_method', 'phone') === 'phone')>Phone</option>
                        <option value="whatsapp" @selected(old('preferred_contact_method') === 'whatsapp')>WhatsApp</option>
                        <option value="email" @selected(old('preferred_contact_method') === 'email')>Email</option>
                    </select>
                    @error('preferred_contact_method')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Password</span>
                    <input type="password" name="password" required autocomplete="new-password" class="{{ $fieldClass }}">
                    @error('password')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Confirm password</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $fieldClass }}">
                </label>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="{{ $subheading }}">Business information</h2>
                <p class="mt-1 {{ $muted }}">Provide the legal and food-business details relevant to your application.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Legal business name</span>
                    <input type="text" name="legal_business_name" value="{{ old('legal_business_name') }}" required maxlength="191" autocomplete="organization" class="{{ $fieldClass }}">
                    @error('legal_business_name')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Trading name <span class="{{ $muted }}">(optional)</span></span>
                    <input type="text" name="trading_name" value="{{ old('trading_name') }}" maxlength="191" class="{{ $fieldClass }}">
                    @error('trading_name')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Business type</span>
                    <select name="business_type" required class="{{ $fieldClass }}">
                        <option value="">Select business type</option>
                        @foreach ((array) config('b2b_application.business_types', []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('business_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('business_type')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">Registered for GST?</span>
                    <select id="gst_registered" name="gst_registered" required class="{{ $fieldClass }}">
                        <option value="">Select</option>
                        <option value="1" @selected((string) $gstRegistered === '1')>Yes</option>
                        <option value="0" @selected((string) $gstRegistered === '0')>No</option>
                    </select>
                    @error('gst_registered')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label id="gstin_field" class="block" @if ((string) $gstRegistered !== '1') hidden @endif>
                    <span class="{{ $labelClass }}">GSTIN</span>
                    <input id="gstin" type="text" name="gstin" value="{{ old('gstin') }}" maxlength="15" autocomplete="off" class="{{ $fieldClass }}">
                    @error('gstin')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">PAN <span class="{{ $muted }}">(optional)</span></span>
                    <input type="text" name="pan" value="{{ old('pan') }}" maxlength="10" autocomplete="off" class="{{ $fieldClass }}">
                    @error('pan')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">FSSAI number <span class="{{ $muted }}">(optional)</span></span>
                    <input type="text" inputmode="numeric" name="fssai_number" value="{{ old('fssai_number') }}" maxlength="14" autocomplete="off" class="{{ $fieldClass }}">
                    @error('fssai_number')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Website or social page <span class="{{ $muted }}">(optional)</span></span>
                    <input type="url" name="website" value="{{ old('website') }}" maxlength="500" placeholder="https://" autocomplete="url" class="{{ $fieldClass }}">
                    @error('website')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="{{ $subheading }}">Business address</h2>
                <p class="mt-1 {{ $muted }}">This helps Bandara review delivery availability and commercial requirements.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Address line 1</span>
                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" required maxlength="255" autocomplete="address-line1" class="{{ $fieldClass }}">
                    @error('address_line_1')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block md:col-span-2">
                    <span class="{{ $labelClass }}">Address line 2 <span class="{{ $muted }}">(optional)</span></span>
                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" maxlength="255" autocomplete="address-line2" class="{{ $fieldClass }}">
                    @error('address_line_2')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">State</span>
                    <select id="business_state_id" name="state_id" required class="{{ $fieldClass }}">
                        <option value="">Select state</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected((string) old('state_id') === (string) $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">City</span>
                    <select id="business_city_id" name="city_id" required data-selected="{{ $selectedCityId }}" class="{{ $fieldClass }}">
                        <option value="">Select city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected($selectedCityId === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>

                <label class="block">
                    <span class="{{ $labelClass }}">PIN code</span>
                    <input type="text" inputmode="numeric" name="postal_code" value="{{ old('postal_code') }}" required maxlength="6" autocomplete="postal-code" class="{{ $fieldClass }}">
                    @error('postal_code')<span class="mt-1 block {{ $muted }}">{{ $message }}</span>@enderror
                </label>
            </div>
        </section>

        <div class="space-y-3">
            <button type="submit" class="{{ $primary }}">Create business login and continue</button>

            <p class="{{ $muted }}">
                Already have a Bandara customer account?
                <a href="{{ route('business-account.login') }}" class="{{ $linkClass }}">Sign in and convert the existing account</a>.
            </p>
            <p class="{{ $muted }}">
                <a href="{{ route('business-account.index') }}" class="{{ $linkClass }}">Back to Business Accounts</a>
            </p>
        </div>
    </form>
</div>

<script>
(() => {
    const gstRegistered = document.getElementById('gst_registered');
    const gstinField = document.getElementById('gstin_field');
    const gstinInput = document.getElementById('gstin');
    const stateSelect = document.getElementById('business_state_id');
    const citySelect = document.getElementById('business_city_id');
    const cityUrl = @json(route('business-account.register.cities'));
    let cityRequest = null;

    const updateGstin = () => {
        const required = gstRegistered?.value === '1';
        if (gstinField) gstinField.hidden = !required;
        if (gstinInput) gstinInput.required = required;
    };

    const resetCities = (label = 'Select city') => {
        if (!citySelect) return;
        citySelect.replaceChildren(new Option(label, ''));
    };

    const loadCities = async () => {
        if (!stateSelect || !citySelect) return;

        const stateId = stateSelect.value;
        const selected = citySelect.dataset.selected || '';
        cityRequest?.abort();
        resetCities(stateId ? 'Loading cities…' : 'Select city');

        if (!stateId) {
            citySelect.disabled = false;
            return;
        }

        citySelect.disabled = true;
        cityRequest = new AbortController();

        try {
            const response = await fetch(`${cityUrl}?state_id=${encodeURIComponent(stateId)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: cityRequest.signal,
            });

            if (!response.ok) throw new Error(`City lookup returned ${response.status}`);
            const cities = await response.json();
            resetCities('Select city');

            for (const city of cities) {
                const option = new Option(city.name, city.id);
                option.selected = String(city.id) === String(selected);
                citySelect.add(option);
            }
        } catch (error) {
            if (error?.name !== 'AbortError') {
                console.error('Bandara business-registration city lookup failed.', error);
                resetCities('Could not load cities');
            }
        } finally {
            citySelect.disabled = false;
        }
    };

    gstRegistered?.addEventListener('change', updateGstin);
    stateSelect?.addEventListener('change', () => {
        if (citySelect) citySelect.dataset.selected = '';
        loadCities();
    });

    updateGstin();
})();
</script>

@endsection

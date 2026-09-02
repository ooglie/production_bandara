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
    $secondary = $ui['button_secondary'] ?? 'inline-flex items-center justify-center px-4 py-2';
    $linkClass = $ui['link'] ?? '';
    $container = $ui['container'] ?? 'mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8';
    $panel = $ui['panel'] ?? 'border p-5';
    $panelCompact = $ui['panel_compact'] ?? $panel;
    $errorClass = $ui['alert_error'] ?? 'border p-3';
    $selectedCityId = (string) old('city_id', '');
    $gstRegistered = old('gst_registered', '');

    $hasErrorsFor = static function (array $fields) use ($errors): bool {
        foreach ($fields as $field) {
            if ($errors->has($field)) {
                return true;
            }
        }

        return false;
    };

    $initialStep = 1;

    if ($hasErrorsFor(['contact_first_name', 'contact_last_name', 'email', 'phone', 'whatsapp', 'preferred_contact_method', 'password', 'password_confirmation'])) {
        $initialStep = 1;
    } elseif ($hasErrorsFor(['legal_business_name', 'trading_name', 'business_type', 'gst_registered', 'gstin', 'pan', 'fssai_number', 'website'])) {
        $initialStep = 2;
    } elseif ($hasErrorsFor(['address_line_1', 'address_line_2', 'state_id', 'city_id', 'postal_code'])) {
        $initialStep = 3;
    }
@endphp

{{-- B2B-REGISTRATION-TABS-V4.3-START --}}
<div class="{{ $container }}">
    <div class="mx-auto w-full max-w-4xl space-y-6">
        <div>
            <p class="{{ $muted }}">Bandara for business</p>
            <h1 class="mt-1 {{ $heading }}">Create a Business Account</h1>
            <p class="mt-2 max-w-3xl {{ $text }}">
                For restaurants, hotels, cafés, caterers, retailers and other professional buyers. Complete the three short steps below. B2B pricing and payment terms remain disabled until Bandara approves the application.
            </p>
            <p class="mt-3 {{ $muted }}">
                Already have a Bandara customer account?
                <a href="{{ route('business-account.login') }}" class="{{ $linkClass }}">Sign in and apply using the existing account</a>.
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

        <form
            id="business-registration-form"
            method="POST"
            action="{{ route('business-account.register.store') }}"
            class="space-y-5"
            data-initial-step="{{ $initialStep }}"
            novalidate
        >
            @csrf

            <div class="{{ $panelCompact }}">
                <div class="flex items-center justify-between gap-3">
                    <p id="business-registration-step-status" class="{{ $muted }}" aria-live="polite">Step {{ $initialStep }} of 3</p>
                    <p class="{{ $muted }}">Business account registration</p>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-1 border-b" role="tablist" aria-label="Business account registration steps">
                    <button
                        type="button"
                        id="business-registration-tab-1"
                        role="tab"
                        aria-controls="business-registration-panel-1"
                        aria-selected="{{ $initialStep === 1 ? 'true' : 'false' }}"
                        data-business-tab="1"
                        class="min-w-0 border-b-2 px-2 py-3 text-left transition-opacity {{ $initialStep === 1 ? 'border-current opacity-100' : 'border-transparent opacity-60' }} disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <span class="block text-xs opacity-70">Step 1</span>
                        <span class="mt-1 block {{ $text }}">Contact &amp; login</span>
                    </button>

                    <button
                        type="button"
                        id="business-registration-tab-2"
                        role="tab"
                        aria-controls="business-registration-panel-2"
                        aria-selected="{{ $initialStep === 2 ? 'true' : 'false' }}"
                        aria-disabled="{{ $initialStep < 2 ? 'true' : 'false' }}"
                        data-business-tab="2"
                        class="min-w-0 border-b-2 px-2 py-3 text-left transition-opacity {{ $initialStep === 2 ? 'border-current opacity-100' : 'border-transparent opacity-60' }} disabled:cursor-not-allowed disabled:opacity-40"
                        @if ($initialStep < 2) disabled @endif
                    >
                        <span class="block text-xs opacity-70">Step 2</span>
                        <span class="mt-1 block {{ $text }}">Business details</span>
                    </button>

                    <button
                        type="button"
                        id="business-registration-tab-3"
                        role="tab"
                        aria-controls="business-registration-panel-3"
                        aria-selected="{{ $initialStep === 3 ? 'true' : 'false' }}"
                        aria-disabled="{{ $initialStep < 3 ? 'true' : 'false' }}"
                        data-business-tab="3"
                        class="min-w-0 border-b-2 px-2 py-3 text-left transition-opacity {{ $initialStep === 3 ? 'border-current opacity-100' : 'border-transparent opacity-60' }} disabled:cursor-not-allowed disabled:opacity-40"
                        @if ($initialStep < 3) disabled @endif
                    >
                        <span class="block text-xs opacity-70">Step 3</span>
                        <span class="mt-1 block {{ $text }}">Business address</span>
                    </button>
                </div>
            </div>

            <section
                id="business-registration-panel-1"
                role="tabpanel"
                aria-labelledby="business-registration-tab-1"
                data-business-panel="1"
                class="{{ $panel }} space-y-5"
                @if ($initialStep !== 1) hidden @endif
            >
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

                <div class="flex justify-end border-t pt-5">
                    <button type="button" class="{{ $primary }}" data-business-next>Continue to business details</button>
                </div>
            </section>

            <section
                id="business-registration-panel-2"
                role="tabpanel"
                aria-labelledby="business-registration-tab-2"
                data-business-panel="2"
                class="{{ $panel }} space-y-5"
                @if ($initialStep !== 2) hidden @endif
            >
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

                <div class="flex items-center justify-between gap-3 border-t pt-5">
                    <button type="button" class="{{ $secondary }}" data-business-back>Back</button>
                    <button type="button" class="{{ $primary }}" data-business-next>Continue to business address</button>
                </div>
            </section>

            <section
                id="business-registration-panel-3"
                role="tabpanel"
                aria-labelledby="business-registration-tab-3"
                data-business-panel="3"
                class="{{ $panel }} space-y-5"
                @if ($initialStep !== 3) hidden @endif
            >
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

                <div class="flex items-center justify-between gap-3 border-t pt-5">
                    <button type="button" class="{{ $secondary }}" data-business-back>Back</button>
                    <button type="submit" class="{{ $primary }}">Create business login and continue</button>
                </div>
            </section>

            <p class="text-center {{ $muted }}">
                <a href="{{ route('business-account.index') }}" class="{{ $linkClass }}">Back to Business Accounts</a>
            </p>
        </form>
    </div>
</div>
{{-- B2B-REGISTRATION-TABS-V4.3-END --}}

<script>
(() => {
    const form = document.getElementById('business-registration-form');
    if (!form) return;

    const tabs = Array.from(form.querySelectorAll('[data-business-tab]'));
    const panels = Array.from(form.querySelectorAll('[data-business-panel]'));
    const status = document.getElementById('business-registration-step-status');
    const totalSteps = panels.length;
    let currentStep = Math.min(Math.max(Number(form.dataset.initialStep || 1), 1), totalSteps);
    let furthestStep = currentStep;

    const controlsForStep = (step) => {
        const panel = panels.find((item) => Number(item.dataset.businessPanel) === step);
        if (!panel) return [];

        return Array.from(panel.querySelectorAll('input, select, textarea')).filter((control) => {
            return !control.disabled && control.type !== 'hidden';
        });
    };

    const firstInvalidControl = (step) => {
        return controlsForStep(step).find((control) => !control.checkValidity()) || null;
    };

    const updateTabState = () => {
        for (const tab of tabs) {
            const step = Number(tab.dataset.businessTab);
            const active = step === currentStep;
            const enabled = step <= furthestStep;

            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('aria-disabled', enabled ? 'false' : 'true');
            tab.disabled = !enabled;
            tab.tabIndex = active ? 0 : -1;
            tab.classList.toggle('border-current', active);
            tab.classList.toggle('border-transparent', !active);
            tab.classList.toggle('opacity-100', active);
            tab.classList.toggle('opacity-60', !active);
        }
    };

    const showStep = (step, focusTab = false) => {
        currentStep = Math.min(Math.max(step, 1), totalSteps);

        for (const panel of panels) {
            panel.hidden = Number(panel.dataset.businessPanel) !== currentStep;
        }

        updateTabState();

        if (status) {
            status.textContent = `Step ${currentStep} of ${totalSteps}`;
        }

        if (focusTab) {
            tabs.find((tab) => Number(tab.dataset.businessTab) === currentStep)?.focus();
        }
    };

    const validateStep = (step) => {
        const invalid = firstInvalidControl(step);
        if (!invalid) return true;

        showStep(step);
        invalid.reportValidity();
        invalid.focus({ preventScroll: true });
        return false;
    };

    for (const tab of tabs) {
        tab.addEventListener('click', () => {
            const targetStep = Number(tab.dataset.businessTab);
            if (targetStep <= furthestStep) {
                showStep(targetStep);
            }
        });

        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;

            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            let targetStep = currentStep + direction;

            while (targetStep >= 1 && targetStep <= totalSteps) {
                if (targetStep <= furthestStep) {
                    showStep(targetStep, true);
                    break;
                }
                targetStep += direction;
            }
        });
    }

    for (const nextButton of form.querySelectorAll('[data-business-next]')) {
        nextButton.addEventListener('click', () => {
            if (!validateStep(currentStep)) return;

            furthestStep = Math.max(furthestStep, Math.min(currentStep + 1, totalSteps));
            showStep(currentStep + 1, true);
        });
    }

    for (const backButton of form.querySelectorAll('[data-business-back]')) {
        backButton.addEventListener('click', () => showStep(currentStep - 1, true));
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        for (let step = 1; step <= totalSteps; step += 1) {
            const invalid = firstInvalidControl(step);
            if (invalid) {
                furthestStep = Math.max(furthestStep, step);
                showStep(step);
                invalid.reportValidity();
                invalid.focus({ preventScroll: true });
                return;
            }
        }

        HTMLFormElement.prototype.submit.call(form);
    });

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
    showStep(currentStep);
})();
</script>
@endsection

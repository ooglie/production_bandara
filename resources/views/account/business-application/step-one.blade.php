<x-layouts.business-account title="Business details" heading="Apply for a Business Account">
    <div class="mx-auto max-w-4xl">
        <div class="mb-7 grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white text-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b-2 border-sky-600 px-4 py-3 font-medium text-slate-950 dark:text-white">1. Business details</div>
            <div class="px-4 py-3 text-slate-500 dark:text-slate-400">2. Purchase requirements</div>
        </div>

        @if ($application?->status === \App\Enums\B2BApplicationStatus::MoreInformationRequired && $application->customer_message)
            <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-100">
                <p class="font-medium">Bandara requested additional information</p>
                <p class="mt-1 leading-6">{{ $application->customer_message }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('account.business-application.step-one.save') }}" class="space-y-6">
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-medium text-slate-950 dark:text-white">Contact information</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Who should our business team contact about this application?</p>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    @php
                        $fieldClass = 'mt-1 block w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
                        $labelClass = 'text-sm font-medium text-slate-700 dark:text-slate-200';
                    @endphp
                    <label class="block"><span class="{{ $labelClass }}">First name *</span><input name="contact_first_name" value="{{ old('contact_first_name', $application?->contact_first_name ?? $defaults['contact_first_name']) }}" class="{{ $fieldClass }}" required>@error('contact_first_name')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">Last name</span><input name="contact_last_name" value="{{ old('contact_last_name', $application?->contact_last_name ?? $defaults['contact_last_name']) }}" class="{{ $fieldClass }}">@error('contact_last_name')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">Email *</span><input type="email" name="email" value="{{ old('email', $application?->email ?? $defaults['email']) }}" class="{{ $fieldClass }}" required>@error('email')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">Mobile number *</span><input name="phone" value="{{ old('phone', $application?->phone ?? $defaults['phone']) }}" class="{{ $fieldClass }}" required>@error('phone')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">WhatsApp number</span><input name="whatsapp" value="{{ old('whatsapp', $application?->whatsapp ?? $defaults['whatsapp']) }}" class="{{ $fieldClass }}">@error('whatsapp')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">Preferred contact method *</span><select name="preferred_contact_method" class="{{ $fieldClass }}" required>@foreach (['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $value => $label)<option value="{{ $value }}" @selected(old('preferred_contact_method', $application?->preferred_contact_method ?? 'phone') === $value)>{{ $label }}</option>@endforeach</select></label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-medium text-slate-950 dark:text-white">Business information</h2>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <label class="block sm:col-span-2"><span class="{{ $labelClass }}">Legal business name *</span><input name="legal_business_name" value="{{ old('legal_business_name', $application?->legal_business_name) }}" class="{{ $fieldClass }}" required>@error('legal_business_name')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">Trading name</span><input name="trading_name" value="{{ old('trading_name', $application?->trading_name) }}" class="{{ $fieldClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">Business type *</span><select name="business_type" class="{{ $fieldClass }}" required><option value="">Select business type</option>@foreach (config('b2b_application.business_types') as $value => $label)<option value="{{ $value }}" @selected(old('business_type', $application?->business_type) === $value)>{{ $label }}</option>@endforeach</select>@error('business_type')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>

                    <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/60">
                        <input type="hidden" name="gst_registered" value="0">
                        <label class="flex items-start gap-3"><input id="gst_registered" type="checkbox" name="gst_registered" value="1" @checked(old('gst_registered', $application?->gst_registered ?? false)) class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500"><span><span class="block text-sm font-medium text-slate-800 dark:text-slate-100">This business is registered for GST</span><span class="mt-1 block text-xs text-slate-500">GSTIN becomes mandatory when selected.</span></span></label>
                    </div>

                    <label id="gstin_field" class="block"><span class="{{ $labelClass }}">GSTIN</span><input name="gstin" maxlength="15" value="{{ old('gstin', $application?->gstin) }}" class="{{ $fieldClass }} uppercase">@error('gstin')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">PAN</span><input name="pan" maxlength="10" value="{{ old('pan', $application?->pan) }}" class="{{ $fieldClass }} uppercase">@error('pan')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">FSSAI number</span><input name="fssai_number" maxlength="14" value="{{ old('fssai_number', $application?->fssai_number) }}" class="{{ $fieldClass }}">@error('fssai_number')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">Website or social page</span><input type="url" name="website" value="{{ old('website', $application?->website) }}" placeholder="https://" class="{{ $fieldClass }}">@error('website')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-medium text-slate-950 dark:text-white">Business address</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This helps us assess delivery coverage and business servicing requirements.</p>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <label class="block sm:col-span-2"><span class="{{ $labelClass }}">Address line 1 *</span><input name="address_line_1" value="{{ old('address_line_1', $application?->address_line_1) }}" class="{{ $fieldClass }}" required>@error('address_line_1')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block sm:col-span-2"><span class="{{ $labelClass }}">Address line 2</span><input name="address_line_2" value="{{ old('address_line_2', $application?->address_line_2) }}" class="{{ $fieldClass }}"></label>
                    <label class="block"><span class="{{ $labelClass }}">State *</span><select id="state_id" name="state_id" class="{{ $fieldClass }}" required><option value="">Select state</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) old('state_id', $application?->state_id) === (string) $state->id)>{{ $state->name }}</option>@endforeach</select>@error('state_id')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">City *</span><select id="city_id" name="city_id" data-selected="{{ old('city_id', $application?->city_id) }}" class="{{ $fieldClass }}" required><option value="">Select city</option>@foreach ($cities as $city)<option value="{{ $city->id }}" @selected((string) old('city_id', $application?->city_id) === (string) $city->id)>{{ $city->name }}</option>@endforeach</select>@error('city_id')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="{{ $labelClass }}">PIN code *</span><input name="postal_code" maxlength="6" inputmode="numeric" value="{{ old('postal_code', $application?->postal_code) }}" class="{{ $fieldClass }}" required>@error('postal_code')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('business-account.index') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancel</a>
                <button type="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">Save and continue</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const state = document.getElementById('state_id');
            const city = document.getElementById('city_id');
            const gst = document.getElementById('gst_registered');
            const gstField = document.getElementById('gstin_field');
            const citiesUrl = @json(route('account.business-application.cities'));

            const toggleGst = () => gstField?.classList.toggle('opacity-60', !gst?.checked);
            gst?.addEventListener('change', toggleGst);
            toggleGst();

            state?.addEventListener('change', async () => {
                city.innerHTML = '<option value="">Loading cities…</option>';
                city.disabled = true;
                try {
                    const response = await fetch(`${citiesUrl}?state_id=${encodeURIComponent(state.value)}`, {headers: {'Accept': 'application/json'}});
                    if (!response.ok) throw new Error('Unable to load cities');
                    const records = await response.json();
                    city.innerHTML = '<option value="">Select city</option>';
                    records.forEach((record) => {
                        const option = document.createElement('option');
                        option.value = record.id;
                        option.textContent = record.name;
                        city.appendChild(option);
                    });
                } catch (error) {
                    city.innerHTML = '<option value="">Could not load cities</option>';
                } finally {
                    city.disabled = false;
                }
            });
        })();
    </script>
</x-layouts.business-account>

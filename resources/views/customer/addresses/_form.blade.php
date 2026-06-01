@php
    /** @var \App\Models\CustomerAddress|null $address */
    $isEdit = $address && $address->exists;

    // Selected values (supports old() after validation errors)
    $stateCode = strtoupper(trim((string) old('state_code', $selectedStateCode ?? ($address->state_code ?? ''))));
    $city      = (string) old('city', $address->city ?? '');

    $states = $states ?? collect();
    $cities = $cities ?? collect();
@endphp

<div class="border border-gray-200 dark:border-gray-800 rounded-sm-lg bg-white dark:bg-gray-900 px-4 py-4 space-y-4 text-xs">
    <div class="space-y-1">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">
            {{ $isEdit ? 'Edit address' : 'New address' }}
        </h2>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">
            This address can be used as shipping or billing during checkout.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Full name
            </label>
            <input
                type="text"
                name="full_name"
                value="{{ old('full_name', $address->full_name ?? auth()->user()->name) }}"
                required
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            @error('full_name')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Mobile number
            </label>
            <input
                type="text"
                name="phone"
                value="{{ old('phone', $address->phone ?? auth()->user()->phone) }}"
                required
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            @error('phone')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Address line 1
        </label>
        <input
            type="text"
            name="address_line1"
            value="{{ old('address_line1', $address->address_line1 ?? '') }}"
            required
            class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        @error('address_line1')
            <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Address line 2 (optional)
        </label>
        <input
            type="text"
            name="address_line2"
            value="{{ old('address_line2', $address->address_line2 ?? '') }}"
            class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        @error('address_line2')
            <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                State
            </label>

            @if($states->isEmpty())
                <div class="mt-1 rounded-sm border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px] text-gray-500 dark:text-gray-400">
                    No states found in database (states table is empty).
                </div>
            @else
                <select
                    name="state_code"
                    id="state_code"
                    required
                    class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <option value="" disabled @selected($stateCode === '')>Select state</option>
                    @foreach($states as $s)
                        <option value="{{ $s->code }}" @selected($stateCode === (string)$s->code)>
                            {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            @endif

            @error('state_code')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror

            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                Used to determine GST (Maharashtra vs other states).
            </p>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                City
            </label>

            <select
                name="city"
                id="city"
                required
                @disabled($stateCode === '')
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500 disabled:opacity-60"
            >
                <option value="" disabled @selected($city === '')>
                    {{ $stateCode === '' ? 'Select a state first' : 'Select city' }}
                </option>

                {{-- If editing and the city isn't in current list, keep it selectable --}}
                @php $cityOptions = collect($cities); @endphp
                @if($city !== '' && !$cityOptions->contains($city))
                    <option value="{{ $city }}" selected>{{ $city }}</option>
                @endif

                @foreach($cityOptions as $c)
                    <option value="{{ $c }}" @selected($city === $c)>{{ $c }}</option>
                @endforeach
            </select>

            @error('city')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror

            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                Cities are filtered by state (India only).
            </p>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                PIN code
            </label>
            <input
                type="text"
                name="pincode"
                value="{{ old('pincode', $address->pincode ?? '') }}"
                required
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            @error('pincode')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Country
            </label>

            {{-- India only --}}
            <input
                type="text"
                value="India"
                disabled
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-2 py-1.5 text-xs opacity-90"
            >
            <input type="hidden" name="country" value="India">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                GSTIN (optional)
            </label>
            <input
                type="text"
                name="gstin"
                value="{{ old('gstin', $address->gstin ?? '') }}"
                class="mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            @error('gstin')
                <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
            <input
                type="checkbox"
                name="is_default_shipping"
                value="1"
                @checked(old('is_default_shipping', $address->is_default_shipping ?? false))
            >
            <span>Default shipping address</span>
        </label>

        <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
            <input
                type="checkbox"
                name="is_default_billing"
                value="1"
                @checked(old('is_default_billing', $address->is_default_billing ?? false))
            >
            <span>Default billing address</span>
        </label>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
        >
            {{ $isEdit ? 'Save changes' : 'Save address' }}
        </button>

        <a href="{{ route('account.addresses.index') }}"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
            Cancel
        </a>
    </div>
</div>

<script>
(function () {
    const stateEl = document.getElementById('state_code');
    const cityEl  = document.getElementById('city');

    if (!stateEl || !cityEl) return;

    const citiesUrl = @json(\Illuminate\Support\Facades\Route::has('account.addresses.cities')
        ? route('account.addresses.cities')
        : null
    );

    if (!citiesUrl) return;

    function resetCity(placeholderText) {
        cityEl.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholderText || 'Select city';
        opt.disabled = true;
        opt.selected = true;
        cityEl.appendChild(opt);
    }

    async function loadCities(stateCode) {
        resetCity('Loading…');
        cityEl.disabled = true;

        try {
            const res = await fetch(citiesUrl + '?state_code=' + encodeURIComponent(stateCode), {
                headers: { 'Accept': 'application/json' }
            });

            const json = await res.json();
            const list = (json && json.ok && Array.isArray(json.cities)) ? json.cities : [];

            cityEl.innerHTML = '';
            const first = document.createElement('option');
            first.value = '';
            first.textContent = 'Select city';
            first.disabled = true;
            first.selected = true;
            cityEl.appendChild(first);

            list.forEach((name) => {
                const o = document.createElement('option');
                o.value = name;
                o.textContent = name;
                cityEl.appendChild(o);
            });

            cityEl.disabled = false;
        } catch (e) {
            resetCity('Select city');
            cityEl.disabled = false;
        }
    }

    stateEl.addEventListener('change', function () {
        const code = (stateEl.value || '').trim();
        if (!code) {
            resetCity('Select a state first');
            cityEl.disabled = true;
            return;
        }
        loadCities(code);
    });

    // If state is already selected but city list is empty (rare), load once.
    if ((stateEl.value || '').trim() && cityEl.options.length <= 1) {
        loadCities((stateEl.value || '').trim());
    }
})();
</script>

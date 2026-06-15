@extends('layouts.company')

@section('title', 'Delivery & Handling Charges')

@section('content')
@php
    $customerTypes = ['b2c' => 'B2C', 'b2b' => 'B2B', 'guest' => 'Guest', 'all' => 'All'];
    // Dynamic distance delivery rules are intentionally non-B2B.
    // B2B checkout keeps delivery fee waived in DeliveryChargeService.
    $distanceCustomerTypes = ['b2c' => 'B2C', 'guest' => 'Guest', 'all' => 'All non-B2B'];
    $temperatureModes = ['all' => 'All', 'frozen' => 'Frozen', 'chilled' => 'Chilled', 'ambient' => 'Ambient'];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">Delivery & handling charges</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configure Pune delivery zones, distance-based fees, pincode fallbacks, and cold-chain handling / packing charges.
            </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            Store pincode: <span class="font-semibold text-gray-900 dark:text-gray-50">{{ config('delivery.store_pincode', '411001') }}</span><br>
            <span class="text-[10px] text-gray-500 dark:text-gray-400">Distance mode: {{ config('delivery.distance_enabled') ? 'Enabled' : 'Disabled' }}</span><br>
            <span class="text-[10px] text-gray-500 dark:text-gray-400">Provider: {{ strtoupper((string) config('delivery.distance_provider', 'google')) }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
            <div class="font-semibold">Please fix the following:</div>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Create delivery zone</h2>
        <form method="POST" action="{{ route('admin.delivery.zones.store') }}" class="mt-4 grid gap-3 md:grid-cols-5">
            @csrf
            <input name="name" placeholder="Zone name" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950" required>
            <input name="code" placeholder="Code, e.g. PUNE_CITY" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950" required>
            <input name="sort_order" type="number" min="0" placeholder="Sort" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
            <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 dark:border-gray-700"> Active
            </label>
            <button class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Create zone</button>
            <textarea name="description" placeholder="Description" class="md:col-span-5 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950"></textarea>
        </form>
    </section>

    <div class="space-y-5">
        @foreach($zones as $zone)
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <form method="POST" action="{{ route('admin.delivery.zones.update', $zone) }}" class="grid gap-3 md:grid-cols-6">
                        @csrf
                        @method('PUT')
                        <div class="md:col-span-2">
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Zone name</label>
                            <input name="name" value="{{ old('name', $zone->name) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Code</label>
                            <input name="code" value="{{ old('code', $zone->code) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Sort</label>
                            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $zone->sort_order) }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                        </div>
                        <label class="mt-6 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                            <input type="checkbox" name="is_active" value="1" @checked($zone->is_active) class="rounded border-gray-300 dark:border-gray-700"> Active
                        </label>
                        <button class="mt-5 rounded-xl border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Save zone</button>
                        <textarea name="description" class="md:col-span-6 rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">{{ old('description', $zone->description) }}</textarea>
                    </form>
                </div>

                <div class="grid gap-0 lg:grid-cols-2">
                    <div class="border-b border-gray-100 p-5 dark:border-gray-800 lg:border-b-0 lg:border-r">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pincodes</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse($zone->pincodes as $pin)
                                <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1 text-xs dark:border-gray-700">
                                    {{ $pin->pincode }}
                                    @if($pin->area_name)<span class="text-gray-400">{{ $pin->area_name }}</span>@endif
                                    <form method="POST" action="{{ route('admin.delivery.pincodes.destroy', $pin) }}" onsubmit="return confirm('Remove this pincode?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-500">×</button>
                                    </form>
                                </span>
                            @empty
                                <p class="text-xs text-gray-500 dark:text-gray-400">No pincodes mapped yet.</p>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('admin.delivery.zones.pincodes.store', $zone) }}" class="mt-4 grid gap-2 sm:grid-cols-4">
                            @csrf
                            <input name="pincode" placeholder="Pincode" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950" required>
                            <input name="city" placeholder="City" value="Pune" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            <input name="area_name" placeholder="Area" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            <button class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Add pincode</button>
                            <input type="hidden" name="is_active" value="1">
                        </form>
                    </div>

                    <div class="p-5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Delivery fee rules</h3>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-xs dark:divide-gray-800">
                                <thead><tr class="text-left text-gray-500"><th class="py-2">Customer</th><th>Min order</th><th>Fee</th><th>Free above</th><th>GST %</th><th>Status</th><th></th></tr></thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($zone->deliveryChargeRules as $rule)
                                        <tr>
                                            <form method="POST" action="{{ route('admin.delivery.delivery-rules.update', $rule) }}">
                                                @csrf
                                                @method('PUT')
                                                <td class="py-2"><select name="customer_type" class="rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950">@foreach($customerTypes as $key => $label)<option value="{{ $key }}" @selected($rule->customer_type === $key)>{{ $label }}</option>@endforeach</select></td>
                                                <td><input name="min_order_value" type="number" step="0.01" min="0" value="{{ $rule->min_order_value }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                                                <td><input name="delivery_fee" type="number" step="0.01" min="0" value="{{ $rule->delivery_fee }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                                                <td><input name="free_delivery_above" type="number" step="0.01" min="0" value="{{ $rule->free_delivery_above }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                                                <td><input name="tax_rate" type="number" step="0.01" min="0" value="{{ $rule->tax_rate }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                                                <td><label class="inline-flex items-center gap-1"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> Active</label></td>
                                                <td class="text-right"><button class="rounded-lg border px-2 py-1">Save</button></td>
                                            </form>
                                            <td><form method="POST" action="{{ route('admin.delivery.delivery-rules.destroy', $rule) }}" onsubmit="return confirm('Remove this rule?')">@csrf @method('DELETE')<button class="text-red-500">Delete</button></form></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <form method="POST" action="{{ route('admin.delivery.zones.delivery-rules.store', $zone) }}" class="mt-4 grid gap-2 sm:grid-cols-6">
                            @csrf
                            <select name="customer_type" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">@foreach($customerTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                            <input name="min_order_value" type="number" step="0.01" min="0" placeholder="Min order" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            <input name="delivery_fee" type="number" step="0.01" min="0" placeholder="Fee" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            <input name="free_delivery_above" type="number" step="0.01" min="0" placeholder="Free above" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            <input name="tax_rate" type="number" step="0.01" min="0" placeholder="GST %" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                            <button class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Add rule</button>
                            <input type="hidden" name="is_active" value="1">
                        </form>
                    </div>
                </div>
            </section>
        @endforeach
    </div>


    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Distance-based delivery rules</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    B2C/guest road-distance fees from the store origin to the customer address. B2B delivery fee is always waived. Use fixed slabs, or one dynamic rule: base fee + fixed fee for each started km after the base distance.
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 px-3 py-2 text-[11px] text-gray-600 dark:border-gray-800 dark:text-gray-300">
                <div>Provider: <span class="font-medium text-gray-900 dark:text-gray-50">{{ strtoupper((string) config('delivery.distance_provider', 'google')) }}</span></div>
                <div>Google API key: <span class="font-medium text-gray-900 dark:text-gray-50">{{ config('delivery.google_maps_api_key') ? 'Configured' : 'Not configured' }}</span></div>
                <div>Origin: <span class="font-medium text-gray-900 dark:text-gray-50">{{ config('delivery.store_origin_lat') && config('delivery.store_origin_lng') ? config('delivery.store_origin_lat').', '.config('delivery.store_origin_lng') : (config('delivery.store_origin_address') ?: 'Not configured') }}</span></div>
                <div>Fallback to zone: <span class="font-medium text-gray-900 dark:text-gray-50">{{ config('delivery.distance_fallback_to_zone') ? 'Yes' : 'No' }}</span></div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-blue-100 bg-blue-50/80 p-4 text-xs text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-100">
            <div class="font-semibold">Dynamic fee example</div>
            <div class="mt-1">Set <strong>Customer</strong> to B2C, <strong>Base fee</strong> to ₹49, <strong>Base covers</strong> to 3 km, and <strong>Fee per started km after</strong> to ₹12. A delivery distance of 8.2 km becomes ₹49 + 6 × ₹12 = ₹121.</div>
            <div class="mt-1 text-[11px] opacity-80">Leave <strong>Fee per started km after</strong> blank/0 to use the row as a fixed slab. B2B checkout ignores these rules and keeps delivery fee at ₹0.</div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-xs dark:divide-gray-800">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2">Customer</th>
                        <th>Distance km</th>
                        <th>Min order</th>
                        <th>Base fee</th>
                        <th>Base covers</th>
                        <th>Per km after</th>
                        <th>Free above</th>
                        <th>GST %</th>
                        <th>Status</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($distanceRules as $rule)
                        @php($distanceRuleFormId = 'distance-rule-form-' . $rule->id)
                        <tr>
                            <td class="py-2">
                                <select form="{{ $distanceRuleFormId }}" name="customer_type" class="rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950">@foreach($distanceCustomerTypes as $key => $label)<option value="{{ $key }}" @selected($rule->customer_type === $key)>{{ $label }}</option>@endforeach @if($rule->customer_type === 'b2b')<option value="b2b" selected>B2B (ignored / waived)</option>@endif</select>
                            </td>
                            <td class="whitespace-nowrap">
                                <input form="{{ $distanceRuleFormId }}" name="min_distance_km" type="number" step="0.01" min="0" value="{{ $rule->min_distance_km }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
                                <span class="text-gray-400">to</span>
                                <input form="{{ $distanceRuleFormId }}" name="max_distance_km" type="number" step="0.01" min="0" value="{{ $rule->max_distance_km }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950" placeholder="∞">
                            </td>
                            <td><input form="{{ $distanceRuleFormId }}" name="min_order_value" type="number" step="0.01" min="0" value="{{ $rule->min_order_value }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><input form="{{ $distanceRuleFormId }}" name="delivery_fee" type="number" step="0.01" min="0" value="{{ $rule->delivery_fee }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><input form="{{ $distanceRuleFormId }}" name="included_distance_km" type="number" step="0.01" min="0" value="{{ $rule->included_distance_km ?? 0 }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950" placeholder="0"></td>
                            <td><input form="{{ $distanceRuleFormId }}" name="per_km_fee" type="number" step="0.01" min="0" value="{{ $rule->per_km_fee }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950" placeholder="0"></td>
                            <td><input form="{{ $distanceRuleFormId }}" name="free_delivery_above" type="number" step="0.01" min="0" value="{{ $rule->free_delivery_above }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><input form="{{ $distanceRuleFormId }}" name="tax_rate" type="number" step="0.01" min="0" value="{{ $rule->tax_rate }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><label class="inline-flex items-center gap-1"><input form="{{ $distanceRuleFormId }}" type="checkbox" name="is_active" value="1" @checked($rule->is_active)> Active</label></td>
                            <td>
                                <button type="submit" form="{{ $distanceRuleFormId }}" class="rounded-lg border px-2 py-1">Save</button>
                                <form id="{{ $distanceRuleFormId }}" method="POST" action="{{ route('admin.delivery.distance-rules.update', $rule) }}" class="hidden">
                                    @csrf
                                    @method('PUT')
                                </form>
                            </td>
                            <td><form method="POST" action="{{ route('admin.delivery.distance-rules.destroy', $rule) }}" onsubmit="return confirm('Remove this distance rule?')">@csrf @method('DELETE')<button class="text-red-500">Delete</button></form></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.delivery.distance-rules.store') }}" class="mt-4 rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/40">
            @csrf
            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Customer</span>
                    <select name="customer_type" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">@foreach($distanceCustomerTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Min distance km</span>
                    <input name="min_distance_km" type="number" step="0.01" min="0" value="0" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Max distance km</span>
                    <input name="max_distance_km" type="number" step="0.01" min="0" placeholder="Blank = no max" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Min order value</span>
                    <input name="min_order_value" type="number" step="0.01" min="0" value="0" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Base fee ₹</span>
                    <input name="delivery_fee" type="number" step="0.01" min="0" placeholder="49" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Base covers km</span>
                    <input name="included_distance_km" type="number" step="0.01" min="0" placeholder="3" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Fee per started km after ₹</span>
                    <input name="per_km_fee" type="number" step="0.01" min="0" placeholder="12" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>Free delivery above ₹</span>
                    <input name="free_delivery_above" type="number" step="0.01" min="0" placeholder="1999" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <label class="space-y-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    <span>GST %</span>
                    <input name="tax_rate" type="number" step="0.01" min="0" placeholder="0" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                </label>
                <div class="flex items-end">
                    <button class="w-full rounded-xl bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Add distance rule</button>
                </div>
            </div>
            <input type="hidden" name="is_active" value="1">
            <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">Dynamic formula: base fee + fee per started km after base coverage. Example: ₹49 base covers 3 km, ₹12/km after, 8.2 km distance → ₹49 + 6×₹12 = ₹121.</p>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Cold-chain handling & packing rules</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Use this for small-order frozen packing, gel packs, insulated handling, and special packing fees. The storefront defaults to the
                    <span class="font-semibold">{{ ucfirst(config('delivery.default_handling_temperature_mode', 'frozen')) }}</span>
                    temperature bucket when calculating handling fees.
                </p>
            </div>
        </div>

        @foreach($handlingRules as $rule)
            <form id="handling-rule-update-{{ $rule->id }}" method="POST" action="{{ route('admin.delivery.handling-rules.update', $rule) }}" class="hidden">
                @csrf
                @method('PUT')
            </form>
        @endforeach

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-xs dark:divide-gray-800">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2">Customer</th>
                        <th>Temp</th>
                        <th>Min order</th>
                        <th>Handling fee</th>
                        <th>Free above</th>
                        <th>GST %</th>
                        <th>Status</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($handlingRules as $rule)
                        <tr>
                            <td class="py-2">
                                <select name="customer_type" form="handling-rule-update-{{ $rule->id }}" class="rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
                                    @foreach($customerTypes as $key => $label)
                                        <option value="{{ $key }}" @selected($rule->customer_type === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="temperature_mode" form="handling-rule-update-{{ $rule->id }}" class="rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950">
                                    @foreach($temperatureModes as $key => $label)
                                        <option value="{{ $key }}" @selected($rule->temperature_mode === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input name="min_order_value" form="handling-rule-update-{{ $rule->id }}" type="number" step="0.01" min="0" value="{{ $rule->min_order_value }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><input name="handling_fee" form="handling-rule-update-{{ $rule->id }}" type="number" step="0.01" min="0" value="{{ $rule->handling_fee }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><input name="free_handling_above" form="handling-rule-update-{{ $rule->id }}" type="number" step="0.01" min="0" value="{{ $rule->free_handling_above }}" class="w-24 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><input name="tax_rate" form="handling-rule-update-{{ $rule->id }}" type="number" step="0.01" min="0" value="{{ $rule->tax_rate }}" class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-700 dark:bg-gray-950"></td>
                            <td><label class="inline-flex items-center gap-1"><input type="checkbox" name="is_active" form="handling-rule-update-{{ $rule->id }}" value="1" @checked($rule->is_active)> Active</label></td>
                            <td><button type="submit" form="handling-rule-update-{{ $rule->id }}" class="rounded-lg border px-2 py-1">Save</button></td>
                            <td>
                                <form method="POST" action="{{ route('admin.delivery.handling-rules.destroy', $rule) }}" onsubmit="return confirm('Remove this rule?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-4 text-center text-gray-500 dark:text-gray-400">No handling rules yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.delivery.handling-rules.store') }}" class="mt-4 grid gap-2 md:grid-cols-8">
            @csrf
            <select name="customer_type" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                @foreach($customerTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
            <select name="temperature_mode" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
                @foreach($temperatureModes as $key => $label)
                    <option value="{{ $key }}" @selected($key === config('delivery.default_handling_temperature_mode', 'frozen'))>{{ $label }}</option>
                @endforeach
            </select>
            <input name="min_order_value" type="number" step="0.01" min="0" placeholder="Min order" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
            <input name="handling_fee" type="number" step="0.01" min="0" placeholder="Handling fee" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
            <input name="free_handling_above" type="number" step="0.01" min="0" placeholder="Free above" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
            <input name="tax_rate" type="number" step="0.01" min="0" placeholder="GST %" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-950">
            <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3 py-2 text-xs dark:border-gray-800">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <button class="rounded-xl bg-gray-900 px-4 py-2 text-xs font-medium text-white dark:bg-gray-100 dark:text-gray-900">Add handling rule</button>
        </form>
    </section>
</div>
@endsection

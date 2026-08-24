<x-layouts.business-account title="Purchase requirements" heading="Apply for a Business Account">
    <div class="mx-auto max-w-4xl">
        <div class="mb-7 grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white text-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="px-4 py-3 text-slate-500 dark:text-slate-400">1. Business details</div>
            <div class="border-b-2 border-sky-600 px-4 py-3 font-medium text-slate-950 dark:text-white">2. Purchase requirements</div>
        </div>

        @if ($application->status === \App\Enums\B2BApplicationStatus::MoreInformationRequired && $application->customer_message)
            <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-100">
                <p class="font-medium">Bandara requested additional information</p>
                <p class="mt-1 leading-6">{{ $application->customer_message }}</p>
            </div>
        @endif

        @php
            $fieldClass = 'mt-1 block w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white';
            $selectedCategories = old('interested_categories', $application->interested_categories ?? []);
        @endphp

        <form method="POST" action="{{ route('account.business-application.step-two.save') }}" class="space-y-6">
            @csrf
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-medium text-slate-950 dark:text-white">What are you interested in purchasing?</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Select all relevant categories.</p>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (config('b2b_application.product_categories') as $value => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600">
                            <input type="checkbox" name="interested_categories[]" value="{{ $value }}" @checked(in_array($value, $selectedCategories, true)) class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-slate-800 dark:text-slate-100">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('interested_categories')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror

                <div class="mt-7 grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Expected monthly purchase</span><select name="estimated_monthly_purchase" class="{{ $fieldClass }}"><option value="">Select a range</option>@foreach (config('b2b_application.monthly_purchase_ranges') as $value => $label)<option value="{{ $value }}" @selected(old('estimated_monthly_purchase', $application->estimated_monthly_purchase) === $value)>{{ $label }}</option>@endforeach</select>@error('estimated_monthly_purchase')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Purchase frequency</span><select name="purchase_frequency" class="{{ $fieldClass }}"><option value="">Select frequency</option>@foreach (config('b2b_application.purchase_frequencies') as $value => $label)<option value="{{ $value }}" @selected(old('purchase_frequency', $application->purchase_frequency) === $value)>{{ $label }}</option>@endforeach</select>@error('purchase_frequency')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                    <label class="block sm:col-span-2"><span class="text-sm font-medium text-slate-700 dark:text-slate-200">Tell us what products, pack sizes or quantities you require</span><textarea name="requirements_message" rows="6" class="{{ $fieldClass }}" placeholder="For example: salmon fillet, pork belly slabs and 20-piece dimsum packs every week.">{{ old('requirements_message', $application->requirements_message) }}</textarea>@error('requirements_message')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror</label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-slate-800 dark:bg-slate-900">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="terms_accepted" value="1" @checked(old('terms_accepted', (bool) $application->terms_accepted_at)) class="mt-1 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <span class="text-sm leading-6 text-slate-600 dark:text-slate-300">I confirm that the information provided is accurate and understand that submitting an application does not automatically provide B2B prices, payment terms or credit facilities. Bandara may contact me for verification.</span>
                </label>
                @error('terms_accepted')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            </section>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('account.business-application.step-one') }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200">Back to business details</a>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="intent" value="save" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Save without submitting</button>
                    <button type="submit" name="intent" value="submit" class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">Submit for review</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.business-account>

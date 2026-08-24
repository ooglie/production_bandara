<x-layouts.business-account title="Business account" heading="Business Account">
    <div class="mx-auto max-w-5xl space-y-6">
        @if (! $application && $isB2B)
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/40">
                <h2 class="text-xl font-medium text-emerald-950 dark:text-emerald-100">Your account is already enabled for B2B purchasing</h2>
                <p class="mt-2 text-sm leading-6 text-emerald-800 dark:text-emerald-200">This account predates the business-application workflow. Your existing B2B access remains unchanged.</p>
            </section>
        @elseif ($application)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ $application->application_number }}</p>
                        <h2 class="mt-2 text-2xl font-light text-slate-950 dark:text-white">{{ $application->legal_business_name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Submitted {{ $application->submitted_at?->format('d M Y, g:i a') ?? 'not yet submitted' }}</p>
                    </div>
                    <x-b2b.status-badge :status="$application->status" />
                </div>

                @if ($application->customer_message)
                    <div class="mt-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100">
                        {{ $application->customer_message }}
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($application->status->customerCanEdit())
                        <a href="{{ route('account.business-application.step-one') }}" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-slate-950">Edit business details</a>
                        <a href="{{ route('account.business-application.step-two') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-200">Edit and submit requirements</a>
                    @endif
                    @if (in_array($application->status, [\App\Enums\B2BApplicationStatus::Rejected, \App\Enums\B2BApplicationStatus::Withdrawn], true))
                        <form method="POST" action="{{ route('account.business-application.restart') }}">@csrf<button class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-slate-950">Reapply using these details</button></form>
                    @endif
                    @if ($application->status->customerCanWithdraw())
                        <form method="POST" action="{{ route('account.business-application.withdraw') }}" onsubmit="return confirm('Withdraw this application?')">@csrf<button class="rounded-xl border border-rose-300 px-5 py-2.5 text-sm text-rose-700 dark:border-rose-900 dark:text-rose-300">Withdraw application</button></form>
                    @endif
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-medium text-slate-950 dark:text-white">Business details</h3>
                    <dl class="mt-5 space-y-4 text-sm">
                        @foreach ([
                            'Contact' => trim($application->contact_first_name.' '.$application->contact_last_name),
                            'Email' => $application->email,
                            'Phone' => $application->phone,
                            'Business type' => config('b2b_application.business_types.'.$application->business_type, $application->business_type),
                            'GSTIN' => $application->gstin ?: 'Not provided',
                            'FSSAI' => $application->fssai_number ?: 'Not provided',
                            'Location' => $application->city_name.', '.$application->state_name.' '.$application->postal_code,
                        ] as $label => $value)
                            <div class="grid grid-cols-[8rem_1fr] gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-slate-800 dark:text-slate-200">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="font-medium text-slate-950 dark:text-white">Purchase requirements</h3>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div><dt class="text-slate-500">Categories</dt><dd class="mt-1 text-slate-800 dark:text-slate-200">{{ collect($application->interested_categories ?? [])->map(fn ($item) => config('b2b_application.product_categories.'.$item, $item))->join(', ') ?: 'Not provided' }}</dd></div>
                        <div><dt class="text-slate-500">Monthly purchase</dt><dd class="mt-1 text-slate-800 dark:text-slate-200">{{ config('b2b_application.monthly_purchase_ranges.'.$application->estimated_monthly_purchase, 'Not provided') }}</dd></div>
                        <div><dt class="text-slate-500">Frequency</dt><dd class="mt-1 text-slate-800 dark:text-slate-200">{{ config('b2b_application.purchase_frequencies.'.$application->purchase_frequency, 'Not provided') }}</dd></div>
                        <div><dt class="text-slate-500">Notes</dt><dd class="mt-1 whitespace-pre-line text-slate-800 dark:text-slate-200">{{ $application->requirements_message ?: 'Not provided' }}</dd></div>
                    </dl>
                </section>
            </div>

            @if ($application->status === \App\Enums\B2BApplicationStatus::Approved && $application->profile)
                <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/40">
                    <h3 class="font-medium text-emerald-950 dark:text-emerald-100">Approved commercial terms</h3>
                    <div class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                        <div><p class="text-emerald-700 dark:text-emerald-300">Pay later</p><p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $application->profile->pay_later_enabled ? 'Enabled' : 'Not enabled' }}</p></div>
                        <div><p class="text-emerald-700 dark:text-emerald-300">Payment terms</p><p class="mt-1 font-medium text-emerald-950 dark:text-white">{{ $application->profile->payment_terms_days }} days</p></div>
                        <div><p class="text-emerald-700 dark:text-emerald-300">Minimum order value</p><p class="mt-1 font-medium text-emerald-950 dark:text-white">₹{{ number_format((float) $application->profile->minimum_order_value, 2) }}</p></div>
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                <h3 class="font-medium text-slate-950 dark:text-white">Application timeline</h3>
                <ol class="mt-6 space-y-5">
                    @forelse ($application->histories as $history)
                        <li class="relative pl-7 before:absolute before:left-[0.32rem] before:top-2 before:h-full before:w-px before:bg-slate-200 last:before:hidden dark:before:bg-slate-700">
                            <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full border-2 border-white bg-sky-500 ring-1 ring-sky-200 dark:border-slate-900 dark:ring-sky-800"></span>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1"><p class="text-sm font-medium text-slate-900 dark:text-white">{{ str($history->event)->replace('_', ' ')->title() }}</p><time class="text-xs text-slate-500">{{ $history->created_at?->format('d M Y, g:i a') }}</time></div>
                            @if ($history->message)<p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $history->message }}</p>@endif
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No timeline entries yet.</li>
                    @endforelse
                </ol>
            </section>
        @endif
    </div>
</x-layouts.business-account>

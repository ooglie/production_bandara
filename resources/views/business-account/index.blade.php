<x-layouts.business-account title="Business Account" heading="Business customers">
    <section class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.22em] text-sky-600 dark:text-sky-300">Wholesale and professional supply</p>
            <h2 class="mt-4 max-w-3xl text-4xl font-light leading-tight tracking-tight text-slate-950 sm:text-5xl dark:text-white">
                Better sourcing for restaurants, hotels, retailers and food businesses.
            </h2>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                Apply for a Bandara Business Account to access eligible business pricing, commercial pack sizes, GST invoices, business quantities and approved payment terms.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @if ($isB2B)
                    <a href="{{ route('account.business-application.show') }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">View business account</a>
                @elseif ($application)
                    <a href="{{ route('account.business-application.show') }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">View application status</a>
                @else
                    <a href="{{ route('account.business-application.step-one') }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-medium text-white shadow-sm hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">Apply for business pricing</a>
                @endif
                <a href="mailto:{{ config('mail.from.address') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-medium text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">Contact our team</a>
            </div>

            @if ($application)
                <div class="mt-7 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <x-b2b.status-badge :status="$application->status" />
                    <p class="text-sm text-slate-600 dark:text-slate-300">Application {{ $application->application_number }}</p>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-medium text-slate-950 dark:text-white">Designed for professional buyers</h3>
            <div class="mt-5 space-y-5">
                @foreach ([
                    ['Business pricing', 'View approved B2B prices and minimum order quantities after account approval.'],
                    ['Commercial quantities', 'Order suitable packs and quantities for kitchens, retail and institutional use.'],
                    ['GST documentation', 'Maintain business details for correct invoices and account records.'],
                    ['Approved payment terms', 'Eligible customers may receive pay-later access, limits and agreed credit days.'],
                ] as [$title, $description])
                    <div class="flex gap-4">
                        <span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sm text-sky-700 dark:bg-sky-950 dark:text-sky-200">✓</span>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $title }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mt-16">
        <div class="max-w-2xl">
            <p class="text-xs font-medium uppercase tracking-[0.22em] text-sky-600 dark:text-sky-300">How it works</p>
            <h2 class="mt-3 text-3xl font-light tracking-tight text-slate-950 dark:text-white">A controlled approval process</h2>
        </div>
        <div class="mt-7 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['01', 'Tell us about your business', 'Provide contact, registration and delivery-location details.'],
                ['02', 'Share your purchase needs', 'Select categories, frequency and the expected monthly purchase range.'],
                ['03', 'Bandara reviews the account', 'Your B2C access remains unchanged until the business account is approved.'],
            ] as [$number, $title, $description])
                <article class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs tracking-[0.2em] text-slate-400">{{ $number }}</p>
                    <h3 class="mt-4 text-lg font-medium text-slate-950 dark:text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $description }}</p>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.business-account>

<x-layouts.business-account title="B2B applications" heading="B2B applications">
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('success') }}</div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                'submitted' => 'Received',
                'under_review' => 'Under review',
                'more_information_required' => 'Information required',
                'approved' => 'Approved',
                'rejected' => 'Not approved',
            ] as $value => $label)
                <a href="{{ route('admin.b2b-applications.index', ['status' => $value]) }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs uppercase tracking-[0.14em] text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-light text-slate-950 dark:text-white">{{ number_format((int) ($counts[$value] ?? 0)) }}</p>
                </a>
            @endforeach
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form method="GET" action="{{ route('admin.b2b-applications.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                @php $field = 'block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white'; @endphp
                <label class="xl:col-span-2"><span class="mb-1 block text-xs font-medium text-slate-500">Search</span><input name="search" value="{{ request('search') }}" placeholder="Application, business, GSTIN…" class="{{ $field }}"></label>
                <label><span class="mb-1 block text-xs font-medium text-slate-500">Status</span><select name="status" class="{{ $field }}"><option value="">All statuses</option>@foreach (\App\Enums\B2BApplicationStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-medium text-slate-500">Business type</span><select name="business_type" class="{{ $field }}"><option value="">All types</option>@foreach (config('b2b_application.business_types') as $value => $label)<option value="{{ $value }}" @selected(request('business_type') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-medium text-slate-500">State</span><select name="state_id" class="{{ $field }}"><option value="">All states</option>@foreach ($states as $state)<option value="{{ $state->id }}" @selected((string) request('state_id') === (string) $state->id)>{{ $state->name }}</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-medium text-slate-500">Assigned to</span><select name="assigned_to" class="{{ $field }}"><option value="">Anyone</option><option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>@foreach ($staff as $member)<option value="{{ $member->id }}" @selected((string) request('assigned_to') === (string) $member->id)>{{ $member->name ?: $member->email }}</option>@endforeach</select></label>
                <div class="flex items-end gap-2 xl:col-span-6"><button class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-slate-950">Apply filters</button><a href="{{ route('admin.b2b-applications.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">Reset</a></div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-[0.12em] text-slate-500 dark:bg-slate-950/60">
                        <tr><th class="px-5 py-3 font-medium">Application</th><th class="px-5 py-3 font-medium">Business</th><th class="px-5 py-3 font-medium">Location</th><th class="px-5 py-3 font-medium">Expected purchase</th><th class="px-5 py-3 font-medium">Assigned</th><th class="px-5 py-3 font-medium">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($applications as $application)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-950/40">
                                <td class="whitespace-nowrap px-5 py-4 align-top"><a href="{{ route('admin.b2b-applications.show', $application) }}" class="font-medium text-sky-700 hover:underline dark:text-sky-300">{{ $application->application_number }}</a><p class="mt-1 text-xs text-slate-500">{{ $application->submitted_at?->format('d M Y') ?? 'Draft' }}</p></td>
                                <td class="px-5 py-4 align-top"><p class="font-medium text-slate-900 dark:text-white">{{ $application->legal_business_name }}</p><p class="mt-1 text-xs text-slate-500">{{ config('b2b_application.business_types.'.$application->business_type, $application->business_type) }} · {{ $application->contact_first_name }} {{ $application->contact_last_name }}</p></td>
                                <td class="px-5 py-4 align-top text-slate-600 dark:text-slate-300">{{ $application->city_name }}, {{ $application->state_name }}</td>
                                <td class="px-5 py-4 align-top text-slate-600 dark:text-slate-300">{{ config('b2b_application.monthly_purchase_ranges.'.$application->estimated_monthly_purchase, 'Not provided') }}</td>
                                <td class="px-5 py-4 align-top text-slate-600 dark:text-slate-300">{{ $application->assignee?->name ?? $application->assignee?->email ?? 'Unassigned' }}</td>
                                <td class="px-5 py-4 align-top"><x-b2b.status-badge :status="$application->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No B2B applications match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($applications->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $applications->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.business-account>

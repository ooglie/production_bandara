<x-layouts.admin title="Salary {{ $entry->staff_name }}" heading="Monthly salary record">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-2xl font-semibold text-slate-950 dark:text-white">{{ $entry->staff_name }}</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ \App\Models\SalaryEntry::paymentStatuses()[$entry->payment_status] ?? ucfirst($entry->payment_status) }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Salary month: {{ $entry->salary_month?->format('F Y') }} · {{ $entry->staffMember?->email }}</p>
            </div>
            @if ($canManage)
                <div class="flex flex-wrap gap-2">
                    @if (! $entry->isLockedForEditing())
                        <a href="{{ route('admin.finance.salary-entries.edit', $entry) }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">Edit record</a>
                        <form method="POST" action="{{ route('admin.finance.salary-entries.destroy', $entry) }}" onsubmit="return confirm('Cancel this salary record? The audit record will remain.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 dark:border-rose-800 dark:text-rose-300">Cancel record</button>
                        </form>
                    @else
                        <span class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Locked for audit history</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Basic salary</p>
                <p class="mt-2 text-xl font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $entry->basic_salary, 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Additions</p>
                <p class="mt-2 text-xl font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $entry->additions, 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Deductions</p>
                <p class="mt-2 text-xl font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $entry->deductions, 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-950 p-4 text-white dark:bg-slate-100 dark:text-slate-950">
                <p class="text-xs uppercase tracking-wide text-slate-300 dark:text-slate-600">Net payable</p>
                <p class="mt-2 text-xl font-semibold">₹{{ number_format((float) $entry->net_payable, 2) }}</p>
            </div>
        </div>

        <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-5 text-sm dark:border-slate-800 md:grid-cols-2 xl:grid-cols-3">
            <div><dt class="text-slate-500 dark:text-slate-400">Salary profile</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $entry->salaryProfile ? '#'.$entry->salaryProfile->id.' · effective '.$entry->salaryProfile->effective_from?->format('d M Y') : 'Manual snapshot' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Payment date</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $entry->payment_date?->format('d M Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Payment method</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ \App\Models\SalaryEntry::paymentMethods()[$entry->payment_method] ?? ($entry->payment_method ?: '—') }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Payment reference</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $entry->payment_reference ?: '—' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Created by</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $entry->createdBy?->name ?: 'System' }}</dd></div>
            <div><dt class="text-slate-500 dark:text-slate-400">Last updated by</dt><dd class="mt-1 font-medium text-slate-900 dark:text-white">{{ $entry->updatedBy?->name ?: 'System' }}</dd></div>
        </dl>

        @if ($entry->notes)
            <div class="mt-5 rounded-lg bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:bg-slate-800/60 dark:text-slate-200">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notes</p>
                {!! nl2br(e($entry->notes)) !!}
            </div>
        @endif
    </section>
</x-layouts.admin>

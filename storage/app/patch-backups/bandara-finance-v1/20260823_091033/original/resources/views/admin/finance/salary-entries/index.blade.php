<x-layouts.admin title="Monthly salaries" heading="Monthly salaries">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-950 dark:text-white">Salary register · {{ $month->format('F Y') }}</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">Each record is a month-specific snapshot. The unique staff/month rule prevents duplicate salary records.</p>
            </div>
            @if ($canManage)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.finance.salary-entries.create', ['month' => $month->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Manual entry</a>
                    <form method="POST" action="{{ route('admin.finance.salary-entries.generate-month') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="month" name="month" value="{{ $month->format('Y-m') }}" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">Generate month</button>
                    </form>
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.finance.salary-entries.index') }}" class="mt-5 grid gap-3 sm:grid-cols-4">
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Month</span>
                <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
            <label class="block sm:col-span-2">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Search</span>
                <input name="search" value="{{ request('search') }}" placeholder="Staff name, email, payment reference" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
            <div class="flex items-end gap-2">
                <label class="min-w-0 flex-1">
                    <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Status</span>
                    <select name="payment_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">All</option>
                        @foreach (\App\Models\SalaryEntry::paymentStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Filter</button>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <th class="px-4 py-3 font-medium">Staff member</th>
                        <th class="px-4 py-3 text-right font-medium">Basic</th>
                        <th class="px-4 py-3 text-right font-medium">Additions</th>
                        <th class="px-4 py-3 text-right font-medium">Deductions</th>
                        <th class="px-4 py-3 text-right font-medium">Net payable</th>
                        <th class="px-4 py-3 font-medium">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($entries as $entry)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 align-top">
                                <a href="{{ route('admin.finance.salary-entries.show', $entry) }}" class="font-medium text-slate-950 hover:underline dark:text-white">{{ $entry->staff_name }}</a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $entry->staffMember?->email }}{{ $entry->salary_profile_id ? ' · Profile #'.$entry->salary_profile_id : ' · Manual rate' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-slate-700 dark:text-slate-300">₹{{ number_format((float) $entry->basic_salary, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-slate-700 dark:text-slate-300">₹{{ number_format((float) $entry->additions, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top text-slate-700 dark:text-slate-300">₹{{ number_format((float) $entry->deductions, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $entry->net_payable, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ \App\Models\SalaryEntry::paymentStatuses()[$entry->payment_status] ?? ucfirst($entry->payment_status) }}</span>
                                @if ($entry->payment_date)<p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $entry->payment_date->format('d M Y') }}</p>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No salary records exist for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $entries->links() }}</div>
        @endif
    </section>
</x-layouts.admin>

<x-layouts.admin title="Salary profiles" heading="Salary profiles">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-950 dark:text-white">Historical salary rates</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">Create a new dated profile when a salary changes. Editing a current User record is intentionally not used for payroll, so earlier salary months retain their original rate.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('admin.finance.salary-profiles.create') }}" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">New salary profile</a>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.finance.salary-profiles.index') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
            <label class="block min-w-0 flex-1">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Search staff</span>
                <input name="search" value="{{ request('search') }}" placeholder="Name or email" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
            <label class="block sm:w-48">
                <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Status</span>
                <select name="active" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="">All</option>
                    <option value="yes" @selected(request('active') === 'yes')>Active</option>
                    <option value="no" @selected(request('active') === 'no')>Inactive</option>
                </select>
            </label>
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Filter</button>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        <th class="px-4 py-3 font-medium">Staff member</th>
                        <th class="px-4 py-3 text-right font-medium">Monthly salary</th>
                        <th class="px-4 py-3 font-medium">Effective period</th>
                        <th class="px-4 py-3 font-medium">Payment day</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        @if ($canManage)<th class="px-4 py-3 text-right font-medium">Actions</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($profiles as $profile)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium text-slate-950 dark:text-white">{{ $profile->staffMember?->name ?: 'Former staff member' }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $profile->staffMember?->email }} · {{ $profile->salary_entries_count }} monthly record(s)</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right align-top font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $profile->monthly_salary, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700 dark:text-slate-300">{{ $profile->effective_from?->format('d M Y') }} – {{ $profile->effective_to?->format('d M Y') ?: 'Open' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top text-slate-700 dark:text-slate-300">Day {{ $profile->payment_day }}{{ $profile->payment_day > 28 ? ' (or month-end)' : '' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 align-top"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $profile->is_active ? 'Active' : 'Inactive' }}</span></td>
                            @if ($canManage)
                                <td class="whitespace-nowrap px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.finance.salary-profiles.edit', $profile) }}" class="font-medium text-slate-700 hover:underline dark:text-slate-200">Edit</a>
                                        <form method="POST" action="{{ route('admin.finance.salary-profiles.destroy', $profile) }}" onsubmit="return confirm('Delete or deactivate this salary profile?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-rose-600 dark:text-rose-400">{{ $profile->salary_entries_count ? 'Deactivate' : 'Delete' }}</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No salary profiles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($profiles->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">{{ $profiles->links() }}</div>
        @endif
    </section>
</x-layouts.admin>

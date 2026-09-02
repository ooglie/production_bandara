@extends('layouts.company')

@section('title', 'Salary profiles')
@section('breadcrumb', 'Admin · Finance · Salary profiles')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Salary profiles</h1>
            <p class="mt-1 max-w-3xl text-xs leading-5 text-gray-500 dark:text-gray-400">
                Preserve salary history with effective-dated profiles. Create a new profile when a staff member's monthly salary changes.
            </p>
        </div>
        @if ($canManage)
            <a href="{{ route('admin.finance.salary-profiles.create') }}"
               class="inline-flex w-fit items-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                New salary profile
            </a>
        @endif
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <form method="GET" action="{{ route('admin.finance.salary-profiles.index') }}"
          class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_auto]">
            <div>
                <label for="salary-profile-search" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Search staff</label>
                <input id="salary-profile-search" name="search" value="{{ request('search') }}" placeholder="Name or email"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-gray-500">
            </div>
            <div>
                <label for="salary-profile-active" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select id="salary-profile-active" name="active"
                        class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-gray-500">
                    <option value="">All profiles</option>
                    <option value="yes" @selected(request('active') === 'yes')>Active</option>
                    <option value="no" @selected(request('active') === 'no')>Inactive</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Apply</button>
                @if (request()->query())
                    <a href="{{ route('admin.finance.salary-profiles.index') }}" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">Staff member</th>
                        <th class="px-3 py-2.5 text-right font-medium text-gray-500 dark:text-gray-400">Monthly salary</th>
                        <th class="px-3 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">Effective period</th>
                        <th class="px-3 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">Payment day</th>
                        <th class="px-3 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                        @if ($canManage)
                            <th class="px-3 py-2.5 text-right font-medium text-gray-500 dark:text-gray-400">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($profiles as $profile)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <td class="px-3 py-2.5 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">{{ $profile->staffMember?->name ?: 'Former staff member' }}</div>
                                <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $profile->staffMember?->email ?: 'Staff account unavailable' }} · {{ $profile->salary_entries_count }} monthly record(s)
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right align-top font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $profile->monthly_salary, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top text-gray-700 dark:text-gray-300">
                                {{ $profile->effective_from?->format('d M Y') }} – {{ $profile->effective_to?->format('d M Y') ?: 'Open' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top text-gray-700 dark:text-gray-300">
                                Day {{ $profile->payment_day }}{{ $profile->payment_day > 28 ? ' (month-end where required)' : '' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] {{ $profile->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400' }}">
                                    {{ $profile->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            @if ($canManage)
                                <td class="whitespace-nowrap px-3 py-2.5 text-right align-top">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('admin.finance.salary-profiles.edit', $profile) }}" class="text-[11px] font-medium text-gray-700 hover:underline dark:text-gray-300">Edit</a>
                                        <form method="POST" action="{{ route('admin.finance.salary-profiles.destroy', $profile) }}" onsubmit="return confirm('Delete or deactivate this salary profile?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[11px] font-medium text-red-600 hover:underline dark:text-red-400">{{ $profile->salary_entries_count ? 'Deactivate' : 'Delete' }}</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">No salary profiles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($profiles->hasPages())
            <div class="border-t border-gray-200 px-3 py-2.5 dark:border-gray-800">{{ $profiles->links() }}</div>
        @endif
    </section>
</div>
@endsection

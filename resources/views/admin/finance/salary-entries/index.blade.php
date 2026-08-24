@extends('layouts.company')

@section('title', 'Monthly salaries')
@section('breadcrumb', 'Admin · Finance · Monthly salaries')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Monthly salaries</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Fixed monthly salary snapshots with optional additions, deductions, and payment tracking.</p>
        </div>
        @if ($canManage)
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.finance.salary-entries.create', ['month' => $month->format('Y-m')]) }}"
                   class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">
                    Manual entry
                </a>
                <form method="POST" action="{{ route('admin.finance.salary-entries.generate-month') }}" class="flex items-center gap-2" onsubmit="return confirm('Generate missing salary records for {{ $month->format('F Y') }}? Existing records will not be duplicated.')">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                    <button type="submit" class="rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Generate month</button>
                </form>
            </div>
        @endif
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <form method="GET" action="{{ route('admin.finance.salary-entries.index') }}" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-[10rem_minmax(0,1fr)_12rem_auto]">
            <div>
                <label for="salary-month-filter" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Salary month</label>
                <input id="salary-month-filter" type="month" name="month" value="{{ $month->format('Y-m') }}" class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label for="salary-search" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input id="salary-search" name="search" value="{{ request('search') }}" placeholder="Staff name, email, or payment reference" class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
            </div>
            <div>
                <label for="salary-payment-status" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment status</label>
                <select id="salary-payment-status" name="payment_status" class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\SalaryEntry::paymentStatuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Apply</button>
                @if (request('search') || request('payment_status'))
                    <a href="{{ route('admin.finance.salary-entries.index', ['month' => $month->format('Y-m')]) }}" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <div class="border-b border-gray-200 px-3 py-2.5 dark:border-gray-800">
            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $month->format('F Y') }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-3 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">Staff member</th>
                        <th class="px-3 py-2.5 text-right font-medium text-gray-500 dark:text-gray-400">Basic</th>
                        <th class="px-3 py-2.5 text-right font-medium text-gray-500 dark:text-gray-400">Additions</th>
                        <th class="px-3 py-2.5 text-right font-medium text-gray-500 dark:text-gray-400">Deductions</th>
                        <th class="px-3 py-2.5 text-right font-medium text-gray-500 dark:text-gray-400">Net payable</th>
                        <th class="px-3 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($entries as $entry)
                        @php
                            $salaryStatusClass = match ($entry->payment_status) {
                                \App\Models\SalaryEntry::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
                                \App\Models\SalaryEntry::STATUS_HELD => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300',
                                \App\Models\SalaryEntry::STATUS_CANCELLED => 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400',
                                default => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <td class="px-3 py-2.5 align-top">
                                <a href="{{ route('admin.finance.salary-entries.show', $entry) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-50">{{ $entry->staff_name }}</a>
                                <div class="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $entry->staffMember?->email ?: 'Staff account unavailable' }} · {{ $entry->salary_profile_id ? 'Profile #'.$entry->salary_profile_id : 'Manual rate' }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right align-top text-gray-700 dark:text-gray-300">₹{{ number_format((float) $entry->basic_salary, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right align-top text-gray-700 dark:text-gray-300">₹{{ number_format((float) $entry->additions, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right align-top text-gray-700 dark:text-gray-300">₹{{ number_format((float) $entry->deductions, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right align-top font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $entry->net_payable, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] {{ $salaryStatusClass }}">{{ \App\Models\SalaryEntry::paymentStatuses()[$entry->payment_status] ?? ucfirst($entry->payment_status) }}</span>
                                @if ($entry->payment_date)
                                    <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">{{ $entry->payment_date->format('d M Y') }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">No salary records exist for this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div class="border-t border-gray-200 px-3 py-2.5 dark:border-gray-800">{{ $entries->links() }}</div>
        @endif
    </section>
</div>
@endsection

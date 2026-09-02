@extends('layouts.company')

@section('title', 'Salary '.$entry->staff_name)
@section('breadcrumb', 'Admin · Finance · Monthly salaries · '.$entry->staff_name)

@section('content')
@php
    $salaryStatusClass = match ($entry->payment_status) {
        \App\Models\SalaryEntry::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
        \App\Models\SalaryEntry::STATUS_HELD => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300',
        \App\Models\SalaryEntry::STATUS_CANCELLED => 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400',
        default => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300',
    };
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $entry->staff_name }}</h1>
                <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] {{ $salaryStatusClass }}">{{ \App\Models\SalaryEntry::paymentStatuses()[$entry->payment_status] ?? ucfirst($entry->payment_status) }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $entry->salary_month?->format('F Y') }} · {{ $entry->staffMember?->email ?: 'Staff account unavailable' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.finance.salary-entries.index', ['month' => $entry->salary_month?->format('Y-m')]) }}" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Back</a>
            @if ($canManage)
                @if (! $entry->isLockedForEditing())
                    <a href="{{ route('admin.finance.salary-entries.edit', $entry) }}" class="rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">Edit record</a>
                    <form method="POST" action="{{ route('admin.finance.salary-entries.destroy', $entry) }}" onsubmit="return confirm('Cancel this salary record? The audit record will remain.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded border border-red-300 px-3 py-1.5 text-[11px] font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950/20">Cancel record</button>
                    </form>
                @else
                    <span class="rounded border border-gray-200 px-3 py-1.5 text-[11px] text-gray-500 dark:border-gray-700 dark:text-gray-400">Locked for audit history</span>
                @endif
            @endif
        </div>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Basic salary</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $entry->basic_salary, 2) }}</div>
            </div>
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Additions</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $entry->additions, 2) }}</div>
            </div>
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Deductions</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $entry->deductions, 2) }}</div>
            </div>
            <div class="rounded border border-gray-900 bg-gray-900 p-3 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">
                <div class="text-[10px] uppercase tracking-wide text-gray-300 dark:text-gray-600">Net payable</div>
                <div class="mt-1 text-lg font-semibold">₹{{ number_format((float) $entry->net_payable, 2) }}</div>
            </div>
        </div>

        <dl class="mt-4 grid gap-3 border-t border-gray-200 pt-4 text-xs dark:border-gray-800 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <dt class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Salary profile</dt>
                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $entry->salaryProfile ? '#'.$entry->salaryProfile->id.' · effective '.$entry->salaryProfile->effective_from?->format('d M Y') : 'Manual snapshot' }}</dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment date</dt>
                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $entry->payment_date?->format('d M Y') ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment method</dt>
                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ \App\Models\SalaryEntry::paymentMethods()[$entry->payment_method] ?? ($entry->payment_method ?: '—') }}</dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment reference</dt>
                <dd class="mt-1 break-words font-medium text-gray-900 dark:text-gray-50">{{ $entry->payment_reference ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Created by</dt>
                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $entry->createdBy?->name ?: 'System' }}</dd>
            </div>
            <div>
                <dt class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Last updated by</dt>
                <dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $entry->updatedBy?->name ?: 'System' }}</dd>
            </div>
        </dl>

        @if ($entry->notes)
            <div class="mt-4 rounded border border-gray-100 bg-gray-50 p-3 text-xs leading-5 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                <div class="mb-1 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</div>
                {!! nl2br(e($entry->notes)) !!}
            </div>
        @endif
    </section>
</div>
@endsection

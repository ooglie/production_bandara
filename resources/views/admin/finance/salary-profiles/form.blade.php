@extends('layouts.company')

@section('title', $isEdit ? 'Edit salary profile' : 'New salary profile')
@section('breadcrumb', $isEdit ? 'Admin · Finance · Salary profiles · Edit' : 'Admin · Finance · Salary profiles · New')

@section('content')
@php
    $effectiveFrom = old('effective_from', $profile->effective_from?->format('Y-m-d') ?? today()->startOfMonth()->format('Y-m-d'));
    $effectiveTo = old('effective_to', $profile->effective_to?->format('Y-m-d'));
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-gray-500';
@endphp

<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $isEdit ? 'Edit salary profile' : 'New salary profile' }}</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Set the effective salary period without changing historical monthly records.</p>
        </div>
        <a href="{{ route('admin.finance.salary-profiles.index') }}" class="shrink-0 rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Back</a>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-5 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        Salary profile periods cannot overlap for the same staff member. Close the earlier profile and create a new dated profile when the salary changes. Existing monthly salary snapshots are not recalculated.
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.finance.salary-profiles.update', $profile) : route('admin.finance.salary-profiles.store') }}" class="space-y-4">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Profile details</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="salary-profile-user" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Staff member</label>
                    <select id="salary-profile-user" name="user_id" required class="{{ $inputClass }}">
                        <option value="">Select staff member</option>
                        @foreach ($staffMembers as $staff)
                            <option value="{{ $staff->id }}" @selected((string) old('user_id', $profile->user_id) === (string) $staff->id)>
                                {{ $staff->name }} · {{ $staff->email }}{{ isset($staff->is_active) && ! $staff->is_active ? ' · inactive account' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="monthly-salary" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Monthly salary</label>
                    <input id="monthly-salary" type="number" name="monthly_salary" value="{{ old('monthly_salary', $profile->monthly_salary) }}" min="0.01" step="0.01" required class="{{ $inputClass }}">
                    @error('monthly_salary')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="salary-payment-day" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment day</label>
                    <input id="salary-payment-day" type="number" name="payment_day" value="{{ old('payment_day', $profile->payment_day ?? 7) }}" min="1" max="31" required class="{{ $inputClass }}">
                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Days 29–31 are treated as month-end in shorter months.</p>
                    @error('payment_day')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="salary-effective-from" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Effective from</label>
                    <input id="salary-effective-from" type="date" name="effective_from" value="{{ $effectiveFrom }}" required class="{{ $inputClass }}">
                    @error('effective_from')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="salary-effective-to" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Effective to</label>
                    <input id="salary-effective-to" type="date" name="effective_to" value="{{ $effectiveTo }}" class="{{ $inputClass }}">
                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Leave blank while the profile remains current.</p>
                    @error('effective_to')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="salary-profile-notes" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea id="salary-profile-notes" name="notes" rows="4" maxlength="10000" class="{{ $inputClass }}">{{ old('notes', $profile->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $profile->is_active ?? true)) class="rounded border-gray-300 dark:border-gray-700">
                        <span>Active profile</span>
                    </label>
                    @error('is_active')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.finance.salary-profiles.index') }}" class="text-[11px] text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
            <button type="submit" class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-4 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                {{ $isEdit ? 'Save salary profile' : 'Create salary profile' }}
            </button>
        </div>
    </form>
</div>
@endsection

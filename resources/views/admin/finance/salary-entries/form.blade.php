@extends('layouts.company')

@section('title', $isEdit ? 'Edit monthly salary' : 'New monthly salary')
@section('breadcrumb', $isEdit ? 'Admin · Finance · Monthly salaries · Edit' : 'Admin · Finance · Monthly salaries · New')

@section('content')
@php
    $salaryMonth = old('salary_month', $entry->salary_month?->format('Y-m') ?? today()->format('Y-m'));
    $paymentDate = old('payment_date', $entry->payment_date?->format('Y-m-d'));
    $selectedStatus = old('payment_status', $entry->payment_status ?: \App\Models\SalaryEntry::STATUS_PENDING);
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-gray-500';
@endphp

<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $isEdit ? 'Edit monthly salary' : 'New monthly salary' }}</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Record the salary snapshot, adjustments, and payment status for one staff member and month.</p>
        </div>
        <a href="{{ $isEdit ? route('admin.finance.salary-entries.show', $entry) : route('admin.finance.salary-entries.index', ['month' => $salaryMonth]) }}" class="shrink-0 rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Back</a>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <div class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-[11px] leading-5 text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
        This first release uses fixed monthly salary with optional additions and deductions. Attendance, leave, PF, ESIC, professional tax, TDS, and automatic mid-month proration are not calculated here.
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.finance.salary-entries.update', $entry) : route('admin.finance.salary-entries.store') }}" class="space-y-4">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Staff and salary month</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @if ($isEdit)
                    <div>
                        <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300">Staff member</div>
                        <div class="mt-1 rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-50">{{ $entry->staff_name }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-gray-700 dark:text-gray-300">Salary month</div>
                        <div class="mt-1 rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-50">{{ $entry->salary_month?->format('F Y') }}</div>
                    </div>
                @else
                    <div>
                        <label for="salary-entry-user" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Staff member</label>
                        <select id="salary-entry-user" name="user_id" required class="{{ $inputClass }}">
                            <option value="">Select staff member</option>
                            @foreach ($staffMembers as $staff)
                                <option value="{{ $staff->id }}" @selected((string) old('user_id', $entry->user_id) === (string) $staff->id)>
                                    {{ $staff->name }} · {{ $staff->email }}{{ $staff->is_active ? '' : ' · inactive staff' }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="salary-entry-month" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Salary month</label>
                        <input id="salary-entry-month" type="month" name="salary_month" value="{{ $salaryMonth }}" required class="{{ $inputClass }}">
                        @error('salary_month')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Salary calculation</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="basic_salary" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Basic / monthly salary</label>
                    <input id="basic_salary" type="number" name="basic_salary" value="{{ old('basic_salary', $entry->basic_salary ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('basic_salary')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="additions" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Additions</label>
                    <input id="additions" type="number" name="additions" value="{{ old('additions', $entry->additions ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('additions')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="deductions" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Deductions</label>
                    <input id="deductions" type="number" name="deductions" value="{{ old('deductions', $entry->deductions ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('deductions')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="net_payable_preview" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Net payable</label>
                    <input id="net_payable_preview" readonly value="0.00" class="mt-1 w-full rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-semibold text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-50">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Payment and notes</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="salary-entry-status" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment status</label>
                    <select id="salary-entry-status" name="payment_status" required class="{{ $inputClass }}">
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_status')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="salary-payment-date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment date</label>
                    <input id="salary-payment-date" type="date" name="payment_date" value="{{ $paymentDate }}" class="{{ $inputClass }}">
                    @error('payment_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="salary-payment-method" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment method</label>
                    <select id="salary-payment-method" name="payment_method" class="{{ $inputClass }}">
                        <option value="">Select when paid</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', $entry->payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="salary-payment-reference" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment reference</label>
                    <input id="salary-payment-reference" name="payment_reference" value="{{ old('payment_reference', $entry->payment_reference) }}" maxlength="255" class="{{ $inputClass }}">
                    @error('payment_reference')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <label for="salary-entry-notes" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea id="salary-entry-notes" name="notes" rows="4" maxlength="10000" class="{{ $inputClass }}">{{ old('notes', $entry->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ $isEdit ? route('admin.finance.salary-entries.show', $entry) : route('admin.finance.salary-entries.index', ['month' => $salaryMonth]) }}" class="text-[11px] text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
            <button type="submit" class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-4 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                {{ $isEdit ? 'Save salary record' : 'Create salary record' }}
            </button>
        </div>
    </form>
</div>

<script>
(() => {
    const basic = document.getElementById('basic_salary');
    const additions = document.getElementById('additions');
    const deductions = document.getElementById('deductions');
    const total = document.getElementById('net_payable_preview');
    const paymentStatus = document.getElementById('salary-entry-status');
    const paymentDate = document.getElementById('salary-payment-date');
    const paymentMethod = document.getElementById('salary-payment-method');

    const updateTotal = () => {
        const basicValue = Number.parseFloat(basic?.value || '0') || 0;
        const additionsValue = Number.parseFloat(additions?.value || '0') || 0;
        const deductionsValue = Number.parseFloat(deductions?.value || '0') || 0;
        if (total) total.value = Math.max(0, basicValue + additionsValue - deductionsValue).toFixed(2);
    };

    const updatePaymentFields = () => {
        const isPaid = paymentStatus?.value === 'paid';
        if (paymentDate) paymentDate.required = isPaid;
        if (paymentMethod) paymentMethod.required = isPaid;
    };

    basic?.addEventListener('input', updateTotal);
    additions?.addEventListener('input', updateTotal);
    deductions?.addEventListener('input', updateTotal);
    paymentStatus?.addEventListener('change', updatePaymentFields);
    updateTotal();
    updatePaymentFields();
})();
</script>
@endsection

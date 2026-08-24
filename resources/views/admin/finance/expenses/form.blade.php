@extends('layouts.company')

@section('title', $isEdit ? 'Edit business expense' : 'New business expense')
@section('breadcrumb', $isEdit ? 'Admin · Finance · Business expenses · Edit' : 'Admin · Finance · Business expenses · Create')

@section('content')
@php
    $expenseDate = old('expense_date', $expense->expense_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
    $dueDate = old('due_date', $expense->due_date?->format('Y-m-d'));
    $paidDate = old('paid_date', $expense->paid_date?->format('Y-m-d'));
    $selectedPaymentStatus = old('payment_status', $expense->payment_status ?: \App\Models\BusinessExpense::PAYMENT_UNPAID);
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500';
@endphp

<div class="space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $isEdit ? 'Edit business expense' : 'New business expense' }}</h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Save as a draft first. An authorised accountant can review and post it later.</p>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <form method="POST" action="{{ $isEdit ? route('admin.finance.expenses.update', $expense) : route('admin.finance.expenses.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Expense details</h2>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">The total is calculated from taxable amount plus GST.</p>
                </div>
                @if ($expense->recurring_expense_template_id)
                    <span class="inline-flex w-fit items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[10px] text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300">Generated from recurring template</span>
                @endif
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="expense-date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Expense date</label>
                    <input id="expense-date" type="date" name="expense_date" value="{{ $expenseDate }}" required class="{{ $inputClass }}">
                    @error('expense_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-2">
                    <label for="expense-category-id" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select id="expense-category-id" name="expense_category_id" required class="{{ $inputClass }}">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense->expense_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_id')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label for="expense-description" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <input id="expense-description" name="description" value="{{ old('description', $expense->description) }}" required maxlength="255" class="{{ $inputClass }}">
                    @error('description')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expense-payee" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payee</label>
                    <input id="expense-payee" name="payee" value="{{ old('payee', $expense->payee) }}" maxlength="255" class="{{ $inputClass }}">
                    @error('payee')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Amounts and payment</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="taxable_amount" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Taxable amount</label>
                    <input id="taxable_amount" type="number" name="taxable_amount" value="{{ old('taxable_amount', $expense->taxable_amount ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('taxable_amount')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="gst_amount" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">GST amount</label>
                    <input id="gst_amount" type="number" name="gst_amount" value="{{ old('gst_amount', $expense->gst_amount ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('gst_amount')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="total_amount_preview" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Total amount</label>
                    <input id="total_amount_preview" value="0.00" readonly class="mt-1 w-full rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-semibold text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-50">
                </div>
                <div>
                    <label for="payment_status" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment status</label>
                    <select id="payment_status" name="payment_status" required class="{{ $inputClass }}">
                        @foreach (\App\Models\BusinessExpense::paymentStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPaymentStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_status')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_method" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment method</label>
                    <select id="payment_method" name="payment_method" class="{{ $inputClass }}">
                        <option value="">Select when paid</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', $expense->payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payment_method')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_reference" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment reference</label>
                    <input id="payment_reference" name="payment_reference" value="{{ old('payment_reference', $expense->payment_reference) }}" maxlength="255" class="{{ $inputClass }}">
                    @error('payment_reference')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="due_date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Due date</label>
                    <input id="due_date" type="date" name="due_date" value="{{ $dueDate }}" class="{{ $inputClass }}">
                    @error('due_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="paid_date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Paid date</label>
                    <input id="paid_date" type="date" name="paid_date" value="{{ $paidDate }}" class="{{ $inputClass }}">
                    @error('paid_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Receipt and notes</h2>
            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <div>
                    <label for="receipt" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Invoice or receipt attachment</label>
                    <input id="receipt" type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp"
                           class="mt-1 block w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-[10px] file:text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:file:bg-gray-800 dark:file:text-gray-200">
                    <p class="mt-1 text-[10px] text-gray-500">PDF or image, maximum 10 MB. The file is stored privately.</p>
                    @if ($expense->receipt_path)
                        <p class="mt-1 text-[10px] text-gray-500">Current file: {{ $expense->receipt_original_name }}</p>
                    @endif
                    @error('receipt')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expense-notes" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea id="expense-notes" name="notes" rows="5" maxlength="10000" class="{{ $inputClass }}">{{ old('notes', $expense->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ $isEdit ? route('admin.finance.expenses.show', $expense) : route('admin.finance.expenses.index') }}" class="text-[11px] text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-4 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                {{ $isEdit ? 'Save draft' : 'Create draft expense' }}
            </button>
        </div>
    </form>
</div>

<script>
(() => {
    const taxable = document.getElementById('taxable_amount');
    const gst = document.getElementById('gst_amount');
    const total = document.getElementById('total_amount_preview');
    const updateTotal = () => {
        const taxableValue = Number.parseFloat(taxable?.value || '0') || 0;
        const gstValue = Number.parseFloat(gst?.value || '0') || 0;
        if (total) total.value = (taxableValue + gstValue).toFixed(2);
    };
    taxable?.addEventListener('input', updateTotal);
    gst?.addEventListener('input', updateTotal);
    updateTotal();
})();
</script>
@endsection

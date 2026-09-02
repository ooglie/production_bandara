@extends('layouts.company')

@section('title', $isEdit ? 'Edit recurring expense' : 'New recurring expense')
@section('breadcrumb', $isEdit ? 'Admin · Finance · Recurring expenses · Edit' : 'Admin · Finance · Recurring expenses · Create')

@section('content')
@php
    $startDate = old('start_date', $template->start_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
    $endDate = old('end_date', $template->end_date?->format('Y-m-d'));
    $nextDueDate = old('next_due_date', $template->next_due_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500';
@endphp

<div class="space-y-4">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $isEdit ? 'Edit recurring expense' : 'New recurring expense' }}</h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Generation creates a reviewable draft and never posts an expense automatically.</p>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <form method="POST" action="{{ $isEdit ? route('admin.finance.recurring-expenses.update', $template) : route('admin.finance.recurring-expenses.store') }}" class="space-y-4">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Template details</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div class="md:col-span-2">
                    <label for="recurring-description" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <input id="recurring-description" name="description" value="{{ old('description', $template->description) }}" required maxlength="255" class="{{ $inputClass }}">
                    @error('description')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="recurring-payee" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payee</label>
                    <input id="recurring-payee" name="payee" value="{{ old('payee', $template->payee) }}" maxlength="255" class="{{ $inputClass }}">
                    @error('payee')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="recurring-category" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select id="recurring-category" name="expense_category_id" required class="{{ $inputClass }}">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $template->expense_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_id')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="recurring-frequency" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Frequency</label>
                    <select id="recurring-frequency" name="frequency" required class="{{ $inputClass }}">
                        @foreach ($frequencies as $value => $label)
                            <option value="{{ $value }}" @selected(old('frequency', $template->frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('frequency')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="default-payment-method" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Default payment method</label>
                    <select id="default-payment-method" name="default_payment_method" class="{{ $inputClass }}">
                        <option value="">None</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('default_payment_method', $template->default_payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('default_payment_method')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Expected amounts and dates</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="expected_taxable_amount" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Expected taxable amount</label>
                    <input id="expected_taxable_amount" type="number" name="expected_taxable_amount" value="{{ old('expected_taxable_amount', $template->expected_taxable_amount ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('expected_taxable_amount')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expected_gst_amount" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Expected GST</label>
                    <input id="expected_gst_amount" type="number" name="expected_gst_amount" value="{{ old('expected_gst_amount', $template->expected_gst_amount ?? 0) }}" min="0" step="0.01" required class="{{ $inputClass }}">
                    @error('expected_gst_amount')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="expected_total_preview" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Expected total</label>
                    <input id="expected_total_preview" readonly value="0.00" class="mt-1 w-full rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-semibold text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-50">
                </div>
                <div>
                    <label for="recurring-start-date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Start date</label>
                    <input id="recurring-start-date" type="date" name="start_date" value="{{ $startDate }}" required class="{{ $inputClass }}">
                    @error('start_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="recurring-end-date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">End date</label>
                    <input id="recurring-end-date" type="date" name="end_date" value="{{ $endDate }}" class="{{ $inputClass }}">
                    @error('end_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="recurring-next-due-date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Next due date</label>
                    <input id="recurring-next-due-date" type="date" name="next_due_date" value="{{ $nextDueDate }}" required class="{{ $inputClass }}">
                    @error('next_due_date')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <div>
                <label for="recurring-notes" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Notes copied to generated drafts</label>
                <textarea id="recurring-notes" name="notes" rows="5" maxlength="10000" class="{{ $inputClass }}">{{ old('notes', $template->notes) }}</textarea>
                @error('notes')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror
            </div>
            <label class="mt-3 flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $template->is_active ?? true)) class="rounded border-gray-300 dark:border-gray-700">
                Active recurring template
            </label>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.finance.recurring-expenses.index') }}" class="text-[11px] text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
            <button type="submit" class="rounded border border-gray-900 bg-gray-900 px-4 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">{{ $isEdit ? 'Save template' : 'Create template' }}</button>
        </div>
    </form>
</div>

<script>
(() => {
    const taxable = document.getElementById('expected_taxable_amount');
    const gst = document.getElementById('expected_gst_amount');
    const total = document.getElementById('expected_total_preview');
    const update = () => {
        const a = Number.parseFloat(taxable?.value || '0') || 0;
        const b = Number.parseFloat(gst?.value || '0') || 0;
        if (total) total.value = (a + b).toFixed(2);
    };
    taxable?.addEventListener('input', update);
    gst?.addEventListener('input', update);
    update();
})();
</script>
@endsection

@extends('layouts.company')

@section('title', 'Expense '.$expense->expense_number)
@section('breadcrumb', 'Admin · Finance · Business expenses · '.$expense->expense_number)

@section('content')
@php
    $recordClass = match ($expense->record_status) {
        \App\Models\BusinessExpense::STATUS_POSTED => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
        \App\Models\BusinessExpense::STATUS_VOID => 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400',
        default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300',
    };
    $paymentClass = $expense->payment_status === \App\Models\BusinessExpense::PAYMENT_PAID
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300'
        : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300';
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500';
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">{{ $expense->expense_number }}</h1>
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] {{ $recordClass }}">{{ \App\Models\BusinessExpense::recordStatuses()[$expense->record_status] ?? ucfirst($expense->record_status) }}</span>
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] {{ $paymentClass }}">{{ \App\Models\BusinessExpense::paymentStatuses()[$expense->payment_status] ?? ucfirst($expense->payment_status) }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $expense->expense_date?->format('d F Y') }} · {{ $expense->category?->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($canManage && $expense->isDraft())
                <a href="{{ route('admin.finance.expenses.edit', $expense) }}" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Edit draft</a>
            @endif
            @if ($canPost && $expense->isDraft())
                <form method="POST" action="{{ route('admin.finance.expenses.post', $expense) }}" onsubmit="return confirm('Post this expense? It will enter the operating summary and become non-editable.')">
                    @csrf
                    <button type="submit" class="rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">Post expense</button>
                </form>
            @endif
            @if ($canPost && $expense->isPosted())
                <form method="POST" action="{{ route('admin.finance.expenses.void', $expense) }}" onsubmit="return confirm('Void this posted expense? The audit record will remain.')">
                    @csrf
                    <button type="submit" class="rounded border border-red-300 px-3 py-1.5 text-[11px] font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950/30">Void</button>
                </form>
            @endif
        </div>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Taxable</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $expense->taxable_amount, 2) }}</div>
            </div>
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">GST</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $expense->gst_amount, 2) }}</div>
            </div>
            <div class="rounded border border-gray-900 bg-gray-900 p-3 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">
                <div class="text-[10px] uppercase tracking-wide text-gray-300 dark:text-gray-600">Total</div>
                <div class="mt-1 text-lg font-semibold">₹{{ number_format((float) $expense->total_amount, 2) }}</div>
            </div>
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Payee</div>
                <div class="mt-1 text-xs font-medium text-gray-900 dark:text-gray-50">{{ $expense->payee ?: '—' }}</div>
            </div>
        </div>

        <dl class="mt-4 grid gap-3 border-t border-gray-100 pt-4 text-xs dark:border-gray-800 md:grid-cols-2 xl:grid-cols-3">
            <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Description</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->description }}</dd></div>
            <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Due date</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->due_date?->format('d M Y') ?: '—' }}</dd></div>
            <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Paid date</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->paid_date?->format('d M Y') ?: '—' }}</dd></div>
            <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Payment method</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $paymentMethods[$expense->payment_method] ?? ($expense->payment_method ?: '—') }}</dd></div>
            <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Payment reference</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->payment_reference ?: '—' }}</dd></div>
            <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Created by</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->createdBy?->name ?: 'System' }}</dd></div>
            @if ($expense->posted_at)
                <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Posted</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->posted_at->format('d M Y, H:i') }} by {{ $expense->postedBy?->name ?: 'System' }}</dd></div>
            @endif
            @if ($expense->recurringTemplate)
                <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Recurring source</dt><dd class="mt-1 font-medium text-gray-900 dark:text-gray-50">{{ $expense->recurringTemplate->description }}</dd></div>
            @endif
            @if ($expense->receipt_path)
                <div><dt class="text-[10px] uppercase tracking-wide text-gray-500">Receipt</dt><dd class="mt-1"><a href="{{ route('admin.finance.expenses.attachment', $expense) }}" class="font-medium text-gray-900 underline dark:text-gray-50">Download {{ $expense->receipt_original_name }}</a></dd></div>
            @endif
        </dl>

        @if ($expense->notes)
            <div class="mt-4 rounded bg-gray-50 p-3 text-xs leading-5 text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <div class="mb-1 text-[10px] uppercase tracking-wide text-gray-500">Notes</div>
                {!! nl2br(e($expense->notes)) !!}
            </div>
        @endif
    </section>

    @if ($canPost && $expense->isPosted())
        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Update payment details</h2>
            <form method="POST" action="{{ route('admin.finance.expenses.payment.update', $expense) }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="posted-payment-status" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment status</label>
                    <select id="posted-payment-status" name="payment_status" required class="{{ $inputClass }}">
                        @foreach (\App\Models\BusinessExpense::paymentStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected($expense->payment_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="posted-payment-method" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Method</label>
                    <select id="posted-payment-method" name="payment_method" class="{{ $inputClass }}">
                        <option value="">Select</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($expense->payment_method === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="posted-payment-reference" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Reference</label>
                    <input id="posted-payment-reference" name="payment_reference" value="{{ $expense->payment_reference }}" maxlength="255" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="posted-paid-date" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Paid date</label>
                    <input id="posted-paid-date" type="date" name="paid_date" value="{{ $expense->paid_date?->format('Y-m-d') }}" class="{{ $inputClass }}">
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <button type="submit" class="rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">Save payment details</button>
                </div>
            </form>
        </section>
    @endif

    @if ($canManage && $expense->isDraft())
        <div class="flex justify-end">
            <form method="POST" action="{{ route('admin.finance.expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this draft expense?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-[11px] font-medium text-red-600 hover:underline dark:text-red-400">Delete draft</button>
            </form>
        </div>
    @endif
</div>
@endsection

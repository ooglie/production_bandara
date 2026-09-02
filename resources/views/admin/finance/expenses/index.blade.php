@extends('layouts.company')

@section('title', 'Business expenses')
@section('breadcrumb', 'Admin · Finance · Business expenses')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Business expenses</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Draft, review, post, and track ordinary operating expenses.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('admin.finance.expenses.create') }}"
               class="inline-flex items-center justify-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                + New expense
            </a>
        @endif
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <form method="GET" action="{{ route('admin.finance.expenses.index') }}"
          class="rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <label for="expense-month" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Month</label>
                <input id="expense-month" type="month" name="month" value="{{ $month->format('Y-m') }}"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500">
            </div>
            <div class="xl:col-span-2">
                <label for="expense-search" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Search</label>
                <input id="expense-search" name="search" value="{{ request('search') }}" placeholder="Reference, description, payee"
                       class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500">
            </div>
            <div>
                <label for="expense-category" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Category</label>
                <select id="expense-category" name="category_id"
                        class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="expense-record-status" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Record status</label>
                <select id="expense-record-status" name="record_status"
                        class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\BusinessExpense::recordStatuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('record_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="expense-payment-status" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Payment</label>
                <select id="expense-payment-status" name="payment_status"
                        class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500">
                    <option value="">All payments</option>
                    @foreach (\App\Models\BusinessExpense::paymentStatuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-end gap-2">
            <a href="{{ route('admin.finance.expenses.index', ['month' => $month->format('Y-m')]) }}" class="text-[11px] text-gray-500 hover:underline dark:text-gray-400">Reset</a>
            <button type="submit"
                    class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                Apply filters
            </button>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left font-medium">Date / reference</th>
                        <th class="px-3 py-2 text-left font-medium">Expense</th>
                        <th class="px-3 py-2 text-left font-medium">Status</th>
                        <th class="px-3 py-2 text-right font-medium">Taxable</th>
                        <th class="px-3 py-2 text-right font-medium">GST</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($expenses as $expense)
                        @php
                            $recordClass = match ($expense->record_status) {
                                \App\Models\BusinessExpense::STATUS_POSTED => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
                                \App\Models\BusinessExpense::STATUS_VOID => 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400',
                                default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300',
                            };
                            $paymentClass = $expense->payment_status === \App\Models\BusinessExpense::PAYMENT_PAID
                                ? 'text-emerald-700 dark:text-emerald-300'
                                : 'text-amber-700 dark:text-amber-300';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                <a href="{{ route('admin.finance.expenses.show', $expense) }}" class="font-medium text-gray-900 hover:underline dark:text-gray-50">{{ $expense->expense_number }}</a>
                                <div class="mt-0.5 text-[10px] text-gray-500">{{ $expense->expense_date?->format('d M Y') }}</div>
                            </td>
                            <td class="min-w-64 px-3 py-2 align-top">
                                <div class="font-medium text-gray-900 dark:text-gray-50">{{ $expense->description }}</div>
                                <div class="mt-0.5 text-[10px] text-gray-500">{{ $expense->category?->name }}{{ $expense->payee ? ' · '.$expense->payee : '' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] {{ $recordClass }}">{{ \App\Models\BusinessExpense::recordStatuses()[$expense->record_status] ?? ucfirst($expense->record_status) }}</span>
                                <div class="mt-1 text-[10px] font-medium {{ $paymentClass }}">{{ \App\Models\BusinessExpense::paymentStatuses()[$expense->payment_status] ?? ucfirst($expense->payment_status) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-gray-700 dark:text-gray-300">₹{{ number_format((float) $expense->taxable_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top text-gray-700 dark:text-gray-300">₹{{ number_format((float) $expense->gst_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right align-top font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $expense->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-xs text-gray-500 dark:text-gray-400">No expenses match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="border-t border-gray-100 px-3 py-2 dark:border-gray-800">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection

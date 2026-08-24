@extends('layouts.company')

@section('title', 'Finance')
@section('breadcrumb', 'Admin · Finance')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Finance</h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Salary, business expenses, and the monthly operating cash summary.</p>
        </div>
        <form method="GET" action="{{ route('admin.finance.index') }}" class="flex items-end gap-2 text-xs">
            <div>
                <label for="finance-month" class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Month</label>
                <input id="finance-month" type="month" name="month" value="{{ $month->format('Y-m') }}"
                       class="mt-1 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:focus:ring-gray-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                View
            </button>
        </form>
    </div>

    @include('admin.finance.partials.nav')
    @include('admin.finance.partials.flash')

    <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Operating cash summary</div>
                <h2 class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $summary['month_label'] }}</h2>
                <p class="mt-1 max-w-4xl text-[11px] leading-5 text-gray-500 dark:text-gray-400">
                    This is a provisional management summary, not Profit or Net Profit. Supplier invoices are purchases rather than Cost of Goods Sold, remaining inventory is not adjusted here, and GST is not presented as a statutory reconciliation.
                </p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">Management view</span>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Revenue collected</div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($summary['revenue_collected'], 2) }}</div>
                <div class="mt-1 text-[10px] text-gray-400">Captured customer payments dated in this month.</div>
            </div>
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Supplier purchases</div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($summary['supplier_purchases'], 2) }}</div>
                <div class="mt-1 text-[10px] text-gray-400">Non-cancelled vendor invoices by invoice date.</div>
            </div>
            @if ($canViewSalaryAggregate)
                <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500">Salary expense</div>
                    <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($summary['salary_expense'], 2) }}</div>
                    <div class="mt-1 text-[10px] text-gray-400">Non-cancelled monthly salary snapshots.</div>
                </div>
            @endif
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Other operating expenses</div>
                <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($summary['other_operating_expenses'], 2) }}</div>
                <div class="mt-1 text-[10px] text-gray-400">Posted expenses excluding staff salaries.</div>
            </div>
            @if ($canViewSalaryAggregate)
                <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500">Total operating outflow</div>
                    <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($summary['total_operating_outflow'], 2) }}</div>
                    <div class="mt-1 text-[10px] text-gray-400">Supplier purchases, salaries, and posted expenses.</div>
                </div>
                <div class="rounded border border-gray-900 bg-gray-900 p-3 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">
                    <div class="text-[10px] uppercase tracking-wide text-gray-300 dark:text-gray-600">Provisional operating balance</div>
                    <div class="mt-1 text-xl font-semibold">₹{{ number_format($summary['provisional_operating_cash_balance'], 2) }}</div>
                    <div class="mt-1 text-[10px] text-gray-300 dark:text-gray-600">Collected revenue less operating outflow.</div>
                </div>
            @else
                <div class="rounded border border-amber-200 bg-amber-50 p-3 text-[11px] leading-5 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200 sm:col-span-2">
                    Combined outflow and provisional balance are hidden because your account cannot view the aggregate salary component.
                </div>
            @endif
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            <div class="rounded bg-gray-50 px-3 py-2.5 dark:bg-gray-900">
                <div class="text-[11px] text-gray-500">Draft expenses awaiting review</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">{{ number_format($summary['draft_expense_count']) }}</div>
            </div>
            <div class="rounded bg-gray-50 px-3 py-2.5 dark:bg-gray-900">
                <div class="text-[11px] text-gray-500">Posted but unpaid expenses</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">{{ number_format($summary['unpaid_expense_count']) }}</div>
            </div>
            @if ($canViewSalaryAggregate)
                <div class="rounded bg-gray-50 px-3 py-2.5 dark:bg-gray-900">
                    <div class="text-[11px] text-gray-500">Pending or held salaries</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">{{ number_format($summary['pending_salary_count']) }}</div>
                </div>
            @endif
        </div>
    </section>

    <div class="grid gap-4 xl:grid-cols-2">
        @if ($canViewExpenses)
            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recent business expenses</h2>
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Drafts enter the summary only after posting.</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($canManageExpenses)
                            <a href="{{ route('admin.finance.expenses.create') }}" class="rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-[11px] font-medium text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">New</a>
                        @endif
                        <a href="{{ route('admin.finance.expenses.index', ['month' => $month->format('Y-m')]) }}" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">All</a>
                    </div>
                </div>
                <div class="mt-3 divide-y divide-gray-100 text-xs dark:divide-gray-800">
                    @forelse ($recentExpenses as $expense)
                        <a href="{{ route('admin.finance.expenses.show', $expense) }}" class="flex items-center justify-between gap-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <div class="min-w-0">
                                <div class="truncate font-medium text-gray-900 dark:text-gray-50">{{ $expense->description }}</div>
                                <div class="mt-0.5 text-[10px] text-gray-500">{{ $expense->expense_date?->format('d M Y') }} · {{ $expense->category?->name }} · {{ ucfirst($expense->record_status) }}</div>
                            </div>
                            <div class="shrink-0 font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $expense->total_amount, 2) }}</div>
                        </a>
                    @empty
                        <div class="py-4 text-[11px] text-gray-500">No expenses recorded yet.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($canViewSalaryRecords)
            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Salary records</h2>
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Restricted payroll details for {{ $summary['month_label'] }}.</p>
                    </div>
                    <a href="{{ route('admin.finance.salary-entries.index', ['month' => $month->format('Y-m')]) }}" class="rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Open</a>
                </div>
                <div class="mt-3 divide-y divide-gray-100 text-xs dark:divide-gray-800">
                    @forelse ($recentSalaryEntries as $entry)
                        <a href="{{ route('admin.finance.salary-entries.show', $entry) }}" class="flex items-center justify-between gap-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <div class="min-w-0">
                                <div class="truncate font-medium text-gray-900 dark:text-gray-50">{{ $entry->staff_name }}</div>
                                <div class="mt-0.5 text-[10px] text-gray-500">{{ \App\Models\SalaryEntry::paymentStatuses()[$entry->payment_status] ?? ucfirst($entry->payment_status) }}</div>
                            </div>
                            <div class="shrink-0 font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format((float) $entry->net_payable, 2) }}</div>
                        </a>
                    @empty
                        <div class="py-4 text-[11px] text-gray-500">No salary snapshots exist for this month.</div>
                    @endforelse
                </div>
            </section>
        @elseif ($canViewSalaryAggregate)
            <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Salary privacy</h2>
                <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Your role can view the aggregate salary figure only. Staff names, salary rates, additions, deductions, and payment references remain hidden.</p>
            </section>
        @endif
    </div>

    @if ($summary['category_breakdown'] !== [])
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Posted operating expenses by category</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-2 text-left font-medium">Category</th>
                            <th class="px-3 py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($summary['category_breakdown'] as $row)
                            <tr>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-gray-50">₹{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection

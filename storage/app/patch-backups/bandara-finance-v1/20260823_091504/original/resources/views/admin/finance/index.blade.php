<x-layouts.admin title="Operating cash summary" heading="Finance">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Operating cash summary</p>
                <h2 class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ $summary['month_label'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                    This combines collected revenue, supplier invoices, salary snapshots, and posted operating expenses. It is a provisional management view, not profit or net profit. Supplier purchases are not Cost of Goods Sold, inventory remaining at month-end is not adjusted here, and GST is not presented as a statutory tax reconciliation.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.finance.index') }}" class="flex items-end gap-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Month</span>
                    <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <button type="submit" class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950 dark:hover:bg-white">View</button>
            </form>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Revenue collected</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">₹{{ number_format($summary['revenue_collected'], 2) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Captured customer payments dated in this month.</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Supplier purchases</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">₹{{ number_format($summary['supplier_purchases'], 2) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Non-cancelled vendor invoices by invoice date.</p>
            </div>
            @if ($canViewSalaryAggregate)
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Salary expense</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">₹{{ number_format($summary['salary_expense'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Non-cancelled monthly salary snapshots; individual amounts remain restricted.</p>
                </div>
            @endif
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Other operating expenses</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">₹{{ number_format($summary['other_operating_expenses'], 2) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Posted business expenses, excluding the staff-salary category.</p>
            </div>
            @if ($canViewSalaryAggregate)
                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total operating outflow</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">₹{{ number_format($summary['total_operating_outflow'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Supplier invoices + salaries + posted operating expenses.</p>
                </div>
                <div class="rounded-xl bg-slate-950 p-4 text-white dark:bg-slate-100 dark:text-slate-950">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-300 dark:text-slate-600">Provisional operating balance</p>
                    <p class="mt-2 text-2xl font-semibold">₹{{ number_format($summary['provisional_operating_cash_balance'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-300 dark:text-slate-600">Collected revenue less the operational outflow above.</p>
                </div>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200 sm:col-span-2">
                    Combined outflow and provisional balance are hidden because your account does not have permission to view the aggregate salary component.
                </div>
            @endif
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-sm text-slate-500 dark:text-slate-400">Draft expenses awaiting review</p>
                <p class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ number_format($summary['draft_expense_count']) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                <p class="text-sm text-slate-500 dark:text-slate-400">Posted but unpaid expenses</p>
                <p class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ number_format($summary['unpaid_expense_count']) }}</p>
            </div>
            @if ($canViewSalaryAggregate)
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/60">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pending or held salaries</p>
                    <p class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ number_format($summary['pending_salary_count']) }}</p>
                </div>
            @endif
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        @if ($canViewExpenses)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Recent business expenses</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Drafts do not enter the operating summary until posted.</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($canManageExpenses)
                            <a href="{{ route('admin.finance.expenses.create') }}" class="rounded-lg bg-slate-950 px-3 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">New</a>
                        @endif
                        <a href="{{ route('admin.finance.expenses.index', ['month' => $month->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">All</a>
                    </div>
                </div>

                <div class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($recentExpenses as $expense)
                        <a href="{{ route('admin.finance.expenses.show', $expense) }}" class="flex items-center justify-between gap-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $expense->description }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $expense->expense_date?->format('d M Y') }} · {{ $expense->category?->name }} · {{ ucfirst($expense->record_status) }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $expense->total_amount, 2) }}</p>
                        </a>
                    @empty
                        <p class="py-5 text-sm text-slate-500 dark:text-slate-400">No expenses recorded yet.</p>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($canViewSalaryRecords)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Salary records</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Restricted individual payroll details for {{ $summary['month_label'] }}.</p>
                    </div>
                    <a href="{{ route('admin.finance.salary-entries.index', ['month' => $month->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Open</a>
                </div>

                <div class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($recentSalaryEntries as $entry)
                        <a href="{{ route('admin.finance.salary-entries.show', $entry) }}" class="flex items-center justify-between gap-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $entry->staff_name }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ \App\Models\SalaryEntry::paymentStatuses()[$entry->payment_status] ?? ucfirst($entry->payment_status) }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-slate-950 dark:text-white">₹{{ number_format((float) $entry->net_payable, 2) }}</p>
                        </a>
                    @empty
                        <p class="py-5 text-sm text-slate-500 dark:text-slate-400">No salary snapshots exist for this month.</p>
                    @endforelse
                </div>
            </section>
        @elseif ($canViewSalaryAggregate)
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Salary privacy</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Your role can see only the aggregate salary figure. Staff names, salary profiles, additions, deductions, payment references, and individual monthly records remain hidden.
                </p>
            </section>
        @endif
    </div>

    @if ($summary['category_breakdown'] !== [])
        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Posted operating expenses by category</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">Category</th>
                            <th class="px-3 py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($summary['category_breakdown'] as $row)
                            <tr>
                                <td class="px-3 py-3 text-slate-800 dark:text-slate-200">{{ $row['name'] }}</td>
                                <td class="px-3 py-3 text-right font-medium text-slate-950 dark:text-white">₹{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.admin>

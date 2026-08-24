@php
    $financeDashboardSummary = null;
    $financeDashboardUser = auth()->user();
    $financeCanShowSalaryAggregate = \App\Support\FinanceAccess::canSeeSalaryAggregate($financeDashboardUser);

    try {
        if ($financeDashboardUser
            && \App\Support\FinanceAccess::allows($financeDashboardUser, \App\Support\FinanceAccess::SUMMARY)
            && \Illuminate\Support\Facades\Schema::hasTable('business_expenses')) {
            $financeDashboardSummary = app(\App\Services\Finance\OperatingCashSummaryService::class)->forMonth();
        }
    } catch (\Throwable $exception) {
        report($exception);
    }
@endphp

@if ($financeDashboardSummary)
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" aria-labelledby="finance-dashboard-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Operating cash summary</p>
                <h2 id="finance-dashboard-heading" class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ $financeDashboardSummary['month_label'] }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Management view only. It is not profit, net profit, or a statutory P&amp;L.</p>
            </div>
            <a href="{{ route('admin.finance.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                Open finance
            </a>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Revenue collected</p>
                <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">₹{{ number_format($financeDashboardSummary['revenue_collected'], 2) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Supplier invoices</p>
                <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">₹{{ number_format($financeDashboardSummary['supplier_purchases'], 2) }}</p>
            </div>
            @if ($financeCanShowSalaryAggregate)
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Salaries</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">₹{{ number_format($financeDashboardSummary['salary_expense'], 2) }}</p>
                </div>
            @endif
            <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Other expenses</p>
                <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">₹{{ number_format($financeDashboardSummary['other_operating_expenses'], 2) }}</p>
            </div>
            @if ($financeCanShowSalaryAggregate)
                <div class="rounded-lg bg-slate-950 p-4 text-white dark:bg-slate-100 dark:text-slate-950">
                    <p class="text-xs uppercase tracking-wide text-slate-300 dark:text-slate-600">Provisional balance</p>
                    <p class="mt-2 text-lg font-semibold">₹{{ number_format($financeDashboardSummary['provisional_operating_cash_balance'], 2) }}</p>
                </div>
            @endif
        </div>
    </section>
@endif

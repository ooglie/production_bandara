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
    <section class="mt-4 rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950" aria-labelledby="finance-dashboard-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Operating cash summary</div>
                <h2 id="finance-dashboard-heading" class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">{{ $financeDashboardSummary['month_label'] }}</h2>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Management view only; this is not profit, net profit, or a statutory P&amp;L.</p>
            </div>
            @if (\Illuminate\Support\Facades\Route::has('admin.finance.index'))
                <a href="{{ route('admin.finance.index') }}"
                   class="inline-flex items-center justify-center rounded border border-gray-300 px-3 py-1.5 text-[11px] font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">
                    Open finance
                </a>
            @endif
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Revenue collected</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($financeDashboardSummary['revenue_collected'], 2) }}</div>
            </div>
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Supplier invoices</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($financeDashboardSummary['supplier_purchases'], 2) }}</div>
            </div>
            @if ($financeCanShowSalaryAggregate)
                <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                    <div class="text-[10px] uppercase tracking-wide text-gray-500">Salaries</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($financeDashboardSummary['salary_expense'], 2) }}</div>
                </div>
            @endif
            <div class="rounded border border-gray-100 p-3 dark:border-gray-800">
                <div class="text-[10px] uppercase tracking-wide text-gray-500">Other expenses</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">₹{{ number_format($financeDashboardSummary['other_operating_expenses'], 2) }}</div>
            </div>
            @if ($financeCanShowSalaryAggregate)
                <div class="rounded border border-gray-900 bg-gray-900 p-3 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900">
                    <div class="text-[10px] uppercase tracking-wide text-gray-300 dark:text-gray-600">Provisional balance</div>
                    <div class="mt-1 text-lg font-semibold">₹{{ number_format($financeDashboardSummary['provisional_operating_cash_balance'], 2) }}</div>
                </div>
            @endif
        </div>
    </section>
@endif

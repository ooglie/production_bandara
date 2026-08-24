@php
    $financeUser = auth()->user();
    $financeLinkClass = 'inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800';
    $financeActiveClass = 'border-slate-900 bg-slate-900 text-white hover:bg-slate-800 dark:border-slate-100 dark:bg-slate-100 dark:text-slate-900';
@endphp

<nav class="mb-6 flex flex-wrap gap-2" aria-label="Finance navigation">
    @if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::SUMMARY))
        <a href="{{ route('admin.finance.index') }}" class="{{ $financeLinkClass }} {{ request()->routeIs('admin.finance.index') ? $financeActiveClass : '' }}">
            Operating summary
        </a>
    @endif

    @if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::EXPENSES_VIEW))
        <a href="{{ route('admin.finance.expenses.index') }}" class="{{ $financeLinkClass }} {{ request()->routeIs('admin.finance.expenses.*') ? $financeActiveClass : '' }}">
            Business expenses
        </a>
        <a href="{{ route('admin.finance.recurring-expenses.index') }}" class="{{ $financeLinkClass }} {{ request()->routeIs('admin.finance.recurring-expenses.*') ? $financeActiveClass : '' }}">
            Recurring expenses
        </a>
    @endif

    @if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::EXPENSE_SETTINGS_MANAGE))
        <a href="{{ route('admin.finance.expense-categories.index') }}" class="{{ $financeLinkClass }} {{ request()->routeIs('admin.finance.expense-categories.*') ? $financeActiveClass : '' }}">
            Expense categories
        </a>
    @endif

    @if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::SALARY_VIEW))
        <a href="{{ route('admin.finance.salary-entries.index') }}" class="{{ $financeLinkClass }} {{ request()->routeIs('admin.finance.salary-entries.*') ? $financeActiveClass : '' }}">
            Monthly salaries
        </a>
        <a href="{{ route('admin.finance.salary-profiles.index') }}" class="{{ $financeLinkClass }} {{ request()->routeIs('admin.finance.salary-profiles.*') ? $financeActiveClass : '' }}">
            Salary profiles
        </a>
    @endif
</nav>

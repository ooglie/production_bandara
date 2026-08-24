<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BusinessExpense;
use App\Models\SalaryEntry;
use App\Services\Finance\OperatingCashSummaryService;
use App\Support\FinanceAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __invoke(Request $request, OperatingCashSummaryService $summaryService): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SUMMARY);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'])
            : CarbonImmutable::today()->startOfMonth();

        $summary = $summaryService->forMonth($month);
        $canViewExpenses = FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_VIEW);
        $canManageExpenses = FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_MANAGE);
        $canPostExpenses = FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_POST);
        $canManageExpenseSettings = FinanceAccess::allows($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);
        $canViewSalaryAggregate = FinanceAccess::canSeeSalaryAggregate($request->user());
        $canViewSalaryRecords = FinanceAccess::canSeeIndividualSalary($request->user());
        $canManageSalaryRecords = FinanceAccess::allows($request->user(), FinanceAccess::SALARY_MANAGE);

        $recentExpenses = collect();
        if ($canViewExpenses && Schema::hasTable('business_expenses')) {
            $recentExpenses = BusinessExpense::query()
                ->with('category')
                ->latest('expense_date')
                ->latest('id')
                ->limit(6)
                ->get();
        }

        $recentSalaryEntries = collect();
        if ($canViewSalaryRecords && Schema::hasTable('salary_entries')) {
            $recentSalaryEntries = SalaryEntry::query()
                ->whereDate('salary_month', $month->startOfMonth()->toDateString())
                ->orderBy('staff_name')
                ->limit(8)
                ->get();
        }

        return view('admin.finance.index', compact(
            'summary',
            'month',
            'canViewExpenses',
            'canManageExpenses',
            'canPostExpenses',
            'canManageExpenseSettings',
            'canViewSalaryAggregate',
            'canViewSalaryRecords',
            'canManageSalaryRecords',
            'recentExpenses',
            'recentSalaryEntries',
        ));
    }
}

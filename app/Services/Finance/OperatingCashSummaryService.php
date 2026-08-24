<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use App\Models\SalaryEntry;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperatingCashSummaryService
{
    /**
     * This is an operational management summary, not a statutory P&L.
     * Supplier invoices are grouped by invoice date and salaries by salary month.
     * Revenue is grouped by captured-payment date.
     *
     * @return array<string, mixed>
     */
    public function forMonth(CarbonInterface|string|null $month = null): array
    {
        $start = $month === null
            ? CarbonImmutable::today()->startOfMonth()
            : CarbonImmutable::parse($month)->startOfMonth();
        $end = $start->endOfMonth();

        $revenue = $this->revenueCollected($start, $end);
        $supplierPurchases = $this->supplierPurchases($start, $end);
        $salaryExpense = $this->salaryExpense($start);
        $otherExpenses = $this->otherOperatingExpenses($start, $end);
        $outflow = round($supplierPurchases + $salaryExpense + $otherExpenses, 2);

        return [
            'month' => $start,
            'month_label' => $start->format('F Y'),
            'revenue_collected' => $revenue,
            'supplier_purchases' => $supplierPurchases,
            'salary_expense' => $salaryExpense,
            'other_operating_expenses' => $otherExpenses,
            'total_operating_outflow' => $outflow,
            'provisional_operating_cash_balance' => round($revenue - $outflow, 2),
            'draft_expense_count' => $this->draftExpenseCount($start, $end),
            'unpaid_expense_count' => $this->unpaidExpenseCount($start, $end),
            'pending_salary_count' => $this->pendingSalaryCount($start),
            'category_breakdown' => $this->categoryBreakdown($start, $end),
        ];
    }

    private function revenueCollected(CarbonImmutable $start, CarbonImmutable $end): float
    {
        if (! Schema::hasTable('payments')) {
            return 0.0;
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $startAt = $start->startOfDay()->toDateTimeString();
        $endAt = $end->endOfDay()->toDateTimeString();

        return round((float) DB::table('payments')
            ->where('status', 'captured')
            ->where(function (Builder $query) use ($startDate, $endDate, $startAt, $endAt): void {
                $query->whereBetween('received_date', [$startDate, $endDate])
                    ->orWhere(function (Builder $query) use ($startAt, $endAt): void {
                        $query->whereNull('received_date')
                            ->whereBetween('paid_at', [$startAt, $endAt]);
                    })
                    ->orWhere(function (Builder $query) use ($startAt, $endAt): void {
                        $query->whereNull('received_date')
                            ->whereNull('paid_at')
                            ->whereBetween('created_at', [$startAt, $endAt]);
                    });
            })
            ->sum('amount'), 2);
    }

    private function supplierPurchases(CarbonImmutable $start, CarbonImmutable $end): float
    {
        if (! Schema::hasTable('vendor_invoices')) {
            return 0.0;
        }

        return round((float) DB::table('vendor_invoices')
            ->whereBetween('invoice_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount'), 2);
    }

    private function salaryExpense(CarbonImmutable $start): float
    {
        if (! Schema::hasTable('salary_entries')) {
            return 0.0;
        }

        return round((float) DB::table('salary_entries')
            ->whereNull('deleted_at')
            ->whereDate('salary_month', $start->toDateString())
            ->where('payment_status', '!=', SalaryEntry::STATUS_CANCELLED)
            ->sum('net_payable'), 2);
    }

    private function otherOperatingExpenses(CarbonImmutable $start, CarbonImmutable $end): float
    {
        if (! Schema::hasTable('business_expenses') || ! Schema::hasTable('expense_categories')) {
            return 0.0;
        }

        return round((float) DB::table('business_expenses as expenses')
            ->join('expense_categories as categories', 'categories.id', '=', 'expenses.expense_category_id')
            ->whereNull('expenses.deleted_at')
            ->whereNull('categories.deleted_at')
            ->whereBetween('expenses.expense_date', [$start->toDateString(), $end->toDateString()])
            ->where('expenses.record_status', BusinessExpense::STATUS_POSTED)
            ->where('categories.slug', '!=', 'staff-salaries')
            ->sum('expenses.total_amount'), 2);
    }

    private function draftExpenseCount(CarbonImmutable $start, CarbonImmutable $end): int
    {
        if (! Schema::hasTable('business_expenses')) {
            return 0;
        }

        return DB::table('business_expenses')
            ->whereNull('deleted_at')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->where('record_status', BusinessExpense::STATUS_DRAFT)
            ->count();
    }

    private function unpaidExpenseCount(CarbonImmutable $start, CarbonImmutable $end): int
    {
        if (! Schema::hasTable('business_expenses')) {
            return 0;
        }

        return DB::table('business_expenses')
            ->whereNull('deleted_at')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->where('record_status', BusinessExpense::STATUS_POSTED)
            ->where('payment_status', BusinessExpense::PAYMENT_UNPAID)
            ->count();
    }

    private function pendingSalaryCount(CarbonImmutable $start): int
    {
        if (! Schema::hasTable('salary_entries')) {
            return 0;
        }

        return DB::table('salary_entries')
            ->whereNull('deleted_at')
            ->whereDate('salary_month', $start->toDateString())
            ->whereIn('payment_status', [SalaryEntry::STATUS_PENDING, SalaryEntry::STATUS_HELD])
            ->count();
    }

    /** @return array<int, array{name:string, total:float}> */
    private function categoryBreakdown(CarbonImmutable $start, CarbonImmutable $end): array
    {
        if (! Schema::hasTable('business_expenses') || ! Schema::hasTable('expense_categories')) {
            return [];
        }

        return DB::table('business_expenses as expenses')
            ->join('expense_categories as categories', 'categories.id', '=', 'expenses.expense_category_id')
            ->whereNull('expenses.deleted_at')
            ->whereNull('categories.deleted_at')
            ->whereBetween('expenses.expense_date', [$start->toDateString(), $end->toDateString()])
            ->where('expenses.record_status', BusinessExpense::STATUS_POSTED)
            ->where('categories.slug', '!=', 'staff-salaries')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc(DB::raw('SUM(expenses.total_amount)'))
            ->selectRaw('categories.name, SUM(expenses.total_amount) as total')
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) $row->name,
                'total' => round((float) $row->total, 2),
            ])
            ->all();
    }
}

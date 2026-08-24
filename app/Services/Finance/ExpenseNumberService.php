<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class ExpenseNumberService
{
    public function generate(CarbonInterface|string|null $expenseDate = null): string
    {
        $date = $expenseDate === null
            ? CarbonImmutable::now()
            : CarbonImmutable::parse($expenseDate);

        $prefix = 'BEX-'.$date->format('Ym').'-';

        do {
            $candidate = $prefix.random_int(100000, 999999);
        } while (BusinessExpense::withTrashed()->where('expense_number', $candidate)->exists());

        return $candidate;
    }
}

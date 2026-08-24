<?php

use App\Http\Controllers\Admin\Finance\BusinessExpenseController;
use App\Http\Controllers\Admin\Finance\ExpenseCategoryController;
use App\Http\Controllers\Admin\Finance\FinanceDashboardController;
use App\Http\Controllers\Admin\Finance\RecurringExpenseController;
use App\Http\Controllers\Admin\Finance\SalaryEntryController;
use App\Http\Controllers\Admin\Finance\SalaryProfileController;
use App\Http\Middleware\EnsureFinanceCapability;
use App\Support\FinanceAccess;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->group(function (): void {
        Route::get('/', FinanceDashboardController::class)
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SUMMARY)
            ->name('index');

        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('expense-categories.index');
        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('expense-categories.store');
        Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('expense-categories.update');
        Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('expense-categories.destroy');

        Route::get('/expenses', [BusinessExpenseController::class, 'index'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_VIEW)
            ->name('expenses.index');
        Route::get('/expenses/create', [BusinessExpenseController::class, 'create'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_MANAGE)
            ->name('expenses.create');
        Route::post('/expenses', [BusinessExpenseController::class, 'store'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_MANAGE)
            ->name('expenses.store');
        Route::get('/expenses/{expense}', [BusinessExpenseController::class, 'show'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_VIEW)
            ->name('expenses.show');
        Route::get('/expenses/{expense}/edit', [BusinessExpenseController::class, 'edit'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_MANAGE)
            ->name('expenses.edit');
        Route::put('/expenses/{expense}', [BusinessExpenseController::class, 'update'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_MANAGE)
            ->name('expenses.update');
        Route::delete('/expenses/{expense}', [BusinessExpenseController::class, 'destroy'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_MANAGE)
            ->name('expenses.destroy');
        Route::post('/expenses/{expense}/post', [BusinessExpenseController::class, 'post'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_POST)
            ->name('expenses.post');
        Route::post('/expenses/{expense}/void', [BusinessExpenseController::class, 'void'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_POST)
            ->name('expenses.void');
        Route::put('/expenses/{expense}/payment', [BusinessExpenseController::class, 'updatePayment'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_POST)
            ->name('expenses.payment.update');
        Route::get('/expenses/{expense}/attachment', [BusinessExpenseController::class, 'attachment'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_VIEW)
            ->name('expenses.attachment');

        Route::get('/recurring-expenses', [RecurringExpenseController::class, 'index'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSES_VIEW)
            ->name('recurring-expenses.index');
        Route::get('/recurring-expenses/create', [RecurringExpenseController::class, 'create'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('recurring-expenses.create');
        Route::post('/recurring-expenses', [RecurringExpenseController::class, 'store'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('recurring-expenses.store');
        Route::get('/recurring-expenses/{recurringExpense}/edit', [RecurringExpenseController::class, 'edit'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('recurring-expenses.edit');
        Route::put('/recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'update'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('recurring-expenses.update');
        Route::delete('/recurring-expenses/{recurringExpense}', [RecurringExpenseController::class, 'destroy'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('recurring-expenses.destroy');
        Route::post('/recurring-expenses/generate-due', [RecurringExpenseController::class, 'generateDue'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::EXPENSE_SETTINGS_MANAGE)
            ->name('recurring-expenses.generate-due');

        Route::get('/salary-profiles', [SalaryProfileController::class, 'index'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_VIEW)
            ->name('salary-profiles.index');
        Route::get('/salary-profiles/create', [SalaryProfileController::class, 'create'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-profiles.create');
        Route::post('/salary-profiles', [SalaryProfileController::class, 'store'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-profiles.store');
        Route::get('/salary-profiles/{salaryProfile}/edit', [SalaryProfileController::class, 'edit'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-profiles.edit');
        Route::put('/salary-profiles/{salaryProfile}', [SalaryProfileController::class, 'update'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-profiles.update');
        Route::delete('/salary-profiles/{salaryProfile}', [SalaryProfileController::class, 'destroy'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-profiles.destroy');

        Route::get('/salary-entries', [SalaryEntryController::class, 'index'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_VIEW)
            ->name('salary-entries.index');
        Route::get('/salary-entries/create', [SalaryEntryController::class, 'create'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-entries.create');
        Route::post('/salary-entries', [SalaryEntryController::class, 'store'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-entries.store');
        Route::get('/salary-entries/{salaryEntry}', [SalaryEntryController::class, 'show'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_VIEW)
            ->name('salary-entries.show');
        Route::get('/salary-entries/{salaryEntry}/edit', [SalaryEntryController::class, 'edit'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-entries.edit');
        Route::put('/salary-entries/{salaryEntry}', [SalaryEntryController::class, 'update'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-entries.update');
        Route::delete('/salary-entries/{salaryEntry}', [SalaryEntryController::class, 'destroy'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-entries.destroy');
        Route::post('/salary-entries/generate-month', [SalaryEntryController::class, 'generateMonth'])
            ->middleware(EnsureFinanceCapability::class.':'.FinanceAccess::SALARY_MANAGE)
            ->name('salary-entries.generate-month');
    });

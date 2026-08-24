<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BusinessExpense;
use App\Models\ExpenseCategory;
use App\Models\RecurringExpenseTemplate;
use App\Services\Finance\RecurringExpenseGenerator;
use App\Support\FinanceAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecurringExpenseController extends Controller
{
    public function index(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_VIEW);

        $templates = RecurringExpenseTemplate::query()
            ->with('category')
            ->withCount('generatedExpenses')
            ->orderByDesc('is_active')
            ->orderBy('next_due_date')
            ->paginate(25);

        return view('admin.finance.recurring-expenses.index', [
            'templates' => $templates,
            'canManageSettings' => FinanceAccess::allows($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE),
        ]);
    }

    public function create(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        return view('admin.finance.recurring-expenses.form', [
            'template' => new RecurringExpenseTemplate([
                'frequency' => RecurringExpenseTemplate::FREQUENCY_MONTHLY,
                'start_date' => today(),
                'next_due_date' => today(),
                'is_active' => true,
            ]),
            'categories' => $this->activeCategories(),
            'frequencies' => RecurringExpenseTemplate::frequencies(),
            'paymentMethods' => BusinessExpense::paymentMethods(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        $validated = $this->validateTemplate($request);
        $amounts = $this->amounts($validated);

        RecurringExpenseTemplate::query()->create(array_merge($validated, $amounts, [
            'is_active' => $request->boolean('is_active', true),
            'created_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('admin.finance.recurring-expenses.index')
            ->with('status', 'Recurring template created. Due occurrences will be generated as draft expenses for review.');
    }

    public function edit(Request $request, RecurringExpenseTemplate $recurringExpense): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        return view('admin.finance.recurring-expenses.form', [
            'template' => $recurringExpense,
            'categories' => $this->activeCategories($recurringExpense->expense_category_id),
            'frequencies' => RecurringExpenseTemplate::frequencies(),
            'paymentMethods' => BusinessExpense::paymentMethods(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, RecurringExpenseTemplate $recurringExpense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        $validated = $this->validateTemplate($request);
        $amounts = $this->amounts($validated);

        $recurringExpense->update(array_merge($validated, $amounts, [
            'is_active' => $request->boolean('is_active'),
            'updated_by_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('admin.finance.recurring-expenses.index')
            ->with('status', 'Recurring template updated. Existing generated expenses were not changed.');
    }

    public function destroy(Request $request, RecurringExpenseTemplate $recurringExpense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        if ($recurringExpense->generatedExpenses()->exists()) {
            $recurringExpense->update([
                'is_active' => false,
                'updated_by_id' => $request->user()?->id,
            ]);

            return back()->with('status', 'The template already has generated expenses and was made inactive instead of being deleted.');
        }

        $recurringExpense->delete();

        return back()->with('status', 'Recurring template deleted.');
    }

    public function generateDue(Request $request, RecurringExpenseGenerator $generator): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        $validated = $request->validate([
            'through_date' => ['nullable', 'date'],
        ]);

        $result = $generator->generateDue(
            $validated['through_date'] ?? today(),
            $request->user()?->id,
        );

        $message = "Generated {$result['created']} draft expense(s); {$result['skipped']} already existed or were skipped.";

        if ($result['errors'] !== []) {
            return back()
                ->with('status', $message)
                ->withErrors(['generation' => implode(' | ', $result['errors'])]);
        }

        return back()->with('status', $message);
    }

    private function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'expense_category_id' => [
                'required',
                Rule::exists('expense_categories', 'id')->where(function ($query): void {
                    $query->whereNull('deleted_at')
                        ->where('is_active', true)
                        ->where('slug', '!=', ExpenseCategory::STAFF_SALARIES_SLUG);
                }),
            ],
            'description' => ['required', 'string', 'max:255'],
            'payee' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', Rule::in(array_keys(RecurringExpenseTemplate::frequencies()))],
            'expected_taxable_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'expected_gst_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'next_due_date' => ['required', 'date', 'after_or_equal:start_date'],
            'default_payment_method' => ['nullable', Rule::in(array_keys(BusinessExpense::paymentMethods()))],
            'notes' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['end_date'])
            && strtotime((string) $validated['next_due_date']) > strtotime((string) $validated['end_date'])) {
            throw ValidationException::withMessages([
                'next_due_date' => 'Next due date must be on or before the template end date.',
            ]);
        }

        return $validated;
    }

    private function amounts(array $validated): array
    {
        $taxable = round((float) $validated['expected_taxable_amount'], 2);
        $gst = round((float) $validated['expected_gst_amount'], 2);
        $total = round($taxable + $gst, 2);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'expected_taxable_amount' => 'Expected taxable amount plus GST must be greater than zero.',
            ]);
        }

        return [
            'expected_taxable_amount' => number_format($taxable, 2, '.', ''),
            'expected_gst_amount' => number_format($gst, 2, '.', ''),
            'expected_total_amount' => number_format($total, 2, '.', ''),
        ];
    }

    private function activeCategories(?int $selectedCategoryId = null)
    {
        return ExpenseCategory::query()
            ->where(function (Builder $query) use ($selectedCategoryId): void {
                $query->where(function (Builder $query): void {
                    $query->where('is_active', true)
                        ->where('slug', '!=', ExpenseCategory::STAFF_SALARIES_SLUG);
                });

                if ($selectedCategoryId !== null) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $selectedCategoryId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

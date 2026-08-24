<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BusinessExpense;
use App\Models\ExpenseCategory;
use App\Services\Finance\ExpenseNumberService;
use App\Support\FinanceAccess;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BusinessExpenseController extends Controller
{
    public function index(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_VIEW);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'category_id' => ['nullable', 'integer'],
            'record_status' => ['nullable', Rule::in(array_keys(BusinessExpense::recordStatuses()))],
            'payment_status' => ['nullable', Rule::in(array_keys(BusinessExpense::paymentStatuses()))],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'])
            : CarbonImmutable::today()->startOfMonth();

        $expenses = BusinessExpense::query()
            ->with(['category', 'createdBy', 'postedBy'])
            ->whereBetween('expense_date', [
                $month->startOfMonth()->toDateString(),
                $month->endOfMonth()->toDateString(),
            ])
            ->when($validated['category_id'] ?? null, fn (Builder $query, mixed $categoryId) => $query->where('expense_category_id', $categoryId))
            ->when($validated['record_status'] ?? null, fn (Builder $query, mixed $status) => $query->where('record_status', $status))
            ->when($validated['payment_status'] ?? null, fn (Builder $query, mixed $status) => $query->where('payment_status', $status))
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('payee', 'like', "%{$search}%")
                        ->orWhere('payment_reference', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $categories = ExpenseCategory::query()->orderBy('sort_order')->orderBy('name')->get();
        $canManage = FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_MANAGE);
        $canPost = FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_POST);

        return view('admin.finance.expenses.index', compact('expenses', 'categories', 'month', 'canManage', 'canPost'));
    }

    public function create(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_MANAGE);

        return view('admin.finance.expenses.form', [
            'expense' => new BusinessExpense([
                'expense_date' => today(),
                'payment_status' => BusinessExpense::PAYMENT_UNPAID,
                'record_status' => BusinessExpense::STATUS_DRAFT,
            ]),
            'categories' => $this->activeCategories(),
            'paymentMethods' => BusinessExpense::paymentMethods(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request, ExpenseNumberService $numbers): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_MANAGE);

        $validated = $this->validateExpense($request);
        unset($validated['receipt']);
        $validated = array_merge($validated, $this->normalisePayment($validated));
        $amounts = $this->amounts($validated);
        $receipt = $this->storeReceipt($request);

        try {
            $expense = DB::transaction(fn (): BusinessExpense => BusinessExpense::query()->create(array_merge(
                $validated,
                $amounts,
                $receipt,
                [
                    'expense_number' => $numbers->generate($validated['expense_date']),
                    'record_status' => BusinessExpense::STATUS_DRAFT,
                    'created_by_id' => $request->user()?->id,
                    'updated_by_id' => $request->user()?->id,
                ],
            )), 3);
        } catch (Throwable $exception) {
            $this->deleteStoredReceipt($receipt['receipt_path'] ?? null);

            throw $exception;
        }

        return redirect()
            ->route('admin.finance.expenses.show', $expense)
            ->with('status', 'Draft expense created. It must be reviewed and posted before it enters the operating summary.');
    }

    public function show(Request $request, BusinessExpense $expense): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_VIEW);

        $expense->load(['category', 'recurringTemplate', 'createdBy', 'updatedBy', 'postedBy']);

        return view('admin.finance.expenses.show', [
            'expense' => $expense,
            'canManage' => FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_MANAGE),
            'canPost' => FinanceAccess::allows($request->user(), FinanceAccess::EXPENSES_POST),
            'paymentMethods' => BusinessExpense::paymentMethods(),
        ]);
    }

    public function edit(Request $request, BusinessExpense $expense): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_MANAGE);
        abort_unless($expense->isDraft(), 422, 'Only draft expenses can be edited.');

        return view('admin.finance.expenses.form', [
            'expense' => $expense,
            'categories' => $this->activeCategories($expense->expense_category_id),
            'paymentMethods' => BusinessExpense::paymentMethods(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, BusinessExpense $expense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_MANAGE);
        abort_unless($expense->isDraft(), 422, 'Only draft expenses can be edited.');

        $validated = $this->validateExpense($request);
        unset($validated['receipt']);
        $validated = array_merge($validated, $this->normalisePayment($validated));
        $amounts = $this->amounts($validated);
        $newReceipt = $this->storeReceipt($request);
        $oldReceiptPath = $expense->receipt_path;

        try {
            DB::transaction(function () use ($expense, $validated, $amounts, $newReceipt, $request): void {
                $expense->update(array_merge($validated, $amounts, $newReceipt, [
                    'updated_by_id' => $request->user()?->id,
                ]));
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteStoredReceipt($newReceipt['receipt_path'] ?? null);

            throw $exception;
        }

        if ($newReceipt !== [] && $oldReceiptPath !== null && $oldReceiptPath !== $expense->receipt_path) {
            $this->deleteStoredReceipt($oldReceiptPath);
        }

        return redirect()
            ->route('admin.finance.expenses.show', $expense)
            ->with('status', 'Draft expense updated.');
    }

    public function destroy(Request $request, BusinessExpense $expense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_MANAGE);
        abort_unless($expense->isDraft(), 422, 'Posted expenses cannot be deleted. Void a posted expense instead.');

        $receiptPath = $expense->receipt_path;
        $expense->delete();

        if ($receiptPath !== null) {
            $this->deleteStoredReceipt($receiptPath);
        }

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('status', 'Draft expense deleted.');
    }

    public function post(Request $request, BusinessExpense $expense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_POST);
        abort_unless($expense->isDraft(), 422, 'Only draft expenses can be posted.');

        if ((float) $expense->total_amount <= 0) {
            throw ValidationException::withMessages([
                'total_amount' => 'The expense total must be greater than zero before posting.',
            ]);
        }

        if ($expense->payment_status === BusinessExpense::PAYMENT_PAID
            && ($expense->paid_date === null || blank($expense->payment_method))) {
            throw ValidationException::withMessages([
                'payment_status' => 'A paid expense needs both a paid date and payment method before posting.',
            ]);
        }

        $expense->update([
            'record_status' => BusinessExpense::STATUS_POSTED,
            'posted_at' => now(),
            'posted_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'Expense posted and included in the operating summary.');
    }

    public function void(Request $request, BusinessExpense $expense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_POST);
        abort_unless($expense->isPosted(), 422, 'Only a posted expense can be voided.');

        $expense->update([
            'record_status' => BusinessExpense::STATUS_VOID,
            'updated_by_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'Expense voided. Its record remains available for audit history.');
    }

    public function updatePayment(Request $request, BusinessExpense $expense): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_POST);
        abort_unless($expense->isPosted(), 422, 'Payment details can be updated only after an expense is posted.');

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(array_keys(BusinessExpense::paymentStatuses()))],
            'payment_method' => [
                Rule::requiredIf(fn (): bool => $request->input('payment_status') === BusinessExpense::PAYMENT_PAID),
                'nullable',
                Rule::in(array_keys(BusinessExpense::paymentMethods())),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_date' => [
                Rule::requiredIf(fn (): bool => $request->input('payment_status') === BusinessExpense::PAYMENT_PAID),
                'nullable',
                'date',
                'after_or_equal:'.$expense->expense_date->toDateString(),
            ],
        ]);

        if ($validated['payment_status'] === BusinessExpense::PAYMENT_UNPAID) {
            $validated['payment_method'] = null;
            $validated['payment_reference'] = null;
            $validated['paid_date'] = null;
        }

        $expense->update(array_merge($validated, [
            'updated_by_id' => $request->user()?->id,
        ]));

        return back()->with('status', 'Expense payment details updated.');
    }

    public function attachment(Request $request, BusinessExpense $expense): BinaryFileResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSES_VIEW);
        abort_if($expense->receipt_path === null, 404);
        abort_unless(Storage::disk('local')->exists($expense->receipt_path), 404);

        return response()->download(
            Storage::disk('local')->path($expense->receipt_path),
            $expense->receipt_original_name ?: basename($expense->receipt_path),
            ['Content-Type' => $expense->receipt_mime_type ?: 'application/octet-stream'],
        );
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
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
            'taxable_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'gst_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'payment_status' => ['required', Rule::in(array_keys(BusinessExpense::paymentStatuses()))],
            'payment_method' => [
                Rule::requiredIf(fn (): bool => $request->input('payment_status') === BusinessExpense::PAYMENT_PAID),
                'nullable',
                Rule::in(array_keys(BusinessExpense::paymentMethods())),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date', 'after_or_equal:expense_date'],
            'paid_date' => [
                Rule::requiredIf(fn (): bool => $request->input('payment_status') === BusinessExpense::PAYMENT_PAID),
                'nullable',
                'date',
                'after_or_equal:expense_date',
            ],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }

    private function amounts(array $validated): array
    {
        $taxable = round((float) $validated['taxable_amount'], 2);
        $gst = round((float) $validated['gst_amount'], 2);
        $total = round($taxable + $gst, 2);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'taxable_amount' => 'Taxable amount plus GST must be greater than zero.',
            ]);
        }

        return [
            'taxable_amount' => number_format($taxable, 2, '.', ''),
            'gst_amount' => number_format($gst, 2, '.', ''),
            'total_amount' => number_format($total, 2, '.', ''),
        ];
    }

    private function storeReceipt(Request $request): array
    {
        if (! $request->hasFile('receipt')) {
            return [];
        }

        $file = $request->file('receipt');
        $path = $file->store('finance/expense-receipts', 'local');

        return [
            'receipt_path' => $path,
            'receipt_original_name' => basename($file->getClientOriginalName()),
            'receipt_mime_type' => $file->getMimeType(),
            'receipt_size' => $file->getSize(),
        ];
    }

    private function normalisePayment(array $validated): array
    {
        if (($validated['payment_status'] ?? null) !== BusinessExpense::PAYMENT_PAID) {
            return [
                // A draft may retain an intended/default payment method from a
                // recurring template. Actual payment evidence is always cleared
                // until the expense is marked paid.
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_reference' => null,
                'paid_date' => null,
            ];
        }

        return [
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'paid_date' => $validated['paid_date'] ?? null,
        ];
    }

    private function deleteStoredReceipt(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('local')->delete($path);
        }
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
                    $query->orWhereKey($selectedCategoryId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}

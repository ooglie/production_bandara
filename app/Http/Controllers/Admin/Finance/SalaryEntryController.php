<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\SalaryEntry;
use App\Models\User;
use App\Services\Finance\SalaryEntryGenerator;
use App\Services\Finance\SalaryProfileService;
use App\Support\FinanceAccess;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalaryEntryController extends Controller
{
    public function index(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_VIEW);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'payment_status' => ['nullable', Rule::in(array_keys(SalaryEntry::paymentStatuses()))],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $month = isset($validated['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $validated['month'])
            : CarbonImmutable::today()->startOfMonth();

        $entries = SalaryEntry::query()
            ->with(['staffMember', 'salaryProfile'])
            ->whereDate('salary_month', $month->toDateString())
            ->when($validated['payment_status'] ?? null, fn (Builder $query, mixed $status) => $query->where('payment_status', $status))
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('staff_name', 'like', "%{$search}%")
                        ->orWhere('payment_reference', 'like', "%{$search}%")
                        ->orWhereHas('staffMember', fn (Builder $query) => $query->where('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy('staff_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.salary-entries.index', [
            'entries' => $entries,
            'month' => $month,
            'canManage' => FinanceAccess::allows($request->user(), FinanceAccess::SALARY_MANAGE),
        ]);
    }

    public function create(Request $request, SalaryProfileService $profiles): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        $monthInput = (string) $request->input('month', '');
        $month = preg_match('/^\d{4}-\d{2}$/', $monthInput) === 1
            ? CarbonImmutable::createFromFormat('!Y-m', $monthInput)
            : CarbonImmutable::today()->startOfMonth();
        $selectedUserId = $request->integer('user_id') ?: null;
        $profile = $selectedUserId !== null ? $profiles->profileForMonth($selectedUserId, $month) : null;

        return view('admin.finance.salary-entries.form', [
            'entry' => new SalaryEntry([
                'user_id' => $selectedUserId,
                'salary_month' => $month,
                'basic_salary' => $profile?->monthly_salary ?? 0,
                'additions' => 0,
                'deductions' => 0,
                'payment_status' => SalaryEntry::STATUS_PENDING,
            ]),
            'staffMembers' => $this->staffMembers(),
            'paymentStatuses' => SalaryEntry::editablePaymentStatuses(),
            'paymentMethods' => SalaryEntry::paymentMethods(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request, SalaryProfileService $profiles): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        $validated = $this->validateNewEntry($request);
        $month = CarbonImmutable::createFromFormat('!Y-m', $validated['salary_month'])->startOfMonth();

        if (SalaryEntry::query()
            ->where('user_id', $validated['user_id'])
            ->whereDate('salary_month', $month->toDateString())
            ->exists()) {
            throw ValidationException::withMessages([
                'salary_month' => 'A salary record already exists for this staff member and month.',
            ]);
        }

        $profile = $profiles->profileForMonth((int) $validated['user_id'], $month);
        $staff = User::query()->findOrFail($validated['user_id']);
        $amounts = $this->salaryAmounts($validated);
        $payment = $this->normalisePayment($validated);

        $entry = SalaryEntry::query()->create(array_merge($validated, $amounts, $payment, [
            'salary_month' => $month->toDateString(),
            'salary_profile_id' => $profile?->id,
            'staff_name' => $staff->name,
            'created_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('admin.finance.salary-entries.show', $entry)
            ->with('status', 'Monthly salary record created.');
    }

    public function show(Request $request, SalaryEntry $salaryEntry): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_VIEW);

        $salaryEntry->load(['staffMember', 'salaryProfile', 'createdBy', 'updatedBy']);

        return view('admin.finance.salary-entries.show', [
            'entry' => $salaryEntry,
            'canManage' => FinanceAccess::allows($request->user(), FinanceAccess::SALARY_MANAGE),
        ]);
    }

    public function edit(Request $request, SalaryEntry $salaryEntry): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);
        abort_if($salaryEntry->isLockedForEditing(), 422, 'Paid and cancelled salary records are locked for audit history.');

        return view('admin.finance.salary-entries.form', [
            'entry' => $salaryEntry,
            'staffMembers' => collect([$salaryEntry->staffMember])->filter(),
            'paymentStatuses' => SalaryEntry::editablePaymentStatuses(),
            'paymentMethods' => SalaryEntry::paymentMethods(),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, SalaryEntry $salaryEntry): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);
        abort_if($salaryEntry->isLockedForEditing(), 422, 'Paid and cancelled salary records are locked for audit history.');

        $validated = $this->validateExistingEntry($request);
        $amounts = $this->salaryAmounts($validated);
        $payment = $this->normalisePayment($validated);

        $salaryEntry->update(array_merge($validated, $amounts, $payment, [
            'updated_by_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('admin.finance.salary-entries.show', $salaryEntry)
            ->with('status', 'Salary record updated.');
    }

    public function destroy(Request $request, SalaryEntry $salaryEntry): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        abort_if($salaryEntry->payment_status === SalaryEntry::STATUS_PAID, 422, 'A paid salary record cannot be cancelled.');
        abort_if($salaryEntry->payment_status === SalaryEntry::STATUS_CANCELLED, 422, 'This salary record is already cancelled.');

        $salaryEntry->update([
            'payment_status' => SalaryEntry::STATUS_CANCELLED,
            'payment_date' => null,
            'payment_method' => null,
            'payment_reference' => null,
            'updated_by_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'Salary record cancelled. It remains available for audit history.');
    }

    public function generateMonth(Request $request, SalaryEntryGenerator $generator): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $result = $generator->generateForMonth($validated['month'], $request->user()?->id);
        $message = "Generated {$result['created']} salary record(s); {$result['skipped']} already existed or were skipped.";

        if ($result['errors'] !== []) {
            return back()
                ->with('status', $message)
                ->withErrors(['generation' => implode(' | ', $result['errors'])]);
        }

        return redirect()
            ->route('admin.finance.salary-entries.index', ['month' => $validated['month']])
            ->with('status', $message);
    }

    private function validateNewEntry(Request $request): array
    {
        return array_merge($request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('customer_type', 'staff')),
            ],
            'salary_month' => ['required', 'date_format:Y-m'],
        ]), $this->validateAmountsAndPayment($request));
    }

    private function validateExistingEntry(Request $request): array
    {
        return $this->validateAmountsAndPayment($request);
    }

    private function validateAmountsAndPayment(Request $request): array
    {
        return $request->validate([
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'additions' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'deductions' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'payment_status' => ['required', Rule::in(array_keys(SalaryEntry::editablePaymentStatuses()))],
            'payment_date' => [
                Rule::requiredIf(fn (): bool => $request->input('payment_status') === SalaryEntry::STATUS_PAID),
                'nullable',
                'date',
            ],
            'payment_method' => [
                Rule::requiredIf(fn (): bool => $request->input('payment_status') === SalaryEntry::STATUS_PAID),
                'nullable',
                Rule::in(array_keys(SalaryEntry::paymentMethods())),
            ],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }

    private function salaryAmounts(array $validated): array
    {
        $basic = round((float) $validated['basic_salary'], 2);
        $additions = round((float) $validated['additions'], 2);
        $deductions = round((float) $validated['deductions'], 2);

        if ($deductions > $basic + $additions) {
            throw ValidationException::withMessages([
                'deductions' => 'Deductions cannot be greater than basic salary plus additions.',
            ]);
        }

        return [
            'basic_salary' => number_format($basic, 2, '.', ''),
            'additions' => number_format($additions, 2, '.', ''),
            'deductions' => number_format($deductions, 2, '.', ''),
            'net_payable' => SalaryEntry::calculateNet($basic, $additions, $deductions),
        ];
    }

    private function normalisePayment(array $validated): array
    {
        if ($validated['payment_status'] !== SalaryEntry::STATUS_PAID) {
            return [
                'payment_date' => null,
                'payment_method' => null,
                'payment_reference' => null,
            ];
        }

        return [
            'payment_date' => $validated['payment_date'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
        ];
    }

    private function staffMembers()
    {
        return User::query()
            ->where('customer_type', 'staff')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active']);
    }
}

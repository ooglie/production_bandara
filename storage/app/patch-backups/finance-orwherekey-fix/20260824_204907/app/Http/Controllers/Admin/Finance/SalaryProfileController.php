<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\SalaryProfile;
use App\Models\User;
use App\Services\Finance\SalaryProfileService;
use App\Support\FinanceAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalaryProfileController extends Controller
{
    public function index(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_VIEW);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', Rule::in(['yes', 'no'])],
        ]);

        $profiles = SalaryProfile::query()
            ->with('staffMember')
            ->withCount('salaryEntries')
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('staffMember', function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(($validated['active'] ?? null) === 'yes', fn (Builder $query) => $query->where('is_active', true))
            ->when(($validated['active'] ?? null) === 'no', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('user_id')
            ->orderByDesc('effective_from')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.salary-profiles.index', [
            'profiles' => $profiles,
            'canManage' => FinanceAccess::allows($request->user(), FinanceAccess::SALARY_MANAGE),
        ]);
    }

    public function create(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        return view('admin.finance.salary-profiles.form', [
            'profile' => new SalaryProfile([
                'effective_from' => today()->startOfMonth(),
                'payment_day' => 7,
                'is_active' => true,
            ]),
            'staffMembers' => $this->staffMembers(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request, SalaryProfileService $profiles): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        $validated = $this->validateProfile($request);
        $this->ensureNoOverlap($profiles, $validated);

        SalaryProfile::query()->create(array_merge($validated, [
            'is_active' => $request->boolean('is_active', true),
            'created_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('admin.finance.salary-profiles.index')
            ->with('status', 'Salary profile created. Historical profiles remain unchanged.');
    }

    public function edit(Request $request, SalaryProfile $salaryProfile): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        return view('admin.finance.salary-profiles.form', [
            'profile' => $salaryProfile,
            'staffMembers' => $this->staffMembers($salaryProfile->user_id),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, SalaryProfile $salaryProfile, SalaryProfileService $profiles): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        $validated = $this->validateProfile($request);
        $this->ensureNoOverlap($profiles, $validated, $salaryProfile->id);

        $salaryProfile->update(array_merge($validated, [
            'is_active' => $request->boolean('is_active'),
            'updated_by_id' => $request->user()?->id,
        ]));

        return redirect()
            ->route('admin.finance.salary-profiles.index')
            ->with('status', 'Salary profile updated. Existing monthly salary snapshots were not changed.');
    }

    public function destroy(Request $request, SalaryProfile $salaryProfile): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::SALARY_MANAGE);

        if ($salaryProfile->salaryEntries()->exists()) {
            $salaryProfile->update([
                'is_active' => false,
                'updated_by_id' => $request->user()?->id,
            ]);

            return back()->with('status', 'This profile has salary history and was made inactive instead of being deleted.');
        }

        $salaryProfile->delete();

        return back()->with('status', 'Unused salary profile deleted.');
    }

    private function validateProfile(Request $request): array
    {
        return $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('customer_type', 'staff')),
            ],
            'monthly_salary' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'payment_day' => ['required', 'integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function ensureNoOverlap(SalaryProfileService $profiles, array $validated, ?int $ignoreId = null): void
    {
        if ($profiles->overlaps(
            (int) $validated['user_id'],
            $validated['effective_from'],
            $validated['effective_to'] ?? null,
            $ignoreId,
        )) {
            throw ValidationException::withMessages([
                'effective_from' => 'This effective period overlaps another salary profile for the same staff member. Close or adjust the earlier profile first.',
            ]);
        }
    }

    private function staffMembers(?int $selectedUserId = null)
    {
        return User::query()
            ->where('customer_type', 'staff')
            ->where(function (Builder $query) use ($selectedUserId): void {
                $query->where('is_active', true);

                if ($selectedUserId !== null) {
                    $query->orWhereKey($selectedUserId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active']);
    }
}

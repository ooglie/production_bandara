<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\B2BApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\B2BApplication;
use App\Models\User;
use App\Services\B2B\B2BApplicationApprovalService;
use App\Services\B2B\B2BApplicationWorkflowService;
use App\Services\B2B\B2BLocationService;
use App\Support\B2BApplicationAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class B2BApplicationController extends Controller
{
    public function __construct(
        private readonly B2BApplicationWorkflowService $workflow,
        private readonly B2BApplicationApprovalService $approval,
        private readonly B2BLocationService $locations,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request, 'view');
        $query = B2BApplication::query()->with(['user', 'assignee']);

        $request->whenFilled('status', fn (string $status) => $query->where('status', $status));
        $request->whenFilled('business_type', fn (string $type) => $query->where('business_type', $type));
        $request->whenFilled('state_id', fn (string $stateId) => $query->where('state_id', $stateId));
        $request->whenFilled('assigned_to', function (string $assigned) use ($query): void {
            $assigned === 'unassigned' ? $query->whereNull('assigned_to') : $query->where('assigned_to', $assigned);
        });
        $request->whenFilled('search', function (string $search) use ($query): void {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';
            $query->where(function (Builder $subQuery) use ($needle): void {
                $subQuery->where('application_number', 'like', $needle)
                    ->orWhere('legal_business_name', 'like', $needle)
                    ->orWhere('trading_name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('gstin', 'like', $needle);
            });
        });

        $applications = $query
            ->orderByRaw("CASE status WHEN 'submitted' THEN 1 WHEN 'under_review' THEN 2 WHEN 'more_information_required' THEN 3 WHEN 'draft' THEN 4 ELSE 5 END")
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = B2BApplication::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $staff = $this->staffUsers();
        $states = $this->locations->states();

        return view('admin.b2b-applications.index', compact('applications', 'counts', 'staff', 'states'));
    }

    public function show(Request $request, B2BApplication $b2bApplication): View
    {
        $this->authorizeAdmin($request, 'view');
        $b2bApplication->load(['user', 'assignee', 'reviewer', 'approver', 'accountManager', 'histories.actor', 'profile']);
        $staff = $this->staffUsers();

        return view('admin.b2b-applications.show', compact('b2bApplication', 'staff'));
    }

    public function assign(Request $request, B2BApplication $b2bApplication): RedirectResponse
    {
        $this->authorizeAdmin($request, 'review');
        $validated = $request->validate(['assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')]]);
        $this->workflow->assign($b2bApplication, $request->user(), isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null);

        return back()->with('success', 'Application assignment updated.');
    }

    public function startReview(Request $request, B2BApplication $b2bApplication): RedirectResponse
    {
        $this->authorizeAdmin($request, 'review');
        $this->workflow->startReview($b2bApplication, $request->user());

        return back()->with('success', 'Application moved under review.');
    }

    public function requestInformation(Request $request, B2BApplication $b2bApplication): RedirectResponse
    {
        $this->authorizeAdmin($request, 'review');
        $validated = $request->validate(['customer_message' => ['required', 'string', 'min:10', 'max:3000']]);
        $this->workflow->requestInformation($b2bApplication, $request->user(), $validated['customer_message']);

        return back()->with('success', 'The customer has been asked for additional information.');
    }

    public function reject(Request $request, B2BApplication $b2bApplication): RedirectResponse
    {
        $this->authorizeAdmin($request, 'approve');
        $validated = $request->validate(['customer_message' => ['required', 'string', 'min:10', 'max:3000']]);
        $this->workflow->reject($b2bApplication, $request->user(), $validated['customer_message']);

        return back()->with('success', 'Application status updated.');
    }

    public function note(Request $request, B2BApplication $b2bApplication): RedirectResponse
    {
        $this->authorizeAdmin($request, 'review');
        $validated = $request->validate(['note' => ['required', 'string', 'min:2', 'max:5000']]);
        $this->workflow->addInternalNote($b2bApplication, $request->user(), $validated['note']);

        return back()->with('success', 'Internal note added.');
    }

    public function approve(Request $request, B2BApplication $b2bApplication): RedirectResponse
    {
        $this->authorizeAdmin($request, 'approve');
        $request->merge(['pay_later_enabled' => $request->boolean('pay_later_enabled')]);
        $validated = $request->validate([
            'approved_price_group_id' => ['nullable', 'integer', 'min:1'],
            'pay_later_enabled' => ['required', 'boolean'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'delivery_arrangement' => ['nullable', 'string', 'max:3000'],
            'approved_account_manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'customer_message' => ['nullable', 'string', 'max:3000'],
        ]);
        $this->approval->approve($b2bApplication, $request->user(), $validated);

        return redirect()->route('admin.b2b-applications.show', $b2bApplication)->with('success', 'Business account approved successfully.');
    }

    private function authorizeAdmin(Request $request, string $ability): void
    {
        abort_unless($request->user() && B2BApplicationAccess::adminCan($request->user(), $ability), 403);
    }

    private function staffUsers()
    {
        $roles = (array) config('b2b_application.admin_roles', ['Admin', 'Manager']);
        $query = User::query()->orderBy('name')->orderBy('email');

        try {
            if (method_exists($query->getModel(), 'scopeRole')) {
                $query->role($roles);
            } elseif (method_exists($query->getModel(), 'roles')) {
                $query->whereHas('roles', static fn ($roleQuery) => $roleQuery->whereIn('name', $roles));
            } else {
                return collect();
            }
        } catch (Throwable) {
            return collect();
        }

        return $query->get(['id', 'name', 'email']);
    }
}

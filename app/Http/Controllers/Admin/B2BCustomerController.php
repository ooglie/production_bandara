<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\B2BCustomerTerm;
use App\Services\B2BPayLaterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class B2BCustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) $request->get('q', '');

        $query = User::query()
            ->when(Schema::hasTable('b2b_customer_terms'), fn ($q) => $q->with('b2bTerms'))
            ->where('customer_type', 'b2b')
            ->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('admin.b2b.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.b2b.customers.create', [
            'terms' => new B2BCustomerTerm([
                'pay_later_enabled' => false,
                'credit_limit' => 0,
                'payment_terms_days' => 7,
                'credit_status' => 'active',
            ]),
        ]);
    }

    public function store(Request $request, B2BPayLaterService $payLaterService)
    {
        $data = $request->validate($this->validationRules());

        $u = new User();
        $u->name = $data['name'];
        $u->email = $data['email'];
        $u->phone = $data['phone'];
        $u->gst_number = $data['gst_number'] ?? null;
        $u->fssai_number = $data['fssai_number'] ?? null;
        $u->password = Hash::make($data['password']);
        $u->is_active = $request->boolean('is_active', true);
        $u->customer_type = 'b2b';

        if ($request->boolean('mark_email_verified')) {
            $u->email_verified_at = now();
        }

        $u->save();

        if (method_exists($u, 'syncRoles')) {
            $u->syncRoles(['Customer']);
        }

        $payLaterService->saveTerms($u, $this->termsPayload($request, $data), $request->user());

        return redirect()
            ->route('admin.b2b.customers.index')
            ->with('status', 'B2B customer created.');
    }

    public function edit(User $user, B2BPayLaterService $payLaterService)
    {
        abort_unless(($user->customer_type ?? null) === 'b2b', 404);

        $user->loadMissing('b2bTerms');

        return view('admin.b2b.customers.edit', [
            'user' => $user,
            'terms' => $user->b2bTerms ?? new B2BCustomerTerm([
                'pay_later_enabled' => false,
                'credit_limit' => 0,
                'payment_terms_days' => 7,
                'credit_status' => 'active',
            ]),
            'payLaterSummary' => $payLaterService->summaryFor($user),
        ]);
    }

    public function update(Request $request, User $user, B2BPayLaterService $payLaterService)
    {
        abort_unless(($user->customer_type ?? null) === 'b2b', 404);

        $data = $request->validate($this->validationRules($user));

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        $user->gst_number = $data['gst_number'] ?? null;
        $user->fssai_number = $data['fssai_number'] ?? null;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->boolean('mark_email_verified') && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->is_active = $request->boolean('is_active', $user->is_active);
        $user->customer_type = 'b2b';
        $user->save();

        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles(['Customer']);
        }

        $payLaterService->saveTerms($user, $this->termsPayload($request, $data), $request->user());

        return redirect()
            ->route('admin.b2b.customers.index')
            ->with('status', 'B2B customer updated.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless(($user->customer_type ?? null) === 'b2b', 404);

        if ($request->user()->id === $user->id) {
            return back()->with('status', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('status', 'B2B customer deleted.');
    }

    protected function validationRules(?User $user = null): array
    {
        $userId = $user?->id;

        return [
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email' . ($userId ? ',' . $userId : '')],
            'phone'               => ['required', 'string', 'max:20'],
            'gst_number'          => ['nullable', 'string', 'max:50'],
            'fssai_number'        => ['nullable', 'string', 'max:50'],
            'password'            => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'mark_email_verified' => ['nullable', 'boolean'],
            'is_active'           => ['nullable', 'boolean'],
            'pay_later_enabled'   => ['nullable', 'boolean'],
            'credit_limit'        => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_terms_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'credit_status'       => ['nullable', 'in:active,on_hold,blocked'],
            'credit_notes'        => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function termsPayload(Request $request, array $data): array
    {
        return [
            'pay_later_enabled' => $request->boolean('pay_later_enabled'),
            'credit_limit' => (float) ($data['credit_limit'] ?? 0),
            'payment_terms_days' => (int) ($data['payment_terms_days'] ?? 7),
            'credit_status' => $data['credit_status'] ?? 'active',
            'notes' => $data['credit_notes'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List users with search + role filter.
     */
    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->get();

        $query = User::query()->with('roles');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleName = $request->get('role')) {
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        if ($ct = $request->get('customer_type')) {
            $query->where('customer_type', $ct);
        }


        $users = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show create form.
     * Optional: /admin/users/create?customer_type=b2b
     */
    public function create(Request $request)
    {
        $roles = Role::orderBy('name')->get();

        $customerType = $request->get('customer_type', 'b2c');
        if (!in_array($customerType, ['b2b', 'b2c', 'staff'], true)) {
            $customerType = 'b2c';
        }

        return view('admin.users.create', compact('roles', 'customerType'));
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'               => ['required', 'string', 'max:20'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
            'roles'               => ['nullable', 'array'],
            'roles.*'             => ['string', 'exists:roles,name'],
            'customer_type'       => ['nullable', 'in:b2b,b2c,staff'],
            'gst_number'    => ['nullable', 'string', 'max:50'],
            'fssai_number'  => ['nullable', 'string', 'max:50'],
            'mark_email_verified' => ['nullable', 'boolean'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $roleNames = $this->normalizeRoleNames($data['roles'] ?? []);
        $requestedType = $data['customer_type'] ?? null;

        // Compute final customer_type
        $finalType = $this->resolveCustomerType($roleNames, $requestedType);

        // If customer account, ensure Customer role exists in roles to avoid config mistakes
        $roleNames = $this->ensureCustomerRoleForCustomerTypes($roleNames, $finalType);

        $user = new User();
        $user->name        = $data['name'];
        $user->email       = $data['email'];
        $user->phone       = $data['phone'];
        
        // $user->gst_number = $data['gst_number'];
        // $user->fssai_number = $data['fssai_number'];

        $user->password    = Hash::make($data['password']);
        $user->is_active   = $request->boolean('is_active', true);
        $user->customer_type = $finalType;
        $user->gst_number = $data['gst_number'] ?? null;
        $user->fssai_number = $data['fssai_number'] ?? null;

        if ($request->boolean('mark_email_verified')) {
            $user->email_verified_at = now();
        }

        $user->save();

        $user->syncRoles($roleNames);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    /**
     * Show edit form.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoleNames = $user->roles->pluck('name')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'userRoleNames'));
    }

    /**
     * Update a user (including roles).
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'               => ['required', 'string', 'max:20'],
            'password'            => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles'               => ['nullable', 'array'],
            'roles.*'             => ['string', 'exists:roles,name'],
            'customer_type'       => ['nullable', 'in:b2b,b2c,staff'],
            'gst_number'    => ['nullable', 'string', 'max:50'],
            'fssai_number'  => ['nullable', 'string', 'max:50'],
            'mark_email_verified' => ['nullable', 'boolean'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $roleNames = $this->normalizeRoleNames($data['roles'] ?? []);
        $requestedType = $data['customer_type'] ?? null;

        // prevent removing own Admin role
        if (
            $request->user()->id === $user->id &&
            !in_array('Admin', $roleNames, true)
        ) {
            return back()
                ->withErrors(['roles' => 'You cannot remove your own Admin role.'])
                ->withInput();
        }

        // prevent deactivating self
        if (
            $request->user()->id === $user->id &&
            !$request->boolean('is_active', $user->is_active)
        ) {
            return back()
                ->withErrors(['is_active' => 'You cannot deactivate your own account.'])
                ->withInput();
        }

        // assign fields
        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];
        // $user->gst_number = $data['gst_number'];
        // $user->fssai_number = $data['fssai_number'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->boolean('mark_email_verified')) {
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
        }

        $user->is_active = $request->boolean('is_active', $user->is_active);

        // Compute final customer_type from roles + requested dropdown value
        $finalType = $this->resolveCustomerType($roleNames, $requestedType);

        // Ensure Customer role if b2b/b2c
        $roleNames = $this->ensureCustomerRoleForCustomerTypes($roleNames, $finalType);

        $user->customer_type = $finalType;

        $user->save();

        $user->syncRoles($roleNames);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('status', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    public function impersonate(Request $request, User $user)
    {
        $admin = $request->user();

        if (!$admin->hasRole('Admin')) {
            abort(403);
        }

        if ($admin->id === $user->id) {
            return back()->with('status', 'You cannot impersonate yourself.');
        }

        // Clear any previous impersonation
        $request->session()->forget('impersonator_id');

        $log = ImpersonationLog::create([
            'admin_id'             => $admin->id,
            'impersonated_user_id' => $user->id,
            'type'                 => 'impersonation',
            'started_at'           => now(),
            'ip_address'           => $request->ip(),
            'user_agent'           => substr((string) $request->userAgent(), 0, 1000),
        ]);

        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonation_log_id', $log->id);

        Auth::login($user);

        return redirect()
            ->route('home')
            ->with('status', 'You are now impersonating ' . $user->name . '.');
    }

    /**
     * Stop impersonating and return to the original admin account.
     */
    public function stopImpersonating(Request $request)
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        $logId          = $request->session()->pull('impersonation_log_id');

        if ($logId) {
            $log = ImpersonationLog::find($logId);
            if ($log && !$log->ended_at) {
                $log->ended_at     = now();
                $log->ended_reason = 'manual_stop';
                $log->save();
            }
        }

        if (!$impersonatorId) {
            return redirect()
                ->route('home')
                ->with('status', 'You are not impersonating anyone.');
        }

        $admin = User::find($impersonatorId);

        if ($admin) {
            Auth::login($admin);

            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Impersonation ended. You are back as ' . $admin->name . '.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Original admin account not found. Please login again.');
    }

    /**
     * Decide customer_type based on roles + requested type.
     *
     * Rules:
     * - If any staff role is assigned => staff
     * - Else if requestedType is valid => requestedType
     * - Else if Customer role present => b2c
     * - Else => staff (safe default for non-customer accounts)
     */
    private function resolveCustomerType(array $roleNames, ?string $requestedType): string
    {
        $staffRoles = ['Admin', 'Manager', 'Support', 'Accountant', 'CAAccountant', 'Stores'];

        if (count(array_intersect($roleNames, $staffRoles)) > 0) {
            return 'staff';
        }

        if ($requestedType && in_array($requestedType, ['b2b', 'b2c', 'staff'], true)) {
            return $requestedType;
        }

        if (in_array('Customer', $roleNames, true)) {
            return 'b2c';
        }

        return 'staff';
    }

    /**
     * If final type is b2b/b2c, ensure Customer role is included.
     * (Prevents a “customer” account without Customer role.)
     */
    private function ensureCustomerRoleForCustomerTypes(array $roleNames, string $customerType): array
    {
        if (!in_array($customerType, ['b2b', 'b2c'], true)) {
            return $roleNames;
        }

        // Only add if role exists to avoid Spatie throwing
        $customerRoleExists = Role::where('name', 'Customer')->exists();
        if ($customerRoleExists && !in_array('Customer', $roleNames, true)) {
            $roleNames[] = 'Customer';
        }

        return array_values(array_unique($roleNames));
    }

    /**
     * Normalize roles list.
     */
    private function normalizeRoleNames(array $roleNames): array
    {
        $roleNames = array_filter(array_map('strval', $roleNames));
        $roleNames = array_values(array_unique($roleNames));
        return $roleNames;
    }
}

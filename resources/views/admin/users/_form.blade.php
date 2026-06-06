@php
    /**
     * Shared form for Admin Users (create/edit)
     *
     * Required:
     * - $action (string)
     * - $mode ('create'|'edit')
     *
     * Optional:
     * - $user (\App\Models\User|null) for edit
     * - $roles (iterable) list of role models with ->name
     * - $customerType (string|null) default for create
     * - $userRoleNames (array|null) for edit
     * - $backUrl (string|null)
     */

    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';

    $user = $user ?? null;
    $roles = $roles ?? collect();
    $userRoleNames = $userRoleNames ?? [];

    $backUrl = $backUrl
        ?? (\Illuminate\Support\Facades\Route::has('admin.users.index')
            ? route('admin.users.index')
            : url()->previous());

    // Default customer type:
    // - create: controller provided $customerType else b2c
    // - edit: user value else b2c
    $prefCustomerType = $isEdit
        ? old('customer_type', $user->customer_type ?? 'b2c')
        : old('customer_type', $customerType ?? 'b2c');

    // Roles (old input > saved)
    $oldRoles = $isEdit
        ? collect(old('roles', $userRoleNames ?? []))
        : collect(old('roles', []));

    // If coming from “New B2B/B2C Customer” and roles are empty, auto-check Customer role
    $autoCheckCustomerRole = $oldRoles->isEmpty() && in_array($prefCustomerType, ['b2b','b2c'], true);
@endphp

@if($errors->any())
    <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
        <div class="font-medium mb-1">Please fix the following:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="{{ $isEdit ? 'admin-user-edit-form' : 'admin-user-create-form' }}"
      method="POST"
      action="{{ $action }}"
      class="space-y-4">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- USER TYPE --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">User type</div>
                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                    If you select staff roles below (Admin/Manager/Support/Accountant/Stores/Delivery Agent), we auto-switch this to <b>Staff</b>.
                </div>
            </div>
        </div>

        <select
            id="customer_type"
            name="customer_type"
            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                   focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
        >
            <option value="staff" @selected($prefCustomerType === 'staff')>
                Staff (Admin/Manager/Support/Accountant/Stores/Delivery Agent)
            </option>
            <option value="b2c" @selected($prefCustomerType === 'b2c')>
                Customer (B2C – regular online customer)
            </option>
            <option value="b2b" @selected($prefCustomerType === 'b2b')>
                Customer (B2B – MOQ + customer pricing)
            </option>
        </select>

        @error('customer_type')
            <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- BASIC INFO --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $user->name ?? '') }}"
                    required
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $user->email ?? '') }}"
                    required
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                <input
                    type="text"
                    name="phone"
                    value="{{ old('phone', $user->phone ?? '') }}"
                    required
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>
        </div>

        {{-- PASSWORD --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $isEdit ? 'New password (optional)' : 'Password' }}
                </label>
                <input
                    type="password"
                    name="password"
                    @if(!$isEdit) required @endif
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $isEdit ? 'Confirm new password' : 'Confirm password' }}
                </label>
                <input
                    type="password"
                    name="password_confirmation"
                    @if(!$isEdit) required @endif
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>
        </div>
    </div>

    {{-- ROLES --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Roles</div>
                <div class="text-[10px] text-gray-500 dark:text-gray-400">
                    For customers, select <b>Customer</b>. For staff, select Admin/Manager/Support/Accountant/Stores/Delivery Agent as needed.
                </div>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-2">
            @foreach($roles as $role)
                @php
                    $checked =
                        $oldRoles->contains($role->name)
                        || ($autoCheckCustomerRole && $role->name === 'Customer');
                @endphp

                <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role->name }}"
                        class="role-checkbox rounded border-gray-300 dark:border-gray-700"
                        data-role="{{ $role->name }}"
                        @checked($checked)
                    >
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>

        @error('roles')
            <p class="mt-1 text-[11px] text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- FLAGS --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
        <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="mark_email_verified" value="1"
                   class="rounded border-gray-300 dark:border-gray-700"
                   @checked(old('mark_email_verified', $isEdit ? (bool)($user->email_verified_at ?? false) : false))>
            <span>Mark email as verified</span>
        </label>

        <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-gray-300 dark:border-gray-700"
                   @checked(old('is_active', $isEdit ? (bool)($user->is_active ?? false) : true))
                   @if($isEdit && $user && $user->id === auth()->id()) disabled @endif>
            <span>
                Active / allow login
                @if($isEdit && $user && $user->id === auth()->id())
                    (you cannot deactivate yourself)
                @endif
            </span>
        </label>
    </div>

    {{-- ACTIONS --}}
    <div class="flex items-center justify-between">
        <a href="{{ $backUrl }}" class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100
                       bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium
                       hover:bg-gray-800 dark:hover:bg-gray-200">
            {{ $isEdit ? 'Save changes' : 'Create user' }}
        </button>
    </div>
</form>

{{-- Small UX automation:
   - If customer_type is b2b/b2c => ensure Customer role checked
   - If staff roles checked => set customer_type to staff
--}}
<script>
(function () {
    const customerType = document.getElementById('customer_type');
    const roleCheckboxes = document.querySelectorAll('.role-checkbox');

    const STAFF_ROLES = ['Admin','Manager','Support','Accountant','CAAccountant','Stores','DeliveryAgent'];

    function hasAnyStaffRoleChecked() {
        for (const cb of roleCheckboxes) {
            if (!cb.checked) continue;
            const r = cb.getAttribute('data-role');
            if (STAFF_ROLES.includes(r)) return true;
        }
        return false;
    }

    function findRoleCheckbox(roleName) {
        for (const cb of roleCheckboxes) {
            if (cb.getAttribute('data-role') === roleName) return cb;
        }
        return null;
    }

    function ensureCustomerRoleIfCustomerType() {
        const cb = findRoleCheckbox('Customer');
        if (!cb || !customerType) return;

        if (customerType.value === 'b2b' || customerType.value === 'b2c') {
            cb.checked = true;
        }
    }

    function syncTypeFromRoles() {
        if (!customerType) return;
        if (hasAnyStaffRoleChecked()) {
            customerType.value = 'staff';
        }
    }

    if (customerType) {
        customerType.addEventListener('change', ensureCustomerRoleIfCustomerType);
    }

    roleCheckboxes.forEach(cb => {
        cb.addEventListener('change', syncTypeFromRoles);
    });

    // init
    ensureCustomerRoleIfCustomerType();
    syncTypeFromRoles();
})();
</script>
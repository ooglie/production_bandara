<?php
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
?>

<?php if($errors->any()): ?>
    <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
        <div class="font-medium mb-1">Please fix the following:</div>
        <ul class="list-disc list-inside space-y-0.5">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<form id="<?php echo e($isEdit ? 'admin-user-edit-form' : 'admin-user-create-form'); ?>"
      method="POST"
      action="<?php echo e($action); ?>"
      class="space-y-4">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    
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
            <option value="staff" <?php if($prefCustomerType === 'staff'): echo 'selected'; endif; ?>>
                Staff (Admin/Manager/Support/Accountant/Stores/Delivery Agent)
            </option>
            <option value="b2c" <?php if($prefCustomerType === 'b2c'): echo 'selected'; endif; ?>>
                Customer (B2C – regular online customer)
            </option>
            <option value="b2b" <?php if($prefCustomerType === 'b2b'): echo 'selected'; endif; ?>>
                Customer (B2B – MOQ + customer pricing)
            </option>
        </select>

        <?php $__errorArgs = ['customer_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                <input
                    type="text"
                    name="name"
                    value="<?php echo e(old('name', $user->name ?? '')); ?>"
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
                    value="<?php echo e(old('email', $user->email ?? '')); ?>"
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
                    value="<?php echo e(old('phone', $user->phone ?? '')); ?>"
                    required
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>
        </div>

        
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <?php echo e($isEdit ? 'New password (optional)' : 'Password'); ?>

                </label>
                <input
                    type="password"
                    name="password"
                    <?php if(!$isEdit): ?> required <?php endif; ?>
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <?php echo e($isEdit ? 'Confirm new password' : 'Confirm password'); ?>

                </label>
                <input
                    type="password"
                    name="password_confirmation"
                    <?php if(!$isEdit): ?> required <?php endif; ?>
                    class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                           focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700"
                >
            </div>
        </div>
    </div>

    
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
            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $checked =
                        $oldRoles->contains($role->name)
                        || ($autoCheckCustomerRole && $role->name === 'Customer');
                ?>

                <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="<?php echo e($role->name); ?>"
                        class="role-checkbox rounded border-gray-300 dark:border-gray-700"
                        data-role="<?php echo e($role->name); ?>"
                        <?php if($checked): echo 'checked'; endif; ?>
                    >
                    <span><?php echo e($role->name); ?></span>
                </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php $__errorArgs = ['roles'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
        <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="mark_email_verified" value="1"
                   class="rounded border-gray-300 dark:border-gray-700"
                   <?php if(old('mark_email_verified', $isEdit ? (bool)($user->email_verified_at ?? false) : false)): echo 'checked'; endif; ?>>
            <span>Mark email as verified</span>
        </label>

        <label class="inline-flex items-center gap-2 text-[12px] text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-gray-300 dark:border-gray-700"
                   <?php if(old('is_active', $isEdit ? (bool)($user->is_active ?? false) : true)): echo 'checked'; endif; ?>
                   <?php if($isEdit && $user && $user->id === auth()->id()): ?> disabled <?php endif; ?>>
            <span>
                Active / allow login
                <?php if($isEdit && $user && $user->id === auth()->id()): ?>
                    (you cannot deactivate yourself)
                <?php endif; ?>
            </span>
        </label>
    </div>

    
    <div class="flex items-center justify-between">
        <a href="<?php echo e($backUrl); ?>" class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100
                       bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium
                       hover:bg-gray-800 dark:hover:bg-gray-200">
            <?php echo e($isEdit ? 'Save changes' : 'Create user'); ?>

        </button>
    </div>
</form>


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
</script><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/users/_form.blade.php ENDPATH**/ ?>
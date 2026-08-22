<?php
    /**
     * Reusable B2C Customer form.
     *
     * Expected variables:
     * - $action (string) required
     * - $mode ('create'|'edit') required
     * - $user (\App\Models\User|null) optional (required for edit)
     * - $backUrl (string) optional
     */

    $mode = $mode ?? 'create';
    $isEdit = $mode === 'edit';

    $user = $user ?? null;

    $backUrl = $backUrl
        ?? (\Illuminate\Support\Facades\Route::has('admin.customers.b2c.index')
            ? route('admin.customers.b2c.index')
            : (\Illuminate\Support\Facades\Route::has('admin.users.index')
                ? route('admin.users.index', ['customer_type' => 'b2c'])
                : url()->previous()));
?>

<?php if(session('status')): ?>
    <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
        <ul class="list-disc list-inside space-y-0.5">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($e); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<form id="<?php echo e($isEdit ? 'b2c-update-form' : 'b2c-create-form'); ?>"
      method="POST"
      action="<?php echo e($action); ?>"
      class="space-y-4">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    
    <input type="hidden" name="customer_type" value="b2c">
    <input type="hidden" name="roles[]" value="Customer">

    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-4">
        <div class="grid gap-4">

            <div>
                <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name"
                       value="<?php echo e(old('name', $user->name ?? '')); ?>"
                       required
                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                              focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email"
                           value="<?php echo e(old('email', $user->email ?? '')); ?>"
                           required
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo e(old('phone', $user->phone ?? '')); ?>"
                           required
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">GSTIN</label>
                    <input type="text" name="gst_number"
                           value="<?php echo e(old('gst_number', $user->gst_number ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">FSSAI</label>
                    <input type="text" name="fssai_number"
                           value="<?php echo e(old('fssai_number', $user->fssai_number ?? '')); ?>"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        <?php echo e($isEdit ? 'New password (optional)' : 'Password'); ?>

                    </label>
                    <input type="password" name="password" <?php echo e($isEdit ? '' : 'required'); ?>

                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                        Confirm <?php echo e($isEdit ? 'new ' : ''); ?>password
                    </label>
                    <input type="password" name="password_confirmation" <?php echo e($isEdit ? '' : 'required'); ?>

                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[12px]
                                  focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                </div>
            </div>

            <div class="flex flex-col gap-2 pt-1">
                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="mark_email_verified" value="1"
                           <?php if(old('mark_email_verified', $isEdit ? (bool)($user?->email_verified_at) : false)): echo 'checked'; endif; ?>>
                    <span>Mark email as verified</span>
                </label>

                <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="is_active" value="1"
                           <?php if(old('is_active', $isEdit ? (bool)($user?->is_active) : true)): echo 'checked'; endif; ?>
                           <?php if($isEdit && $user && $user->id === auth()->id()): ?> disabled <?php endif; ?>>
                    <span>
                        Active / allow login
                        <?php if($isEdit && $user && $user->id === auth()->id()): ?>
                            (you cannot deactivate yourself)
                        <?php endif; ?>
                    </span>
                </label>
            </div>

        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="<?php echo e($backUrl); ?>"
           class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100
                       bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-[11px] font-medium
                       hover:bg-gray-800 dark:hover:bg-gray-200">
            <?php echo e($isEdit ? 'Save' : 'Create B2C Customer'); ?>

        </button>
    </div>
</form><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/customers/b2c/_form.blade.php ENDPATH**/ ?>
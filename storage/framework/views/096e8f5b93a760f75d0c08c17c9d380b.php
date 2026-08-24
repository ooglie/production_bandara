<?php $__env->startSection('title', $isEdit ? 'Edit salary profile' : 'New salary profile'); ?>
<?php $__env->startSection('breadcrumb', $isEdit ? 'Admin · Finance · Salary profiles · Edit' : 'Admin · Finance · Salary profiles · New'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $effectiveFrom = old('effective_from', $profile->effective_from?->format('Y-m-d') ?? today()->startOfMonth()->format('Y-m-d'));
    $effectiveTo = old('effective_to', $profile->effective_to?->format('Y-m-d'));
    $inputClass = 'mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-gray-500';
?>

<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50"><?php echo e($isEdit ? 'Edit salary profile' : 'New salary profile'); ?></h1>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Set the effective salary period without changing historical monthly records.</p>
        </div>
        <a href="<?php echo e(route('admin.finance.salary-profiles.index')); ?>" class="shrink-0 rounded border border-gray-300 px-3 py-1.5 text-[11px] text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900">Back</a>
    </div>

    <?php echo $__env->make('admin.finance.partials.nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('admin.finance.partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-5 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        Salary profile periods cannot overlap for the same staff member. Close the earlier profile and create a new dated profile when the salary changes. Existing monthly salary snapshots are not recalculated.
    </div>

    <form method="POST" action="<?php echo e($isEdit ? route('admin.finance.salary-profiles.update', $profile) : route('admin.finance.salary-profiles.store')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>
        <?php if($isEdit): ?>
            <?php echo method_field('PUT'); ?>
        <?php endif; ?>

        <section class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-50">Profile details</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="salary-profile-user" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Staff member</label>
                    <select id="salary-profile-user" name="user_id" required class="<?php echo e($inputClass); ?>">
                        <option value="">Select staff member</option>
                        <?php $__currentLoopData = $staffMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($staff->id); ?>" <?php if((string) old('user_id', $profile->user_id) === (string) $staff->id): echo 'selected'; endif; ?>>
                                <?php echo e($staff->name); ?> · <?php echo e($staff->email); ?><?php echo e(isset($staff->is_active) && ! $staff->is_active ? ' · inactive account' : ''); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['user_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="monthly-salary" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Monthly salary</label>
                    <input id="monthly-salary" type="number" name="monthly_salary" value="<?php echo e(old('monthly_salary', $profile->monthly_salary)); ?>" min="0.01" step="0.01" required class="<?php echo e($inputClass); ?>">
                    <?php $__errorArgs = ['monthly_salary'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="salary-payment-day" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Payment day</label>
                    <input id="salary-payment-day" type="number" name="payment_day" value="<?php echo e(old('payment_day', $profile->payment_day ?? 7)); ?>" min="1" max="31" required class="<?php echo e($inputClass); ?>">
                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Days 29–31 are treated as month-end in shorter months.</p>
                    <?php $__errorArgs = ['payment_day'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="salary-effective-from" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Effective from</label>
                    <input id="salary-effective-from" type="date" name="effective_from" value="<?php echo e($effectiveFrom); ?>" required class="<?php echo e($inputClass); ?>">
                    <?php $__errorArgs = ['effective_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="salary-effective-to" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Effective to</label>
                    <input id="salary-effective-to" type="date" name="effective_to" value="<?php echo e($effectiveTo); ?>" class="<?php echo e($inputClass); ?>">
                    <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">Leave blank while the profile remains current.</p>
                    <?php $__errorArgs = ['effective_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-2">
                    <label for="salary-profile-notes" class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea id="salary-profile-notes" name="notes" rows="4" maxlength="10000" class="<?php echo e($inputClass); ?>"><?php echo e(old('notes', $profile->notes)); ?></textarea>
                    <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-300">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" <?php if((bool) old('is_active', $profile->is_active ?? true)): echo 'checked'; endif; ?> class="rounded border-gray-300 dark:border-gray-700">
                        <span>Active profile</span>
                    </label>
                    <?php $__errorArgs = ['is_active'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-[10px] text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a href="<?php echo e(route('admin.finance.salary-profiles.index')); ?>" class="text-[11px] text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
            <button type="submit" class="inline-flex items-center rounded border border-gray-900 bg-gray-900 px-4 py-1.5 text-[11px] font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                <?php echo e($isEdit ? 'Save salary profile' : 'Create salary profile'); ?>

            </button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/finance/salary-profiles/form.blade.php ENDPATH**/ ?>
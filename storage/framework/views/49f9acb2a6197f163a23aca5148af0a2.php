<?php
    /** @var \App\Models\AttributeValue|null $value */
    $isEdit = isset($value);
?>

<form method="POST" action="<?php echo e($action); ?>">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Name
                </label>
                <input
                    type="text"
                    name="name"
                    value="<?php echo e(old('name', $value->name ?? '')); ?>"
                    required
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['name'];
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

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Internal value / code (optional)
                </label>
                <input
                    type="text"
                    name="value"
                    value="<?php echo e(old('value', $value->value ?? '')); ?>"
                    placeholder="e.g. internal code"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['value'];
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
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Position
                </label>
                <input
                    type="number"
                    name="position"
                    value="<?php echo e(old('position', $value->position ?? 0)); ?>"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['position'];
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
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                <?php echo e($isEdit ? 'Update option value' : 'Create option value'); ?>

            </button>

            <a href="<?php echo e(route('admin.attributes.values.index', $attribute)); ?>"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/values/_form.blade.php ENDPATH**/ ?>
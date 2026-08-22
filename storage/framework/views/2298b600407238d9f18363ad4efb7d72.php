<?php
    /** @var \App\Models\Attribute|null $attribute */
    $isEdit = isset($attribute);
?>

<form method="POST" action="<?php echo e($action); ?>">
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PUT'); ?>
    <?php endif; ?>

    <div class="space-y-5">
        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Name
                </label>
                <input
                    type="text"
                    name="name"
                    value="<?php echo e(old('name', $attribute->name ?? '')); ?>"
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
                    Slug (optional)
                </label>
                <input
                    type="text"
                    name="slug"
                    value="<?php echo e(old('slug', $attribute->slug ?? '')); ?>"
                    placeholder="auto-generated if empty"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['slug'];
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

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    Storefront label (optional)
                </label>
                <input
                    type="text"
                    name="display_name"
                    value="<?php echo e(old('display_name', $attribute->display_name ?? '')); ?>"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                <?php $__errorArgs = ['display_name'];
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
                    Option display style
                </label>
                <select
                    name="frontend_type"
                    class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                >
                    <?php
                        $currentType = old('frontend_type', $attribute->frontend_type ?? 'select');
                    ?>
                    <option value="select" <?php if($currentType === 'select'): echo 'selected'; endif; ?>>Select</option>
                    <option value="radio"  <?php if($currentType === 'radio'): echo 'selected'; endif; ?>>Radio buttons</option>
                    <option value="label"  <?php if($currentType === 'label'): echo 'selected'; endif; ?>>Label tags</option>
                    <option value="text"   <?php if($currentType === 'text'): echo 'selected'; endif; ?>>Free text</option>
                </select>
                <?php $__errorArgs = ['frontend_type'];
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

        <div>
            <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                <input
                    type="checkbox"
                    name="is_filterable"
                    value="1"
                    <?php if(old('is_filterable', $attribute->is_filterable ?? true)): echo 'checked'; endif; ?>
                >
                <span>Use as storefront filter</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
            >
                <?php echo e($isEdit ? 'Update option group' : 'Create option group'); ?>

            </button>

            <a href="<?php echo e(route('admin.attributes.index')); ?>"
               class="text-xs text-gray-500 hover:text-gray-800 dark:hover:text-gray-200">
                Cancel
            </a>
        </div>
    </div>
</form>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/attributes/_form.blade.php ENDPATH**/ ?>
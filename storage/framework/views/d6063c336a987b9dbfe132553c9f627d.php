<?php
    /** @var \App\Models\HsnCode|null $hsnCode */
    $isEdit = isset($hsnCode);
?>

<div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
    <div class="grid gap-3 md:grid-cols-3">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                HSN Code
            </label>
            <input type="text" name="code"
                   value="<?php echo e(old('code', $hsnCode->code ?? '')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]"
                   placeholder="e.g. 0402" required>
            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                GST %
            </label>
            <input type="number" step="0.01" min="0" max="100" name="gst_rate"
                   value="<?php echo e(old('gst_rate', $hsnCode->gst_rate ?? 5)); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]" required>
            <?php $__errorArgs = ['gst_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="flex items-end">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1"
                       <?php if(old('is_active', $hsnCode->is_active ?? true)): echo 'checked'; endif; ?>>
                <span class="text-[11px] text-gray-700 dark:text-gray-300">Active</span>
            </label>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Name (optional)
            </label>
            <input type="text" name="name"
                   value="<?php echo e(old('name', $hsnCode->name ?? '')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]"
                   placeholder="e.g. Dairy / Frozen / Grocery">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300 mb-1">
                Description (optional)
            </label>
            <input type="text" name="description"
                   value="<?php echo e(old('description', $hsnCode->description ?? '')); ?>"
                   class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[11px]"
                   placeholder="Any notes for accountant/admin">
            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>
</div>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/hsn-codes/_form.blade.php ENDPATH**/ ?>
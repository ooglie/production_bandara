<?php $__env->startSection('title', 'Variant Option Values'); ?>

<?php $__env->startSection('breadcrumb', 'Admin · Variant Options · Values'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $currentQuery = request()->only(['attribute_id', 'q', 'page']);
        $queryString = http_build_query($currentQuery);
    ?>

    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                    Variant Option Values
                </h1>
                <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                    Manage all selectable values used by product variants, such as pack size, prawn size, or cut option.
                </p>
            </div>

            <a href="<?php echo e(route('admin.attributes.index')); ?>"
               class="inline-flex items-center px-3 py-1.5 text-xs rounded border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Manage option groups
            </a>
        </div>

        <?php if(session('status')): ?>
            <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-100">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <div class="rounded-lg border border-gray-200 bg-white p-4 text-xs dark:border-gray-800 dark:bg-gray-950">
            <form method="POST" action="<?php echo e(route('admin.variant-option-values.store')); ?>" class="grid gap-3 md:grid-cols-5 md:items-end">
                <?php echo csrf_field(); ?>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Option group
                    </label>
                    <select name="attribute_id" required
                            class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                        <option value="">Choose group</option>
                        <?php $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attribute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($attribute->id); ?>" <?php if((int) old('attribute_id', $selectedAttributeId) === (int) $attribute->id): echo 'selected'; endif; ?>>
                                <?php echo e($attribute->display_name ?: $attribute->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['attribute_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Display value
                    </label>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" required placeholder="e.g. 500g / Jumbo"
                           class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Internal value/code
                    </label>
                    <input type="text" name="value" value="<?php echo e(old('value')); ?>" placeholder="Optional"
                           class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    <?php $__errorArgs = ['value'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                        Position
                    </label>
                    <input type="number" name="position" value="<?php echo e(old('position', 0)); ?>"
                           class="mt-1 w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    <?php $__errorArgs = ['position'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-[11px] text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-1">
                    <button type="submit"
                            class="mt-1 inline-flex w-full items-center justify-center rounded border border-gray-900 bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200">
                        + Add value
                    </button>
                </div>
            </form>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-3 text-xs">
            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Option group
                </label>
                <select name="attribute_id"
                        class="mt-1 w-56 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                    <option value="">All groups</option>
                    <?php $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attribute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($attribute->id); ?>" <?php if((int) $selectedAttributeId === (int) $attribute->id): echo 'selected'; endif; ?>>
                            <?php echo e($attribute->display_name ?: $attribute->name); ?> (<?php echo e($attribute->values_count); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    Search
                </label>
                <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Value, code, or group"
                       class="mt-1 w-64 rounded border border-gray-300 bg-white px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
            </div>

            <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">
                Apply
            </button>

            <?php if($selectedAttributeId || $search !== ''): ?>
                <a href="<?php echo e(route('admin.variant-option-values.index')); ?>"
                   class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200 text-xs dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr class="text-[11px] uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left">Option group</th>
                        <th class="px-3 py-2 text-left">Display value</th>
                        <th class="px-3 py-2 text-left">Internal value/code</th>
                        <th class="px-3 py-2 text-right">Position</th>
                        <th class="px-3 py-2 text-right">Used by</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                    <?php $__empty_1 = true; $__currentLoopData = $values; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $formId = 'update-option-value-' . $value->id;
                            $deleteFormId = 'delete-option-value-' . $value->id;
                            $variantCount = $variantUsageCounts[$value->id] ?? 0;
                            $isUsed = ((int) $value->products_count > 0) || ((int) $variantCount > 0);
                            $actionQuery = $queryString ? '?' . $queryString : '';
                        ?>
                        <tr>
                            <td class="px-3 py-2 align-top text-gray-800 dark:text-gray-100">
                                <div class="font-medium">
                                    <?php echo e($value->attribute?->display_name ?: $value->attribute?->name ?: '—'); ?>

                                </div>
                                <?php if($value->attribute?->slug): ?>
                                    <div class="text-[10px] text-gray-400">
                                        <?php echo e($value->attribute->slug); ?>

                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-2 align-top">
                                <input form="<?php echo e($formId); ?>" type="text" name="name" value="<?php echo e($value->name); ?>" required
                                       class="w-48 rounded border border-gray-300 bg-white px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                            </td>

                            <td class="px-3 py-2 align-top">
                                <input form="<?php echo e($formId); ?>" type="text" name="value" value="<?php echo e($value->value); ?>" placeholder="—"
                                       class="w-44 rounded border border-gray-300 bg-white px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                            </td>

                            <td class="px-3 py-2 align-top text-right">
                                <input form="<?php echo e($formId); ?>" type="number" name="position" value="<?php echo e($value->position ?? 0); ?>"
                                       class="w-20 rounded border border-gray-300 bg-white px-2 py-1 text-right text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-gray-500">
                            </td>

                            <td class="px-3 py-2 align-top text-right text-gray-700 dark:text-gray-300">
                                <div><?php echo e((int) $value->products_count); ?> product(s)</div>
                                <div class="text-[10px] text-gray-400"><?php echo e((int) $variantCount); ?> variant(s)</div>
                            </td>

                            <td class="px-3 py-2 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <form id="<?php echo e($formId); ?>" method="POST" action="<?php echo e(route('admin.variant-option-values.update', $value)); ?><?php echo e($actionQuery); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                    </form>
                                    <button form="<?php echo e($formId); ?>" type="submit"
                                            class="text-[11px] text-gray-700 hover:text-gray-950 dark:text-gray-300 dark:hover:text-gray-100">
                                        Save
                                    </button>

                                    <?php if($isUsed): ?>
                                        <span class="text-[11px] text-gray-400" title="Remove this value from products and variants before deleting it.">
                                            Delete locked
                                        </span>
                                    <?php else: ?>
                                        <form id="<?php echo e($deleteFormId); ?>" method="POST" action="<?php echo e(route('admin.variant-option-values.destroy', $value)); ?><?php echo e($actionQuery); ?>"
                                              onsubmit="return confirm('Delete this variant option value?');">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                        </form>
                                        <button form="<?php echo e($deleteFormId); ?>" type="submit"
                                                class="text-[11px] text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                                No variant option values found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <?php echo e($values->links()); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/variant_option_values/index.blade.php ENDPATH**/ ?>
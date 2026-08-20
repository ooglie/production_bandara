<?php $__env->startSection('title', 'Edit Role Permissions'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $isAdminRole = strcasecmp($role->name, 'Admin') === 0;

    $has = function(string $perm) use ($rolePermissions) {
        return in_array($perm, $rolePermissions, true);
    };

    $labelFor = function(string $module) use ($labels) {
        return $labels[$module] ?? ucfirst($module);
    };
?>

<div class="max-w-6xl mx-auto px-4 py-5 text-xs space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                Permissions for: <?php echo e($role->name); ?>

            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                “Manage” = add/edit/delete. “View” = list/view pages.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.roles.index')); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Back
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <ul class="list-disc pl-4 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.roles.update', $role)); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-gray-50">Module permissions</h2>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        Tip: If you tick “Manage”, “View” will be auto-enabled on save.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="select-all"
                            class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Select all
                    </button>
                    <button type="button" id="select-none"
                            class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Clear
                    </button>
                </div>
            </div>

            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-[11px]">
                    <thead class="bg-gray-50 dark:bg-gray-950/40">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Module</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">View</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Add / Edit / Delete</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__currentLoopData = $matrix; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $actions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $viewName = 'view ' . $module;
                            $manageName = 'manage ' . $module;
                            $hasView = in_array('view', $actions, true);
                            $hasManage = in_array('manage', $actions, true);
                        ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-50 font-medium">
                                <?php echo e($labelFor($module)); ?>

                            </td>

                            <td class="px-3 py-2">
                                <?php if($hasView): ?>
                                    <label class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                        <input type="checkbox" class="perm-checkbox"
                                               name="permissions[]"
                                               value="<?php echo e($viewName); ?>"
                                               <?php if($has($viewName)): echo 'checked'; endif; ?>
                                               <?php if($isAdminRole): echo 'disabled'; endif; ?>>
                                        <span><?php echo e($viewName); ?></span>
                                    </label>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-2">
                                <?php if($hasManage): ?>
                                    <label class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-200">
                                        <input type="checkbox" class="perm-checkbox"
                                               name="permissions[]"
                                               value="<?php echo e($manageName); ?>"
                                               <?php if($has($manageName)): echo 'checked'; endif; ?>
                                               <?php if($isAdminRole): echo 'disabled'; endif; ?>>
                                        <span><?php echo e($manageName); ?></span>
                                    </label>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        
        <?php if($extraPermissions->count() > 0): ?>
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                <h2 class="font-semibold text-gray-900 dark:text-gray-50">Other permissions</h2>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                    These are permissions found in the database that are not part of the main matrix.
                </p>

                <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <?php $__currentLoopData = $extraPermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="inline-flex items-center gap-2 text-gray-700 dark:text-gray-200">
                            <input type="checkbox" class="perm-checkbox"
                                   name="permissions[]"
                                   value="<?php echo e($perm->name); ?>"
                                   <?php if($has($perm->name)): echo 'checked'; endif; ?>
                                   <?php if($isAdminRole): echo 'disabled'; endif; ?>>
                            <span><?php echo e($perm->name); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between">
            <div class="text-[11px] text-gray-500 dark:text-gray-400">
                <?php if($isAdminRole): ?>
                    Admin role is enforced as full access to prevent lockouts.
                <?php endif; ?>
            </div>

            <button type="submit"
                    class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Save
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const boxes = Array.from(document.querySelectorAll('.perm-checkbox'));
    const btnAll = document.getElementById('select-all');
    const btnNone = document.getElementById('select-none');

    if (btnAll) btnAll.addEventListener('click', function () {
        boxes.forEach(cb => { if (!cb.disabled) cb.checked = true; });
    });

    if (btnNone) btnNone.addEventListener('click', function () {
        boxes.forEach(cb => { if (!cb.disabled) cb.checked = false; });
    });
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/roles/edit.blade.php ENDPATH**/ ?>
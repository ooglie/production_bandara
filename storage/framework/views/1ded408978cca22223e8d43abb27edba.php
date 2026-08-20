<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Users
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage customers and staff. You can edit roles, update details, and delete accounts.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('admin.users.create')); ?>"
               class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs">
                + Add user
            </a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <form method="GET" class="flex flex-wrap items-center gap-2 text-xs mb-3">
        <input
            type="text"
            name="q"
            value="<?php echo e(request('q')); ?>"
            placeholder="Search name / email / phone"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <select
            name="role"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
            <option value="">All roles</option>
            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($role->name); ?>" <?php if(request('role') === $role->name): echo 'selected'; endif; ?>>
                    <?php echo e($role->name); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button
            class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5">
            Filter
        </button>
    </form>

    <div class="border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-900 overflow-hidden">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-800">
                <tr class="text-left text-[11px] text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Email</th>
                    <th class="px-3 py-2.5">Phone</th>
                    <th class="px-3 py-2.5">Roles</th>
                    <th class="px-3 py-2.5">Created</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                <?php echo e($user->name); ?>

                                <?php if($user->id === auth()->id()): ?>
                                    <span class="ml-1 text-[10px] text-gray-400">(you)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <div class="text-[11px] text-gray-800 dark:text-gray-100">
                                <?php echo e($user->email); ?>

                            </div>
                            <div class="text-[10px] text-gray-400">
                                <?php if($user->email_verified_at): ?>
                                    Verified <?php echo e($user->email_verified_at->format('d M Y')); ?>

                                <?php else: ?>
                                    Not verified
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($user->phone ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php
                                $roleNames = $user->getRoleNames();
                            ?>
                            <?php if($roleNames->isEmpty()): ?>
                                <span class="text-[11px] text-gray-400">No roles</span>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php $__currentLoopData = $roleNames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                                            <?php echo e($role); ?>

                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($user->created_at->format('d M Y')); ?>

                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.users.edit', $user)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>
                                <?php if($user->id !== auth()->id()): ?>
                                    <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>"
                                          onsubmit="return confirm('Delete this user?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit"
                                                class="text-[11px] text-red-600 dark:text-red-400 underline">
                                            Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            <?php echo e($users->withQueryString()->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/users/index.blade.php ENDPATH**/ ?>
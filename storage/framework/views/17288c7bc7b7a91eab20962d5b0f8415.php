<?php $__env->startSection('title', 'B2B Customers'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $customers = $customers ?? $users ?? collect();
    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);

    // We intentionally DO NOT fall back to admin.users.* for Edit/Delete on this screen
    // to prevent accidental deletes / 404s due to missing/role-restricted user routes.

    $createUrl = $has('admin.b2b.customers.create')
        ? route('admin.b2b.customers.create')
        : ($has('admin.users.create') ? route('admin.users.create', ['customer_type' => 'b2b']) : null);

    $q = request('q', '');

    $missingCrudRoutes = [];
    foreach ([
        'admin.b2b.customers.create',
        'admin.b2b.customers.store',
        'admin.b2b.customers.edit',
        'admin.b2b.customers.update',
        'admin.b2b.customers.destroy',
    ] as $r) {
        if (!$has($r)) $missingCrudRoutes[] = $r;
    }
?>

<div class="max-w-7xl mx-auto px-4 py-5 text-xs space-y-4">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-50">
                B2B Customers
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage wholesale customers. Configure MOQ + customer pricing per customer.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <?php if($createUrl): ?>
                <a href="<?php echo e($createUrl); ?>"
                   class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Add B2B Customer
                </a>
            <?php else: ?>
                <span class="text-[11px] px-3 py-1 rounded-full border border-yellow-300 bg-yellow-50 text-yellow-800">
                    Create route missing
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if(!empty($missingCrudRoutes)): ?>
        <div class="rounded border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-900">
            <div class="font-medium">B2B Customer CRUD routes missing</div>
            <div class="mt-1 text-[10px] text-yellow-800">
                Edit/Delete is disabled on this screen until these routes exist:
                <ul class="list-disc pl-4 mt-1 space-y-0.5">
                    <?php $__currentLoopData = $missingCrudRoutes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><code><?php echo e($r); ?></code></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="<?php echo e($q); ?>"
               placeholder="Search name / email / phone"
               class="w-64 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1 text-[11px]">
        <button type="submit"
                class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Search
        </button>
        <?php if($q): ?>
            <a href="<?php echo e(url()->current()); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Clear
            </a>
        <?php endif; ?>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50 dark:bg-gray-950/40">
            <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Customer</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Phone</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Active</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Pay Later</th>
                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Actions</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                    <td class="px-3 py-2">
                        <div class="font-medium text-gray-900 dark:text-gray-50"><?php echo e($c->name); ?></div>
                        <div class="text-[10px] text-gray-400"><?php echo e($c->email); ?> · #<?php echo e($c->id); ?></div>
                    </td>

                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200"><?php echo e($c->phone ?? '—'); ?></td>

                    <td class="px-3 py-2">
                        <?php if((bool)($c->is_active ?? true)): ?>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
                                Disabled
                            </span>
                        <?php endif; ?>
                    </td>

                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                        <?php ($terms = \Illuminate\Support\Facades\Schema::hasTable('b2b_customer_terms') ? $c->b2bTerms : null); ?>
                        <?php if($terms && $terms->pay_later_enabled && $terms->credit_status === 'active'): ?>
                            <div class="font-medium text-emerald-700 dark:text-emerald-300">Enabled</div>
                            <div class="text-[10px] text-gray-400">₹<?php echo e(number_format((float) $terms->credit_limit, 2)); ?> · <?php echo e((int) $terms->payment_terms_days); ?> days</div>
                        <?php elseif($terms && $terms->pay_later_enabled): ?>
                            <div class="font-medium text-amber-700 dark:text-amber-300"><?php echo e(ucwords(str_replace('_', ' ', $terms->credit_status ?? 'on_hold'))); ?></div>
                            <div class="text-[10px] text-gray-400">₹<?php echo e(number_format((float) $terms->credit_limit, 2)); ?> limit</div>
                        <?php else: ?>
                            <span class="text-[10px] text-gray-400">Disabled</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-2">

                            <?php if($has('admin.customers.b2b-products.index')): ?>
                                <a href="<?php echo e(route('admin.customers.b2b-products.index', $c)); ?>"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Catalog
                                </a>
                            <?php endif; ?>

                            <?php if($has('admin.b2b.moq.index')): ?>
                                <a href="<?php echo e(route('admin.b2b.moq.index', $c)); ?>"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    MOQ
                                </a>
                            <?php endif; ?>

                            <?php if($has('admin.b2b.prices.index')): ?>
                                <a href="<?php echo e(route('admin.b2b.prices.index', $c)); ?>"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Prices
                                </a>
                            <?php endif; ?>

                            <?php if($has('admin.b2b.customers.edit')): ?>
                                <a href="<?php echo e(route('admin.b2b.customers.edit', $c)); ?>"
                                   class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Edit
                                </a>
                            <?php else: ?>
                                <span class="text-[11px] px-3 py-1 rounded-full border border-gray-200 text-gray-400">
                                    Edit (route missing)
                                </span>
                            <?php endif; ?>

                            <?php if($has('admin.b2b.customers.destroy')): ?>
                                <form method="POST" action="<?php echo e(route('admin.b2b.customers.destroy', $c)); ?>"
                                      class="inline"
                                      onsubmit="return confirm('Delete this B2B customer? This will delete the user account and related records.');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit"
                                            class="text-[11px] px-3 py-1 rounded-full border border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-900/20">
                                        Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-[11px] px-3 py-1 rounded-full border border-gray-200 text-gray-400">
                                    Delete (route missing)
                                </span>
                            <?php endif; ?>

                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        No B2B customers found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if(is_object($customers) && method_exists($customers, 'links')): ?>
        <div class="mt-3">
            <?php echo e($customers->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/customers/index.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', 'Newsletter subscribers'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Newsletter subscribers
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage newsletter signup, double opt-in, and exports.
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <a href="<?php echo e(route('admin.newsletter-subscribers.index', array_merge(request()->all(), ['export' => 'csv']))); ?>"
               class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5">
                Export CSV
            </a>
            <a href="<?php echo e(route('admin.newsletter-subscribers.create')); ?>"
               class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5">
                + Add subscriber
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
            placeholder="Search email / name / source"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <select
            name="status"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
            <option value="">All statuses</option>
            <?php $__currentLoopData = ['pending', 'active', 'unsubscribed', 'bounced']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($status); ?>" <?php if(request('status') === $status): echo 'selected'; endif; ?>>
                    <?php echo e(ucfirst($status)); ?>

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
                    <th class="px-3 py-2.5">Email</th>
                    <th class="px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">Confirmed</th>
                    <th class="px-3 py-2.5">Unsubscribed</th>
                    <th class="px-3 py-2.5">Source</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $subscribers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subscriber): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            <?php echo e($subscriber->email); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($subscriber->name ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                <?php if($subscriber->status === 'active'): ?>
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                <?php elseif($subscriber->status === 'unsubscribed'): ?>
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                <?php elseif($subscriber->status === 'pending'): ?>
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                <?php else: ?>
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                <?php endif; ?>
                            ">
                                <?php echo e(ucfirst($subscriber->status)); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e(optional($subscriber->confirmed_at)->format('d M Y') ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e(optional($subscriber->unsubscribed_at)->format('d M Y') ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                <?php echo e($subscriber->source ?? '—'); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.newsletter-subscribers.edit', $subscriber)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                <?php if($subscriber->status === 'pending'): ?>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.newsletter-subscribers.resend-confirmation', $subscriber)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit"
                                                class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                            Resend
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST"
                                      action="<?php echo e(route('admin.newsletter-subscribers.destroy', $subscriber)); ?>"
                                      onsubmit="return confirm('Delete this subscriber?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit"
                                            class="text-[11px] text-red-600 dark:text-red-400 underline">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No subscribers found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            <?php echo e($subscribers->withQueryString()->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/newsletter_subscribers/index.blade.php ENDPATH**/ ?>
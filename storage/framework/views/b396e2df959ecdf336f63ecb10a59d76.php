<?php $__env->startSection('title', 'Newsletter campaigns'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Newsletter campaigns
            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Create and send email campaigns to active newsletter subscribers.
            </p>
        </div>
        <a href="<?php echo e(route('admin.newsletter-campaigns.create')); ?>"
           class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs">
            + New campaign
        </a>
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
            placeholder="Search name / subject"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
        <select
            name="status"
            class="rounded-full border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
            <option value="">All statuses</option>
            <?php $__currentLoopData = ['draft','scheduled','sending','sent','cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                    <th class="px-3 py-2.5">Name</th>
                    <th class="px-3 py-2.5">Subject</th>
                    <th class="px-3 py-2.5">Status</th>
                    <th class="px-3 py-2.5">Recipients</th>
                    <th class="px-3 py-2.5">Created by</th>
                    <th class="px-3 py-2.5">Sent at</th>
                    <th class="px-3 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $campaigns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campaign): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-2 align-top">
                            <div class="font-medium text-gray-900 dark:text-gray-50">
                                <?php echo e($campaign->name); ?>

                            </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($campaign->subject); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                <?php if($campaign->status === 'sent'): ?>
                                    bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                <?php elseif($campaign->status === 'sending'): ?>
                                    bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                <?php elseif($campaign->status === 'cancelled'): ?>
                                    bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                <?php else: ?>
                                    bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200
                                <?php endif; ?>
                            ">
                                <?php echo e(ucfirst($campaign->status)); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($campaign->recipients_count); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e($campaign->createdBy->name ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top">
                            <?php echo e(optional($campaign->sent_at)->format('d M Y H:i') ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 align-top text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="<?php echo e(route('admin.newsletter-campaigns.edit', $campaign)); ?>"
                                   class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                    Edit
                                </a>

                                <?php if(in_array($campaign->status, ['draft','scheduled'], true)): ?>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.newsletter-campaigns.send', $campaign)); ?>"
                                          onsubmit="return confirm('Send this campaign to all active subscribers now?');">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit"
                                                class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                                            Send now
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if(!in_array($campaign->status, ['sending','sent'], true)): ?>
                                    <form method="POST"
                                          action="<?php echo e(route('admin.newsletter-campaigns.destroy', $campaign)); ?>"
                                          onsubmit="return confirm('Delete this campaign?');">
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
                        <td colspan="7" class="px-3 py-4 text-center text-[11px] text-gray-500 dark:text-gray-400">
                            No campaigns found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800">
            <?php echo e($campaigns->withQueryString()->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/newsletter_campaigns/index.blade.php ENDPATH**/ ?>
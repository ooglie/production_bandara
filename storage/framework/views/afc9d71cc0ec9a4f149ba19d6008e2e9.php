<?php $__env->startSection('title', 'Support Tickets'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Support Tickets</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Manage customer tickets (Admin / Manager / Support).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('support.tickets.index')); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                All
            </a>
            <a href="<?php echo e(route('support.tickets.unassigned')); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Unassigned
            </a>
            <a href="<?php echo e(route('support.tickets.mine')); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Mine
            </a>
        </div>
    </div>

    
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-3">
        <form method="GET" action="<?php echo e(url()->current()); ?>" class="grid gap-2 sm:grid-cols-4 items-end">
            <div>
                <label class="block text-[10px] text-gray-600 dark:text-gray-300 mb-1">Search</label>
                <input type="text" name="q" value="<?php echo e(request('q')); ?>"
                       placeholder="Ticket # / subject / email"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            </div>

            <div>
                <label class="block text-[10px] text-gray-600 dark:text-gray-300 mb-1">Status</label>
                <select name="status"
                        class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                    <option value="">All</option>
                    <?php $__currentLoopData = ['awaiting_customer_reply','resolved','closed','open','new']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($st); ?>" <?php if(request('status') === $st): echo 'selected'; endif; ?>>
                            <?php echo e(str_replace('_',' ', ucfirst($st))); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] text-gray-600 dark:text-gray-300 mb-1">Tag</label>
                <input type="text" name="tag" value="<?php echo e(request('tag')); ?>"
                       placeholder="billing / technical / sales"
                       class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                        class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                    Apply
                </button>

                <a href="<?php echo e(url()->current()); ?>"
                   class="text-[11px] text-gray-500 dark:text-gray-400 hover:underline">
                    Reset
                </a>
            </div>
        </form>
    </div>

    
    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-3 py-2 font-medium">Ticket</th>
                        <th class="px-3 py-2 font-medium">Subject</th>
                        <th class="px-3 py-2 font-medium">Customer</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Assigned</th>
                        <th class="px-3 py-2 font-medium">Updated</th>
                        <th class="px-3 py-2 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = ($tickets ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            // $subject = $ticket->subject ?? $ticket->title ?? 'Ticket';
                            $status  = $ticket->status ?? 'open';

                            // $customerName = data_get($ticket, 'user.name')
                            //     ?? data_get($ticket, 'customer.name')
                            //     ?? data_get($ticket, 'customer_email')
                            //     ?? 'Customer';

                            $subject = $ticket->displaySubject();
                            $customerName = $ticket->displayCustomerName();
                            
                            $assigneeName = data_get($ticket, 'assignee.name')
                                ?? data_get($ticket, 'assignedTo.name')
                                ?? '—';

                            $updated = $ticket->updated_at ?? null;
                        ?>
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-3 py-2 whitespace-nowrap">
                                #<?php echo e($ticket->id); ?>

                            </td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-900 dark:text-gray-50">
                                    <?php echo e($subject); ?>

                                </div>
                                <?php if(!empty($ticket->category)): ?>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400">
                                        <?php echo e(is_string($ticket->category) ? $ticket->category : ($ticket->category->name ?? '')); ?>

                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <?php echo e($customerName); ?>

                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-2 py-0.5 text-[10px]">
                                    <?php echo e(ucfirst(str_replace('_',' ', $status))); ?>

                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <?php echo e($assigneeName); ?>

                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                <?php if($updated): ?>
                                    <?php echo e(\Illuminate\Support\Carbon::parse($updated)->diffForHumans()); ?>

                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-right">
                                <a href="<?php echo e(route('support.tickets.show', $ticket)); ?>"
                                   class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] hover:bg-gray-100 dark:hover:bg-gray-800">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                No tickets found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <?php if(isset($tickets) && is_object($tickets) && method_exists($tickets, 'links')): ?>
            <div class="px-3 py-3 border-t border-gray-200 dark:border-gray-800">
                <?php echo e($tickets->withQueryString()->links()); ?>

            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/support/tickets/index.blade.php ENDPATH**/ ?>
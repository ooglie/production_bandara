<?php $__env->startSection('title', 'Ticket #' . ($ticket->id ?? '')); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4 text-xs">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                Ticket #<?php echo e($ticket->id); ?>

            </h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                <?php echo e($ticket->subject ?? $ticket->title ?? 'Support ticket'); ?>

            </p>
        </div>

        <a href="<?php echo e(route('support.tickets.index')); ?>"
           class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="rounded border border-emerald-300 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[11px] text-red-800">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="grid gap-4 lg:grid-cols-3">
        
        <div class="lg:col-span-1 space-y-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Customer</div>
                <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                    <?php echo e($ticket->customer?->name ?? $ticket->customer_email ?? '—'); ?>

                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                    <?php echo e($ticket->customer?->email ?? '—'); ?>

                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Status</div>
                        <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                            <?php echo e(ucfirst(str_replace('_',' ', $ticket->status ?? 'open'))); ?>

                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[11px] text-gray-500 dark:text-gray-400">Assigned</div>
                        <div class="text-[12px] font-medium text-gray-900 dark:text-gray-50">
                            <?php echo e($ticket->assignee?->name ?? '—'); ?>

                        </div>
                    </div>
                </div>

                
                <form method="POST" action="<?php echo e(route('support.tickets.status', $ticket)); ?>" class="pt-2 space-y-2">
                    <?php echo csrf_field(); ?>
                    <label class="block text-[10px] text-gray-600 dark:text-gray-300">Change status</label>
                    <select name="status"
                            class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-[11px]">
                        <?php $__currentLoopData = ['open','awaiting_customer','awaiting_agent','resolved','closed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($st); ?>" <?php if(($ticket->status ?? 'open') === $st): echo 'selected'; endif; ?>>
                                <?php echo e(ucfirst(str_replace('_',' ', $st))); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button class="w-full inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Update
                    </button>
                </form>

                
                <form method="POST" action="<?php echo e(route('support.tickets.assignToMe', $ticket)); ?>" class="pt-2">
                    <?php echo csrf_field(); ?>
                    <button class="w-full inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                        Assign to me
                    </button>
                </form>
            </div>

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Tags</div>

                <form method="POST" action="<?php echo e(route('support.tickets.tags', $ticket)); ?>" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <?php $__currentLoopData = ($allTags ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="inline-flex items-center gap-2 text-[11px] text-gray-700 dark:text-gray-200">
                                <input type="checkbox" name="tags[]" value="<?php echo e($tag->id); ?>"
                                       <?php if($ticket->tags?->contains('id', $tag->id)): echo 'checked'; endif; ?>>
                                <span><?php echo e($tag->name); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <button class="w-full inline-flex items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                        Save tags
                    </button>
                </form>
            </div>
        </div>

        
        <div class="lg:col-span-2 space-y-3">

            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Reply to customer</div>

                <form method="POST" action="<?php echo e(route('support.tickets.reply', $ticket)); ?>" enctype="multipart/form-data" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <textarea name="message" rows="4" required
                              class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                              placeholder="Type your reply..."><?php echo e(old('message')); ?></textarea>

                    <input type="file" name="attachments[]" multiple
                           class="w-full text-[11px] text-gray-600 dark:text-gray-300">

                    <button class="inline-flex items-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                        Send reply
                    </button>
                </form>
            </div>


            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-2">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Internal note</div>

                <form method="POST" action="<?php echo e(route('support.tickets.note', $ticket)); ?>" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <textarea name="message" rows="3" required
                              class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-2 text-[11px]"
                              placeholder="Internal note (only staff can see)..."><?php echo e(old('message')); ?></textarea>

                    <button class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-4 py-1.5 text-[11px] hover:bg-gray-100 dark:hover:bg-gray-800">
                        Add note
                    </button>
                </form>
            </div>
            
            
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3">
                <div class="text-[12px] font-semibold text-gray-900 dark:text-gray-50">Conversation</div>

                <div class="space-y-3">

                    <?php $__empty_1 = true; $__currentLoopData = $ticket->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        
                        <?php if($message->is_internal): ?>
                            <?php continue; ?>
                        <?php endif; ?>

                        <?php
                            $isCustomer = $message->author && (int)$message->author->id === (int)$ticket->user_id;
                            $isStaff = $message->author && !$isCustomer;

                            $bubble =
                                $isCustomer
                                    ? 'border-gray-200 bg-gray-50 text-gray-900 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-100'
                                    : 'border-sky-200 bg-sky-50 text-gray-900 dark:border-sky-900/50 dark:bg-sky-900/20 dark:text-gray-100';

                            $side = $isCustomer ? 'items-end text-right' : 'items-start text-left';
                            $name = $isCustomer ? $message->author->name : ($isStaff ? 'Support Team' : 'System');
                            $time = $message->created_at ? $message->created_at->format('d M Y, H:i') : '';
                            $body = $message->message ?? $message->body ?? '';
                        ?>

                        <div class="flex flex-col <?php echo e($side); ?> gap-1">
                            <div class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                <span class="font-medium text-gray-700 dark:text-gray-200"><?php echo e($name); ?></span>
                                <span>•</span>
                                <span><?php echo e($time); ?></span>
                            </div>

                            <div class="max-w-3xl rounded-2xl border px-4 py-3 text-[13px] whitespace-pre-line <?php echo e($bubble); ?>">
                                <?php echo e($body); ?>

                            </div>

                            <?php if($message->attachments && $message->attachments->count()): ?>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <?php $__currentLoopData = $message->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $filePath = $att->file_path ?? $att->path ?? null;
                                            $fileName = $att->original_name ?? basename((string)$filePath);
                                        ?>
                                        <?php if($filePath): ?>
                                            <a href="<?php echo e(route('ticket-attachments.download', $att)); ?>"
                                            class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                                <?php echo e($fileName); ?>

                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center text-[12px] text-gray-500 dark:text-gray-400 py-10">
                            No messages yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/support/tickets/show.blade.php ENDPATH**/ ?>
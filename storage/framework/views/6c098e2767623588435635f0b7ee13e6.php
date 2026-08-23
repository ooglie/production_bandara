<?php $__env->startSection('title', 'Open support ticket'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $backUrl = \Illuminate\Support\Facades\Route::has('tickets.index') ? route('tickets.index') : url()->previous();
    $storeUrl = \Illuminate\Support\Facades\Route::has('tickets.store') ? route('tickets.store') : '#';
?>

<div class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Open a support ticket</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Tell us what you need help with — we’ll get back to you as soon as possible.
            </p>
        </div>

        <a href="<?php echo e($backUrl); ?>" class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
            ← Back to tickets
        </a>
    </div>

    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[12px] text-red-800 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-200">
            <div class="font-medium mb-1">Please fix the following:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e($storeUrl); ?>" enctype="multipart/form-data" class="space-y-4">
        <?php echo csrf_field(); ?>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Ticket details</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Category, subject and your message.</div>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select name="category_id"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500">
                            <option value="">Select a category…</option>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($category->id); ?>" <?php if(old('category_id') == $category->id): echo 'selected'; endif; ?>>
                                    <?php echo e($category->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                        <input type="text" name="subject" value="<?php echo e(old('subject')); ?>"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                               placeholder="e.g. Order issue, invoice request, delivery question">
                        <?php $__errorArgs = ['subject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-[11px] text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Describe your issue</label>
                    <textarea name="message" rows="6"
                              class="w-full rounded-2xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2 text-[13px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                              placeholder="Include order number, product name, and what went wrong…"><?php echo e(old('message')); ?></textarea>
                    <?php $__errorArgs = ['message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 p-4">
                    <label class="block text-[12px] font-medium text-gray-700 dark:text-gray-300 mb-1">Attachments (optional)</label>
                    <input type="file" name="attachments[]" multiple class="w-full text-[12px] text-gray-600 dark:text-gray-300">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        Up to 5MB per file.
                    </p>
                    <?php $__errorArgs = ['attachments.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-[11px] text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <a href="<?php echo e($backUrl); ?>" class="text-[12px] text-gray-500 dark:text-gray-400 hover:underline">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center rounded-xl border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-5 py-2 text-[12px] font-semibold hover:bg-gray-800 dark:hover:bg-gray-200">
                    Submit ticket
                </button>
            </div>
        </div>
    </form>

    
    <?php if(isset($tickets) && is_iterable($tickets)): ?>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">Recent tickets</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">Your latest requests.</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-[12px]">
                    <thead class="bg-gray-50 dark:bg-gray-950/40 border-b border-gray-200 dark:border-gray-800">
                    <tr class="text-left text-gray-600 dark:text-gray-300">
                        <th class="px-4 py-3 font-medium">Ticket</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Updated</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-950/30">
                            <td class="px-4 py-3">
                                <?php if(\Illuminate\Support\Facades\Route::has('tickets.show')): ?>
                                    <a href="<?php echo e(route('tickets.show', $t)); ?>" class="font-semibold text-gray-900 dark:text-gray-50 hover:underline">
                                        <?php echo e($t->ticket_number ?? ('#'.$t->id)); ?>

                                    </a>
                                <?php else: ?>
                                    <?php echo e($t->ticket_number ?? ('#'.$t->id)); ?>

                                <?php endif; ?>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400"><?php echo e($t->subject ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                <?php echo e($t->category?->name ?? '—'); ?>

                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border border-gray-300 dark:border-gray-700 px-2.5 py-1 text-[11px]">
                                    <?php echo e(ucfirst(str_replace('_',' ', (string)($t->status ?? 'open')))); ?>

                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                <?php echo e(optional($t->updated_at)->diffForHumans()); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No tickets yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(is_object($tickets) && method_exists($tickets, 'links')): ?>
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
                    <?php echo e($tickets->withQueryString()->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/customer/tickets/create.blade.php ENDPATH**/ ?>
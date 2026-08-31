<?php $__env->startSection('title', 'Chefs | Bandara Kitchen'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Bandara Kitchen</p>
                <h1 class="mt-1 text-2xl font-light tracking-tight text-slate-950 dark:text-white">Chefs</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">Manage concise Chef stories, signature dishes and the single manually selected homepage Chef.</p>
            </div>
            <a href="<?php echo e(route('admin.kitchen.chefs.create')); ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-950 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">Add Chef</a>
        </div>

        <?php if(session('status')): ?>
            <div class="mt-5 rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300" role="status"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="mt-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300" role="alert">
                <ul class="list-disc space-y-1 pl-5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="mt-6 rounded-xl border border-slate-200/80 bg-white p-5 dark:border-slate-800 dark:bg-slate-950" aria-labelledby="homepage-chef-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">Manual homepage selection</p>
                    <h2 id="homepage-chef-heading" class="mt-1 text-lg font-light text-slate-950 dark:text-white"><?php echo e($featuredChef?->display_name ?? 'No Chef selected'); ?></h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        <?php if($featuredChef): ?>
                            This Chef remains featured until an administrator selects another Chef or clears the selection.
                        <?php else: ?>
                            The homepage Chef section is hidden until a published Chef is selected.
                        <?php endif; ?>
                    </p>
                </div>
                <?php if($featuredChef): ?>
                    <form method="POST" action="<?php echo e(route('admin.kitchen.chefs.unfeature')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:border-slate-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500">Clear homepage selection</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <form method="GET" action="<?php echo e(route('admin.kitchen.chefs.index')); ?>" class="mt-6 grid gap-3 rounded-xl border border-slate-200/80 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_12rem_auto] dark:border-slate-800 dark:bg-slate-950">
            <label class="block">
                <span class="sr-only">Search Chefs</span>
                <input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Search Chef, title, organisation, dish or city" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            </label>
            <label class="block">
                <span class="sr-only">Filter by status</span>
                <select name="status" class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-slate-500 focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">All statuses</option>
                    <?php $__currentLoopData = \App\Models\Chef::STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($option); ?>" <?php if($status === $option): echo 'selected'; endif; ?>><?php echo e(ucfirst($option)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </label>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-slate-950 px-4 text-sm font-medium text-white dark:bg-white dark:text-slate-950">Filter</button>
                <?php if($search !== '' || $status !== ''): ?>
                    <a href="<?php echo e(route('admin.kitchen.chefs.index')); ?>" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm text-slate-700 dark:border-slate-700 dark:text-slate-300">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-950">
            <?php if($chefs->isNotEmpty()): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-900/70">
                            <tr>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Chef</th>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Status</th>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Signature dish</th>
                                <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Homepage</th>
                                <th class="px-4 py-3 text-right font-medium text-slate-600 dark:text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <?php $__currentLoopData = $chefs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chef): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $dishReady = filled($chef->signature_dish_name) && filled($chef->signature_dish_image_path);
                                ?>
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="flex min-w-72 items-center gap-3">
                                            <div class="h-14 w-11 shrink-0 overflow-hidden rounded-md bg-slate-100 dark:bg-slate-900">
                                                <?php if($chef->portraitUrl()): ?>
                                                    <img src="<?php echo e($chef->portraitUrl()); ?>" alt="" class="h-full w-full object-cover">
                                                <?php else: ?>
                                                    <div class="flex h-full items-center justify-center text-xs text-slate-500"><?php echo e(\App\Support\BandaraKitchen::initials($chef->display_name)); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="<?php echo e(route('admin.kitchen.chefs.edit', $chef)); ?>" class="font-medium text-slate-950 hover:underline dark:text-white"><?php echo e($chef->display_name); ?></a>
                                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-500">
                                                    <?php echo e($chef->professional_title); ?>

                                                    <?php if($chef->organisation_name): ?><span aria-hidden="true"> · </span><?php echo e($chef->organisation_name); ?><?php endif; ?>
                                                </p>
                                                <?php if($chef->city): ?><p class="text-xs text-slate-500 dark:text-slate-500"><?php echo e($chef->city); ?></p><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-md border border-slate-300 px-2.5 py-1 text-xs capitalize text-slate-700 dark:border-slate-700 dark:text-slate-300"><?php echo e($chef->status); ?></span>
                                        <?php if($chef->published_at): ?><p class="mt-2 whitespace-nowrap text-xs text-slate-500"><?php echo e($chef->published_at->format('d M Y')); ?></p><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php if($dishReady): ?>
                                            <p class="max-w-56 text-sm text-slate-700 dark:text-slate-300"><?php echo e($chef->signature_dish_name); ?></p>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">Needs dish name and photograph</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php if($chef->isHomepageFeaturedSelection()): ?>
                                            <span class="inline-flex rounded-md bg-slate-950 px-2.5 py-1 text-xs text-white dark:bg-white dark:text-slate-950">Featured</span>
                                        <?php elseif($chef->isPublished()): ?>
                                            <form method="POST" action="<?php echo e(route('admin.kitchen.chefs.feature', $chef)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button type="submit" class="text-xs font-medium text-slate-700 underline-offset-4 hover:underline dark:text-slate-300">Feature on homepage</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">Publish first</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-3 whitespace-nowrap">
                                            <?php if($chef->isPublished()): ?>
                                                <a href="<?php echo e(route('kitchen.chefs.show', $chef)); ?>" target="_blank" rel="noopener" class="text-xs text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white">View</a>
                                            <?php endif; ?>
                                            <a href="<?php echo e(route('admin.kitchen.chefs.edit', $chef)); ?>" class="text-xs font-medium text-slate-700 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white">Edit</a>
                                            <form method="POST" action="<?php echo e(route('admin.kitchen.chefs.destroy', $chef)); ?>" onsubmit="return confirm('Remove this Chef profile? The uploaded media will be retained for recovery.');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="text-xs text-red-700 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php if($chefs->hasPages()): ?><div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800"><?php echo e($chefs->links()); ?></div><?php endif; ?>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <h2 class="text-lg font-light text-slate-950 dark:text-white">No Chef profiles found.</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Create the first Chef profile or clear the current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/kitchen/chefs/index.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', 'My deliveries'); ?>

<?php $__env->startSection('body'); ?>
<?php
    $icons = [
        'active' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
        'failed' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
        'delivered' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9"/></svg>',
        'logout' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 19V5a2 2 0 0 0-2-2h-5"/></svg>',
        'phone' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.11 5.18 2 2 0 0 1 5.1 3h3a2 2 0 0 1 2 1.72c.12.9.33 1.77.62 2.6a2 2 0 0 1-.45 2.11L9 10.7a16 16 0 0 0 4.3 4.3l1.27-1.27a2 2 0 0 1 2.11-.45c.83.29 1.7.5 2.6.62A2 2 0 0 1 22 16.92z"/></svg>',
        'map' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l-6 3V6l6-3 6 3 6-3v15l-6 3-6-3z"/><path d="M9 3v15M15 6v15"/></svg>',
        'view' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
        'truck' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
        'check' => '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
    ];
?>
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-50">
    <header class="sticky top-0 z-30 border-b border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-gray-400">Bandara delivery</p>
                <h1 class="text-base font-semibold">My deliveries</h1>
            </div>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" title="Logout" aria-label="Logout" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200">
                    <?php echo $icons['logout']; ?>

                </button>
            </form>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-4 space-y-4">
        <?php if(session('status')): ?>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Active</p>
                <p class="mt-1 text-2xl font-semibold"><?php echo e(number_format($stats['active'] ?? 0)); ?></p>
            </div>
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-3">
                <p class="text-[11px] text-gray-500 dark:text-gray-400">Delivered today</p>
                <p class="mt-1 text-2xl font-semibold"><?php echo e(number_format($stats['delivered_today'] ?? 0)); ?></p>
            </div>
        </div>

        <div class="flex gap-2 overflow-x-auto pb-1 text-xs">
            <a href="<?php echo e(route('delivery.index')); ?>" title="Active deliveries" aria-label="Active deliveries"
               class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border <?php echo e($filter === 'active' ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300'); ?>">
                <?php echo $icons['active']; ?>

                <span class="sr-only">Active</span>
            </a>
            <a href="<?php echo e(route('delivery.index', ['status' => 'failed'])); ?>" title="Could not deliver" aria-label="Could not deliver"
               class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border <?php echo e($filter === 'failed' ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300'); ?>">
                <?php echo $icons['failed']; ?>

                <span class="sr-only">Could not deliver</span>
            </a>
            <a href="<?php echo e(route('delivery.index', ['status' => 'delivered'])); ?>" title="Delivered" aria-label="Delivered"
               class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border <?php echo e($filter === 'delivered' ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300'); ?>">
                <?php echo $icons['delivered']; ?>

                <span class="sr-only">Delivered</span>
            </a>
        </div>

        <div class="space-y-3">
            <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $shipping = $order->shippingAddress;
                    $phone = $shipping?->phone ?: $order->user?->phone;
                    $addressText = trim(collect([
                        $shipping?->address_line1,
                        $shipping?->address_line2,
                        trim(($shipping?->city ?? '') . ' ' . ($shipping?->pincode ?? '')),
                        $shipping?->state,
                    ])->filter()->implode(', '));
                    $isDelivered = ($order->delivery_status ?? '') === 'delivered' || ($order->status ?? '') === 'delivered';
                    $mapDestination = ($shipping?->latitude !== null && $shipping?->longitude !== null)
                        ? trim((string) $shipping->latitude) . ',' . trim((string) $shipping->longitude)
                        : $addressText;
                    $mapsUrl = $mapDestination !== '' ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($mapDestination) . '&travelmode=driving' : null;
                ?>
                <article class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 space-y-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">Order <?php echo e($order->order_number); ?></p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                <?php echo e($order->user?->name ?? 'Customer'); ?> · ₹<?php echo e(number_format((float) $order->grand_total, 2)); ?>

                            </p>
                        </div>
                        <span class="rounded-full border px-2.5 py-1 text-[10px]
                            <?php if(($order->delivery_status ?? '') === 'out_for_delivery'): ?> border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-200
                            <?php elseif(($order->delivery_status ?? '') === 'delivered'): ?> border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200
                            <?php elseif(($order->delivery_status ?? '') === 'failed'): ?> border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200
                            <?php else: ?> border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200
                            <?php endif; ?>">
                            <?php echo e(str_replace('_', ' ', ucfirst($order->delivery_status ?? 'assigned'))); ?>

                        </span>
                    </div>

                    <div class="rounded-xl bg-gray-50 dark:bg-gray-950/70 border border-gray-100 dark:border-gray-800 px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                        <p class="font-semibold text-gray-900 dark:text-gray-50"><?php echo e($shipping?->full_name ?: $order->user?->name ?: 'Customer'); ?></p>

                        <?php if($phone): ?>
                            <?php if(! $isDelivered): ?>
                                <a href="tel:<?php echo e($phone); ?>" title="Call customer" aria-label="Call customer" class="mt-1 flex items-center gap-2 text-sky-700 dark:text-sky-300">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-sky-200 bg-white dark:border-sky-900/60 dark:bg-sky-950/30"><?php echo $icons['phone']; ?></span>
                                    <span class="font-semibold"><?php echo e($phone); ?></span>
                                </a>
                            <?php else: ?>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"><?php echo $icons['phone']; ?></span>
                                    <span><?php echo e($phone); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if($addressText !== ''): ?>
                            <div class="mt-1 flex items-start gap-2">
                                <?php if($mapsUrl && ! $isDelivered): ?>
                                    <a href="<?php echo e($mapsUrl); ?>" target="_blank" rel="noopener" title="Start navigation" aria-label="Start navigation" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        <?php echo $icons['map']; ?>

                                    </a>
                                    <a href="<?php echo e($mapsUrl); ?>" target="_blank" rel="noopener" class="leading-relaxed"><?php echo e($addressText); ?></a>
                                <?php else: ?>
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"><?php echo $icons['map']; ?></span>
                                    <span class="leading-relaxed"><?php echo e($addressText); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if($order->delivery_note): ?>
                        <p class="rounded-xl bg-gray-50 dark:bg-gray-950/70 border border-gray-100 dark:border-gray-800 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                            <?php echo e($order->delivery_note); ?>

                        </p>
                    <?php endif; ?>

                    <div class="flex items-center justify-end gap-2">
                        <a href="<?php echo e(route('delivery.orders.show', $order)); ?>" title="View details" aria-label="View details" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 dark:border-gray-700 text-xs font-medium">
                            <?php echo $icons['view']; ?>

                            <span class="sr-only">View details</span>
                        </a>
                        <?php if(! $isDelivered && ($order->delivery_status ?? '') !== 'out_for_delivery'): ?>
                            <form method="POST" action="<?php echo e(route('delivery.orders.out-for-delivery', $order)); ?>" class="inline-flex">
                                <?php echo csrf_field(); ?>
                                <button type="submit" title="Out for delivery" aria-label="Out for delivery" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-sky-300 bg-sky-50 text-xs font-semibold text-sky-800 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-200">
                                    <?php echo $icons['truck']; ?>

                                    <span class="sr-only">Out for delivery</span>
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if(! $isDelivered): ?>
                            <form method="POST" action="<?php echo e(route('delivery.orders.delivered', $order)); ?>" class="inline-flex" onsubmit="return confirm('Mark this order as delivered?');">
                                <?php echo csrf_field(); ?>
                                <button type="submit" title="Delivered" aria-label="Delivered" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-emerald-300 bg-emerald-50 text-xs font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                                    <?php echo $icons['check']; ?>

                                    <span class="sr-only">Delivered</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    No deliveries found for this view.
                </div>
            <?php endif; ?>
        </div>

        <?php if(method_exists($orders, 'links')): ?>
            <?php echo e($orders->links()); ?>

        <?php endif; ?>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/delivery/index.blade.php ENDPATH**/ ?>
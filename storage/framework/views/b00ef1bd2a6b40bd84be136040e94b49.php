<?php $__env->startSection('title', 'My dashboard'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $user = auth()->user();

    $has = fn (string $r) => Route::has($r);

    $shopUrl = $has('shop.index') ? route('shop.index') : '#';
    $ordersUrl = $has('orders.index') ? route('orders.index') : '#';
    $invoicesUrl = $has('invoices.index') ? route('invoices.index') : '#';
    $wishlistUrl = $has('wishlist.index') ? route('wishlist.index') : '#';
    $addressesUrl = $has('account.addresses.index') ? route('account.addresses.index') : '#';
    $ticketsUrl = $has('tickets.index') ? route('tickets.index') : '#';
    $newsletterUrl = $has('account.newsletter') ? route('account.newsletter') : '#';
    $rewardsUrl = $has('account.rewards') ? route('account.rewards') : '#';

    $productUrl = function ($product) use ($has) {
        if (!$product) return '#';

        if ($has('product.show')) return route('product.show', $product->slug ?? $product);

        return '#';
    };

    $cartAddUrl =
        $has('cart.add') ? route('cart.add')
        : ($has('cart.store') ? route('cart.store') : null);

    $canQuickAdd = function ($product) use ($cartAddUrl) {
        if (!$product || !$cartAddUrl) {
            return false;
        }

        if (($product->type ?? 'simple') !== 'simple') {
            return false;
        }

        $manageStock = (bool) ($product->manage_stock ?? false);
        $stockValue = (float) ($product->stock_quantity ?? 0);

        if ($manageStock && $stockValue <= 0) {
            return false;
        }

        return true;
    };

    $resolveMediaUrl = function ($pathOrPaths) {
        $candidates = is_array($pathOrPaths) ? $pathOrPaths : [$pathOrPaths];

        foreach ($candidates as $candidate) {
            if (!$candidate) continue;

            $candidate = trim((string) $candidate);
            if ($candidate === '') continue;

            if (preg_match('#^https?://[^/]+(/storage/.*)$#i', $candidate, $matches)) {
                return $matches[1];
            }

            if (Str::startsWith($candidate, ['http://', 'https://'])) {
                return $candidate;
            }

            if (Str::startsWith($candidate, '/storage/')) {
                return $candidate;
            }

            if (Str::startsWith($candidate, 'storage/')) {
                return '/' . ltrim($candidate, '/');
            }

            if (Str::startsWith($candidate, 'storage/app/public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'storage/app/public/'), '/');
            }

            if (Str::startsWith($candidate, 'public/')) {
                return '/storage/' . ltrim(Str::after($candidate, 'public/'), '/');
            }

            if (Str::startsWith($candidate, '/')) {
                $publicRelative = ltrim($candidate, '/');

                if (file_exists(public_path($publicRelative))) {
                    return '/' . $publicRelative;
                }

                return $candidate;
            }

            if (file_exists(public_path($candidate))) {
                return '/' . ltrim($candidate, '/');
            }

            if (Storage::disk('public')->exists($candidate)) {
                return '/storage/' . ltrim($candidate, '/');
            }
        }

        return null;
    };

    $productImageUrl = function ($product) use ($resolveMediaUrl) {
        if (!$product) return null;

        $images = $product->images ?? collect();

        return $resolveMediaUrl([
            $product->primary_image_url ?? null,
            $product->primary_image ?? null,
            $product->image_path ?? null,
            optional($images->firstWhere('is_primary', true))->file_path,
            optional($images->first())->file_path,
        ]);
    };

    $isB2b = ($user->customer_type ?? 'b2c') === 'b2b';

    $lastOrderUrl = ($lastOrder && $has('orders.show')) ? route('orders.show', $lastOrder) : '#';
    $favoriteProductImage = $favoriteProduct ? $productImageUrl($favoriteProduct) : null;

    $favoriteOrdersCount = (int) ($favoriteProductStats['orders_count'] ?? 0);
    $favoriteTotalQty = (int) ($favoriteProductStats['total_quantity'] ?? 0);

    $offersCount = $personalOffers->count();

    $statusMeta = function (?string $status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'delivered' => [
                'label' => 'Delivered',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
            ],
            'shipped' => [
                'label' => 'Shipped',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
            ],
            'processing' => [
                'label' => 'Processing',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-900/20 dark:text-sky-300 dark:border-sky-800',
            ],
            'cancelled' => [
                'label' => 'Cancelled',
                'class' => 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800',
            ],
            default => [
                'label' => Str::headline($status ?: 'Pending'),
                'class' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            ],
        };
    };

    $lastOrderMeta = $lastOrder ? $statusMeta($lastOrder->status) : null;
    $lastOrderItems = $lastOrder && $lastOrder->items ? $lastOrder->items : collect();

    $availablePoints = (int) ($availablePoints ?? 0);
    $redemptionEnabled = (bool) ($redemptionEnabled ?? ($redeemEnabled ?? false));
    $pendingPoints = (int) ($pendingPoints ?? 0);
    $nextRewardAt = (int) ($nextRewardAt ?? 500);
    $pointsToNextReward = max($nextRewardAt - $availablePoints, 0);
    $progressPercent = $nextRewardAt > 0
        ? min(100, (int) round(($availablePoints / $nextRewardAt) * 100))
        : 0;

    $programEnabled = (bool) ($programEnabled ?? false);
    $eligibleUser = (bool) ($eligibleUser ?? false);
    $currentTier = strtolower((string) ($currentTier ?? 'silver'));
    $currentTierLabel = (string) ($currentTierLabel ?? $currentTierName ?? $tierName ?? Str::headline($currentTier));
    $tierPoints = (int) ($tierPoints ?? $annualTierPoints ?? 0);
    $nextTier = $nextTier ?? null;
    $nextTierLabel = $nextTierLabel ?? ($nextTier ? Str::headline((string) $nextTier) : null);
    $nextTierThreshold = isset($nextTierThreshold) && $nextTierThreshold !== null ? (int) $nextTierThreshold : null;
    $pointsToNextTier = (int) ($pointsToNextTier ?? $tierPointsToNext ?? 0);
    $tierProgressPercent = min(100, max(0, (float) ($tierProgressPercent ?? 0)));
    $tierRewardRatePercent = (float) ($tierRewardRatePercent ?? 1);
    $tierValidUntil = $tierValidUntil ?? null;
    $tierBadgeClass = match ($currentTier) {
        'platinum' => 'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
        'gold' => 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-200',
        default => 'border-gray-300 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-950/40 dark:text-gray-200',
    };

    $suggestedProducts = collect();

    if ($favoriteProduct) {
        $suggestedProducts->push($favoriteProduct);
    }

    foreach ($personalOffers as $offer) {
        if ($offer->product && !$suggestedProducts->contains(fn ($p) => $p->id === $offer->product->id)) {
            $suggestedProducts->push($offer->product);
        }
    }

    $suggestedProducts = $suggestedProducts->take(3)->values();

    $quickActions = collect([
        [
            'title' => 'Orders',
            'text' => 'Track orders',
            'href' => $ordersUrl,
            'icon' => '📦',
            'show' => true,
        ],
        [
            'title' => 'Invoices',
            'text' => 'Download invoices',
            'href' => $invoicesUrl,
            'icon' => '🧾',
            'show' => true,
        ],
        [
            'title' => 'Wishlist',
            'text' => 'Saved favourites',
            'href' => $wishlistUrl,
            'icon' => '💛',
            'show' => config('features.wishlist', true),
        ],
        [
            'title' => 'Addresses',
            'text' => 'Manage addresses',
            'href' => $addressesUrl,
            'icon' => '📍',
            'show' => true,
        ],
        [
            'title' => 'Support',
            'text' => 'Get help',
            'href' => $ticketsUrl,
            'icon' => '🎫',
            'show' => true,
        ],
        [
            'title' => 'Newsletter',
            'text' => 'Preferences',
            'href' => $newsletterUrl,
            'icon' => '✉️',
            'show' => config('features.newsletter', true),
        ],
        [
            'title' => 'Rewards',
            'text' => 'View points',
            'href' => $rewardsUrl,
            'icon' => '⭐',
            'show' => !$isB2b && $has('account.rewards'),
        ],
    ])->filter(fn ($item) => $item['show'])->values();
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-50">
                Hi <?php echo e($user->name); ?>, welcome back 👋
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                A compact snapshot of your orders, favourites, account pricing, and next-best actions.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <?php if(! $isB2b && $programEnabled && $eligibleUser): ?>
                <a href="<?php echo e($rewardsUrl); ?>"
                   class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-medium <?php echo e($tierBadgeClass); ?>"
                   title="<?php echo e(number_format($tierPoints)); ?> annual tier points<?php echo e($tierValidUntil ? ' • valid until '.$tierValidUntil : ''); ?>">
                    <span class="text-[10px] uppercase tracking-wide opacity-70">Tier</span>
                    <span class="font-semibold"><?php echo e($currentTierLabel); ?></span>
                    <span class="text-[10px] opacity-70"><?php echo e(rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.')); ?>% back</span>
                </a>
            <?php endif; ?>

            <a href="<?php echo e($shopUrl); ?>"
               class="inline-flex items-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                Continue shopping
            </a>

            <?php if($lastOrder): ?>
                <a href="<?php echo e($lastOrderUrl); ?>"
                   class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                    View last order
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($isB2b): ?>
        <?php echo $__env->make('dashboard.partials.b2b_quick_order', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>

    <?php if(! $isB2b): ?>
    
    <section class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                    Your rewards
                </p>
                <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    Track redeemable Bandara Credit and your current tier progress.
                </p>
            </div>

            <?php if($has('account.rewards')): ?>
                <a href="<?php echo e($rewardsUrl); ?>"
                   class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[10px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                    View rewards
                </a>
            <?php endif; ?>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Current tier</div>
                <div class="mt-1 flex items-center gap-2">
                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold <?php echo e($tierBadgeClass); ?>">
                        <?php echo e($currentTierLabel); ?>

                    </span>
                    <?php if($tierRewardRatePercent > 0): ?>
                        <span class="text-[11px] text-gray-500 dark:text-gray-400">
                            <?php echo e(rtrim(rtrim(number_format($tierRewardRatePercent, 2), '0'), '.')); ?>% back
                        </span>
                    <?php endif; ?>
                </div>
                <div class="mt-2 text-[10px] text-gray-500 dark:text-gray-400">
                    <?php echo e(number_format($tierPoints)); ?> annual tier points
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Available</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    <?php echo e(number_format($availablePoints)); ?>

                </div>
                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    <?php echo e($redemptionEnabled ? 'Ready to redeem' : 'Tracked for future redemption'); ?>

                </div>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">Pending</div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    <?php echo e(number_format($pendingPoints)); ?>

                </div>
                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    Added after order completion
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-3">
                <div class="text-[10px] uppercase tracking-wide text-gray-400">
                    <?php echo e($nextTierLabel ? 'Next tier' : 'Highest tier'); ?>

                </div>
                <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-50">
                    <?php echo e($nextTierLabel ?: $currentTierLabel); ?>

                </div>
                <div class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                    <?php if($nextTierLabel): ?>
                        <?php echo e(number_format($pointsToNextTier)); ?> tier points to go
                    <?php else: ?>
                        You are at the top tier
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between text-[10px] text-gray-500 dark:text-gray-400">
                <span><?php echo e($nextTierLabel ? 'Progress to '.$nextTierLabel : 'Top tier reached'); ?></span>
                <span>
                    <?php if($nextTierLabel): ?>
                        <?php echo e(number_format($tierPoints)); ?> / <?php echo e(number_format($nextTierThreshold ?: ($tierPoints + $pointsToNextTier))); ?> pts
                    <?php else: ?>
                        100%
                    <?php endif; ?>
                </span>
            </div>

            <div class="h-2 rounded-sm bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div class="h-full bg-gray-900 dark:bg-gray-100" style="width: <?php echo e($nextTierLabel ? min(100, max(0, $tierProgressPercent)) : 100); ?>%"></div>
            </div>

            <div class="text-[10px] text-gray-500 dark:text-gray-400">
                <?php if($nextTierLabel): ?>
                    <?php echo e(number_format($pointsToNextTier)); ?> annual tier points to <?php echo e($nextTierLabel); ?>.
                <?php else: ?>
                    You are currently at the highest Bandara Credit tier.
                <?php endif; ?>
                <?php if($tierValidUntil): ?>
                    Valid until <?php echo e(\Illuminate\Support\Carbon::parse($tierValidUntil)->format('d M Y')); ?>.
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    
    <div class="grid gap-3 lg:grid-cols-4">

        
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Last order</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        <?php echo e($lastOrder ? ('#' . ($lastOrder->order_number ?? $lastOrder->id)) : 'No orders yet'); ?>

                    </p>
                </div>

                <?php if($lastOrderMeta): ?>
                    <span class="inline-flex rounded-sm border px-2.5 py-1 text-[10px] font-medium <?php echo e($lastOrderMeta['class']); ?>">
                        <?php echo e($lastOrderMeta['label']); ?>

                    </span>
                <?php endif; ?>
            </div>

            <?php if($lastOrder): ?>
                <div class="mt-3 space-y-2">
                    <p class="text-[11px] text-gray-600 dark:text-gray-300">
                        Placed <?php echo e(optional($lastOrder->placed_at ?? $lastOrder->created_at)->format('d M Y, H:i')); ?>

                    </p>

                    <p class="text-[11px] font-medium text-gray-900 dark:text-gray-50">
                        ₹<?php echo e(number_format($lastOrder->grand_total ?? 0, 2)); ?>

                    </p>

                    <?php if($lastOrderItems->isNotEmpty()): ?>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-2">
                            <?php echo e($lastOrderItems->take(2)->pluck('product_name')->join(', ')); ?>

                            <?php if($lastOrderItems->count() > 2): ?>
                                and <?php echo e($lastOrderItems->count() - 2); ?> more
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <a href="<?php echo e($lastOrderUrl); ?>"
                       class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                        View order
                    </a>
                </div>
            <?php else: ?>
                <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                    You haven’t placed any orders yet.
                </p>
            <?php endif; ?>
        </div>

        
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full flex flex-col">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Favourite product</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        Your most ordered item
                    </p>
                </div>
                <span class="text-xl">💛</span>
            </div>

            <?php if($favoriteProduct): ?>
                <div class="mt-3 flex items-start gap-3 flex-1">
                    <div class="h-16 w-16 overflow-hidden rounded-sm border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 shrink-0">
                        <?php if($favoriteProductImage): ?>
                            <img
                                src="<?php echo e($favoriteProductImage); ?>"
                                alt="<?php echo e($favoriteProduct->name); ?>"
                                class="h-full w-full object-cover"
                            >
                        <?php else: ?>
                            <div class="h-full w-full flex items-center justify-center text-xl">❄️</div>
                        <?php endif; ?>
                    </div>

                    <div class="min-w-0 flex-1 flex flex-col">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-50 line-clamp-2">
                            <?php echo e($favoriteProduct->name); ?>

                        </p>

                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            <?php echo e($favoriteOrdersCount); ?> <?php echo e(Str::plural('order', $favoriteOrdersCount)); ?> • <?php echo e($favoriteTotalQty); ?> units
                        </p>

                        <div class="mt-auto pt-3">
                            <div class="flex flex-wrap gap-2">
                                <?php if($canQuickAdd($favoriteProduct)): ?>
                                    <form method="POST" action="<?php echo e($cartAddUrl); ?>" class="flex-1 min-w-[120px]">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo e($favoriteProduct->id); ?>">
                                        <input type="hidden" name="quantity" value="1">

                                        <button
                                            type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                            Buy again
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="<?php echo e($productUrl($favoriteProduct)); ?>"
                                   class="inline-flex flex-1 min-w-[120px] items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-[11px] text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    View product
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                    Once you order more, your most loved item will show here.
                </p>
            <?php endif; ?>
        </div>

        
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Offers for you</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        <?php echo e($offersCount); ?> available
                    </p>
                </div>
                <span class="text-xl">✨</span>
            </div>

            <?php if($personalOffers->isNotEmpty()): ?>
                <div class="mt-3 space-y-2">
                    <?php $__currentLoopData = $personalOffers->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $offer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950/40 px-3 py-2">
                            <p class="text-[11px] font-medium text-gray-900 dark:text-gray-50 line-clamp-1">
                                <?php echo e(optional($offer->product)->name ?? 'Special offer'); ?>

                            </p>
                            <p class="mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                Tailored for your next order
                            </p>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <p class="mt-3 text-[11px] text-gray-500 dark:text-gray-400">
                    We’ll surface personalised offers here as your history grows.
                </p>
            <?php endif; ?>
        </div>

        
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 h-full">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Account</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-50">
                        <?php echo e($isB2b ? 'B2B customer' : 'B2C customer'); ?>

                    </p>
                </div>

                <span class="inline-flex rounded-sm px-2.5 py-1 text-[10px] font-medium <?php echo e($isB2b ? 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'); ?>">
                    <?php echo e($isB2b ? 'Business' : 'Retail'); ?>

                </span>
            </div>

            <div class="mt-3 space-y-2 text-[11px] text-gray-600 dark:text-gray-300">
                <a href="<?php echo e($invoicesUrl); ?>" class="block hover:underline">View invoices</a>
                <a href="<?php echo e($addressesUrl); ?>" class="block hover:underline">Manage addresses</a>
                <a href="<?php echo e($ticketsUrl); ?>" class="block hover:underline">Support tickets</a>
                <?php if($has('account.newsletter')): ?>
                    <a href="<?php echo e($newsletterUrl); ?>" class="block hover:underline">Newsletter preferences</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <?php if($suggestedProducts->isNotEmpty()): ?>
        <section class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-gray-400">Buy again</p>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                        Quick picks for your next order
                    </h2>
                </div>

                <a href="<?php echo e($shopUrl); ?>"
                   class="text-[11px] font-medium text-gray-700 dark:text-gray-200 hover:underline">
                    Browse all
                </a>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <?php $__currentLoopData = $suggestedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $productThumb = $productImageUrl($product);
                    ?>

                    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                        <div class="relative h-36 overflow-hidden bg-gray-100 dark:bg-gray-800">
                            <?php if($productThumb): ?>
                                <img
                                    src="<?php echo e($productThumb); ?>"
                                    alt="<?php echo e($product->name); ?>"
                                    class="h-full w-full object-cover"
                                >
                            <?php else: ?>
                                <div class="h-full w-full flex items-center justify-center text-3xl">🛒</div>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 space-y-2">
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50 line-clamp-2">
                                <?php echo e($product->name); ?>

                            </div>

                            <?php if(!empty($product->short_description)): ?>
                                <div class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2">
                                    <?php echo e($product->short_description); ?>

                                </div>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-2 pt-2">
                                <?php if($canQuickAdd($product)): ?>
                                    <form method="POST" action="<?php echo e($cartAddUrl); ?>" class="flex-1">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                                        <input type="hidden" name="quantity" value="1">

                                        <button type="submit"
                                                class="w-full inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-2 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200">
                                            Add again
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="<?php echo e($productUrl($product)); ?>"
                                   class="inline-flex items-center justify-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
    <?php endif; ?>

    
    <section class="space-y-3">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-gray-400">Quick actions</p>
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-50">
                Manage your account faster
            </h2>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php $__currentLoopData = $quickActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($item['href']); ?>"
                   class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-4 hover:border-gray-300 dark:hover:border-gray-700 transition">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                <?php echo e($item['title']); ?>

                            </div>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                <?php echo e($item['text']); ?>

                            </div>
                        </div>

                        <span class="text-lg"><?php echo e($item['icon']); ?></span>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/dashboard/customer.blade.php ENDPATH**/ ?>
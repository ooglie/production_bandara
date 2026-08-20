<?php $__env->startSection('title', config('app.name') . ' - Shop'); ?>

<?php $__env->startSection('content'); ?>
<?php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Route;

    $has = fn(string $r) => Route::has($r);

    $q    = request('q', '');
    $sort = request('sort', '');

    $rawCategories = request()->input('category', []);
    if (!is_array($rawCategories)) {
        $rawCategories = filled($rawCategories)
            ? explode(',', (string) $rawCategories)
            : [];
    }

    $selectedCategoryIds = collect($rawCategories)
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->unique()
        ->values()
        ->all();

    $selectedCategories = collect($categories ?? [])
        ->filter(fn ($cat) => in_array((string) $cat->id, $selectedCategoryIds, true))
        ->values();

    $cartAddUrl =
        $has('cart.add') ? route('cart.add')
        : ($has('cart.store') ? route('cart.store') : null);

    $cartUrl = $has('cart.index') ? route('cart.index') : null;

    $wishlistToggleUrl = $has('wishlist.store') ? route('wishlist.store') : null;

    $wishlistUrl = $has('wishlist.index') ? route('wishlist.index') : null;

    $loginUrl = $has('login') ? route('login') : null;

    // Helper to build URLs while preserving current query params
    $link = function(array $add = [], array $remove = []) {
        $query = request()->query();
        unset($query['page']); // always reset pagination on changes

        foreach ($remove as $k) {
            unset($query[$k]);
        }

        foreach ($add as $k => $v) {
            if ($v === null || $v === '' || (is_array($v) && count($v) === 0)) {
                unset($query[$k]);
            } else {
                $query[$k] = $v;
            }
        }

        $url = url()->current();
        $qs = http_build_query($query);

        return $qs ? ($url . '?' . $qs) : $url;
    };

    $shown = is_countable($products) ? $products->count() : 0;
    $total = (is_object($products) && method_exists($products, 'total')) ? (int) $products->total() : $shown;
    $singleCard = $shown === 1;

    $sortLabel = match($sort) {
        'price_asc'  => 'Lowest price',
        'price_desc' => 'Highest price',
        default      => 'Newest',
    };

    // Flag emoji from ISO alpha-2 country code (e.g. IN -> 🇮🇳)
    $flagEmoji = function (?string $code) {
        if (!function_exists('mb_convert_encoding')) return null;

        $code = strtoupper(trim((string) $code));
        if (!preg_match('/^[A-Z]{2}$/', $code)) return null;

        $a = 127397 + ord($code[0]);
        $b = 127397 + ord($code[1]);

        return mb_convert_encoding("&#{$a};&#{$b};", 'UTF-8', 'HTML-ENTITIES');
    };
?>

<div class="max-w-6xl mx-auto px-4 py-6 space-y-4">

    
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">
                <?php echo e(filled($q) ? 'Search results' : 'Shop'); ?>

            </h1>

            <?php if(filled($q)): ?>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Results for <span class="font-medium text-gray-800 dark:text-gray-200">“<?php echo e($q); ?>”</span>
                </p>
            <?php endif; ?>
            <div>
                
                
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                
                <?php if($total > 0): ?>
                    <span class="ml-1">Showing products <?php echo e($shown); ?> of <?php echo e($total); ?>.</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if($total > 0): ?>
            <div class="inline-flex items-center rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-1.5 text-[11px]">
                <span class="text-gray-500 dark:text-gray-400">Sorted by</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-gray-50"><?php echo e($sortLabel); ?></span>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-3 py-3 space-y-3">

        
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <a href="<?php echo e($link(['category' => null])); ?>"
               class="shrink-0 inline-flex items-center rounded-sm border px-3 py-1 text-[11px]
                      <?php echo e(empty($selectedCategoryIds)
                            ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'); ?>">
                All
            </a>

            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $id = (string) $category->id;
                    $active = in_array($id, $selectedCategoryIds, true);

                    $nextCategories = $selectedCategoryIds;

                    if ($active) {
                        $nextCategories = array_values(array_filter(
                            $nextCategories,
                            fn ($v) => (string) $v !== $id
                        ));
                    } else {
                        $nextCategories[] = $id;
                    }
                ?>

                <a href="<?php echo e($link(['category' => $nextCategories])); ?>"
                   class="shrink-0 inline-flex items-center rounded-sm border px-3 py-1 text-[11px]
                          <?php echo e($active
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'); ?>">
                    <?php echo e($category->name); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2">

            
            <form method="GET" action="<?php echo e(url()->current()); ?>" class="w-full lg:max-w-md">
                <?php $__currentLoopData = $selectedCategoryIds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $selectedCategoryId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <input type="hidden" name="category[]" value="<?php echo e($selectedCategoryId); ?>">
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if(!empty($sort)): ?>
                    <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
                <?php endif; ?>

                <div class="relative">
                    <input
                        type="search"
                        name="q"
                        value="<?php echo e($q); ?>"
                        placeholder="Search products…"
                        class="w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-4 py-2 pr-10 text-[12px] focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
                    >

                    <?php if(!empty($q)): ?>
                        <a href="<?php echo e($link(['q' => null])); ?>"
                           class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                           title="Clear search"
                           aria-label="Clear search">
                            ✕
                        </a>
                    <?php endif; ?>
                </div>

                <button type="submit" class="sr-only">Search</button>
            </form>

            
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px] text-gray-500 dark:text-gray-400 hidden lg:inline">
                    Sort:
                </span>

                <a href="<?php echo e($link(['sort' => null])); ?>"
                   class="inline-flex items-center rounded-sm border px-3 py-2 text-[11px]
                          <?php echo e(empty($sort)
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'); ?>">
                    Newest
                </a>

                <a href="<?php echo e($link(['sort' => 'price_asc'])); ?>"
                   class="inline-flex items-center rounded-sm border px-3 py-2 text-[11px]
                          <?php echo e($sort === 'price_asc'
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'); ?>">
                    Lowest price
                </a>

                <a href="<?php echo e($link(['sort' => 'price_desc'])); ?>"
                   class="inline-flex items-center rounded-sm border px-3 py-2 text-[11px]
                          <?php echo e($sort === 'price_desc'
                                ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'); ?>">
                    Highest price
                </a>

                <?php if(!empty($q) || !empty($selectedCategoryIds) || !empty($sort)): ?>
                    <a href="<?php echo e($link([], ['q','category','sort'])); ?>"
                       class="inline-flex items-center rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-2 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if(!empty($q) || !empty($selectedCategoryIds) || !empty($sort)): ?>
            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-800">
                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                    Active Category:
                </span>

                <?php if(!empty($q)): ?>
                    <a href="<?php echo e($link(['q' => null])); ?>"
                       class="inline-flex items-center gap-2 rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Search: <?php echo e($q); ?>

                        <span class="text-gray-400">✕</span>
                    </a>
                <?php endif; ?>

                <?php $__currentLoopData = $selectedCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $selectedCategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $removeCategoryIds = array_values(array_filter(
                            $selectedCategoryIds,
                            fn ($id) => (string) $id !== (string) $selectedCategory->id
                        ));
                    ?>

                    <a href="<?php echo e($link(['category' => $removeCategoryIds])); ?>"
                       class="inline-flex items-center gap-2 rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <?php echo e($selectedCategory->name); ?>

                        <span class="text-gray-400">✕</span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if(!empty($sort)): ?>
                    <a href="<?php echo e($link(['sort' => null])); ?>"
                       class="inline-flex items-center gap-2 rounded-sm border border-gray-300 dark:border-gray-700 px-3 py-1 text-[11px] text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Sort: <?php echo e($sortLabel); ?>

                        <span class="text-gray-400">✕</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    
    
    <?php if($products->isEmpty()): ?>
        <div class="rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 py-6">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                No products found. Try changing categories, clearing filters, or add products in admin.
            </p>
        </div>
    <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 <?php echo e($singleCard ? 'justify-items-start' : ''); ?>">
            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo $__env->make('partials.home_cards.product_card', [
                    'product' => $product,
                    'cartAddUrl' => $cartAddUrl,
                    'wishlistToggleUrl' => $wishlistToggleUrl,
                    'wishlistUrl' => $wishlistUrl,
                    'loginUrl' => $loginUrl,
                    'singleCard' => $singleCard,
                    'flagEmoji' => $flagEmoji,
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-4">
            <?php echo e($products->links()); ?>

        </div>
    <?php endif; ?>

</div>

<?php if(\Illuminate\Support\Facades\Route::has('product.variants.options')): ?>
    <?php echo $__env->make('home.sections.product-card-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/shop/index.blade.php ENDPATH**/ ?>
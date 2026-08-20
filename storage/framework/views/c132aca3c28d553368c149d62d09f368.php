<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'mobile' => false,
    'tablet' => false,
    'placeholder' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'mobile' => false,
    'tablet' => false,
    'placeholder' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $searchValue = request()->routeIs('shop.*')
        ? trim((string) request()->query('q', ''))
        : '';

    $resolvedPlaceholder = $placeholder ?: 'Search products';

    $formClass = $mobile
        ? 'mx-auto w-full max-w-[360px]'
        : ($tablet ? 'mx-auto w-full max-w-[360px]' : 'w-full min-w-0');

    $shopCategoryFilters = request()->routeIs('shop.*')
        ? collect((array) request()->query('category', []))->filter()->values()->all()
        : [];
    $shopSort = request()->routeIs('shop.*') ? trim((string) request()->query('sort', '')) : '';

    $clearQuery = request()->routeIs('shop.*') ? request()->query() : [];
    unset($clearQuery['q'], $clearQuery['page']);
    $clearUrl = route('shop.index', $clearQuery);
?>

<form method="GET" action="<?php echo e(route('shop.index')); ?>" role="search" <?php echo e($attributes->class([$formClass])); ?>>
    <?php $__currentLoopData = $shopCategoryFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <input type="hidden" name="category[]" value="<?php echo e($categoryId); ?>">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if($shopSort !== ''): ?>
        <input type="hidden" name="sort" value="<?php echo e($shopSort); ?>">
    <?php endif; ?>
    <label for="<?php echo e($mobile ? 'mobile-storefront-search' : ($tablet ? 'tablet-storefront-search' : 'desktop-storefront-search')); ?>" class="sr-only">
        Search products
    </label>

    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400 dark:text-gray-500" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="6.5"></circle>
                <path stroke-linecap="round" d="m16 16 4 4"></path>
            </svg>
        </span>

        <input
            id="<?php echo e($mobile ? 'mobile-storefront-search' : ($tablet ? 'tablet-storefront-search' : 'desktop-storefront-search')); ?>"
            type="search"
            name="q"
            value="<?php echo e($searchValue); ?>"
            placeholder="<?php echo e($resolvedPlaceholder); ?>"
            enterkeyhint="search"
            autocomplete="off"
            class="h-8 w-full rounded-sm border border-gray-300 bg-white pl-8 pr-14 text-[11px] text-gray-900 placeholder:text-gray-400 focus:border-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:placeholder:text-gray-500 dark:focus:border-gray-600 dark:focus:ring-gray-700"
        >

        <div class="absolute inset-y-0 right-0 flex items-center">
            <?php if($searchValue !== ''): ?>
                <a
                    href="<?php echo e($clearUrl); ?>"
                    class="inline-flex h-full w-7 items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                    aria-label="Clear product search"
                    title="Clear search"
                >
                    <span aria-hidden="true">&times;</span>
                </a>
            <?php endif; ?>

            <button
                type="submit"
                class="inline-flex h-full w-7 items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                aria-label="Search products"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="6.5"></circle>
                    <path stroke-linecap="round" d="m16 16 4 4"></path>
                </svg>
            </button>
        </div>
    </div>
</form>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/components/storefront/search-bar.blade.php ENDPATH**/ ?>
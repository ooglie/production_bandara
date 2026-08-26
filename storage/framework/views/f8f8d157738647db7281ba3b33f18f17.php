<?php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $wishlistCount = $wishlistCount ?? 0;
    $cartCount = $cartCount ?? 0;

    $user = Auth::user();
    $isB2bCustomer = $user && (($user->customer_type ?? 'b2c') === 'b2b');
    $customerDashboardUrl = Route::has('account.dashboard') ? route('account.dashboard') : route('dashboard.customer');
    $avatarInitials = 'U';
    $avatarUrl = null;

    if ($user) {
        $source = trim((string) ($user->name ?: $user->email ?: 'U'));

        $avatarInitials = collect(preg_split('/\s+/', $source))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        $avatarInitials = $avatarInitials !== '' ? $avatarInitials : 'U';

        $avatarCandidate = trim((string) ($user->avatar_url ?? $user->avatar_path ?? ''));

        if ($avatarCandidate !== '') {
            if (Str::startsWith($avatarCandidate, ['http://', 'https://', '//', 'data:'])) {
                $avatarUrl = $avatarCandidate;
            } else {
                $normalized = ltrim($avatarCandidate, '/');
                $avatarUrl = Str::startsWith($normalized, 'storage/')
                    ? '/' . $normalized
                    : '/storage/' . $normalized;
            }
        }
    }

    $languageOptions = [
        'en' => ['label' => 'English', 'flag' => '🇺🇸'],
        'th' => ['label' => 'Thai',    'flag' => '🇹🇭'],
        'ko' => ['label' => 'Korean',  'flag' => '🇰🇷'],
        'ja' => ['label' => 'Japanese','flag' => '🇯🇵'],
        'fr' => ['label' => 'French',  'flag' => '🇫🇷'],
        'es' => ['label' => 'Spanish', 'flag' => '🇪🇸'],
        'de' => ['label' => 'German',  'flag' => '🇩🇪'],
        'ru' => ['label' => 'Russian', 'flag' => '🇷🇺'],
        'hi' => ['label' => 'Hindi',   'flag' => '🇮🇳'],
        'mr' => ['label' => 'Marathi', 'flag' => '🇮🇳'],
        'ta' => ['label' => 'Tamil',   'flag' => '🇮🇳'],
        'bn' => ['label' => 'Bengali', 'flag' => '🇮🇳'],
        'gu' => ['label' => 'Gujarati','flag' => '🇮🇳'],
        'pa' => ['label' => 'Punjabi', 'flag' => '🇮🇳'],
        'ml' => ['label' => 'Malayalam','flag' => '🇮🇳'],
        'te' => ['label' => 'Telugu',  'flag' => '🇮🇳'],
        'kn' => ['label' => 'Kannada', 'flag' => '🇮🇳'],
    ];

    $sourceLanguage = 'en';

    $googTrans = request()->cookie('googtrans') ?? ($_COOKIE['googtrans'] ?? null);
    $currentLang = $sourceLanguage;

    if (is_string($googTrans) && preg_match('#^/[^/]+/([^/]+)$#', $googTrans, $m)) {
        $candidate = trim($m[1]);
        if (array_key_exists($candidate, $languageOptions)) {
            $currentLang = $candidate;
        }
    }

    $currentLanguage = $languageOptions[$currentLang] ?? $languageOptions[$sourceLanguage];
    $customerHomeUrl = route('home');
    $wishlistUrl = Route::has('wishlist.index') ? route('wishlist.index') : null;
    $cartUrl = Route::has('cart.index') ? route('cart.index') : null;
?>

<nav class="hidden md:block fixed inset-x-0 top-0 z-50 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-14 flex items-center justify-between gap-3">
            
            <div class="flex items-center gap-2 min-w-0">
                <a href="<?php echo e($customerHomeUrl); ?>" class="flex items-center gap-2 min-w-0">
                    <span class="inline-flex h-15 w-15 shrink-0 items-center justify-center rounded-sm dark:border-gray-700 text-[11px] font-semibold text-gray-900 dark:text-gray-50">
                        <img
                            src="<?php echo e(asset('storage/images/logo-bandara.png')); ?>"
                            alt="Bandara Logo"
                            class="h-full w-full invert-0 dark:invert"
                        >
                    </span>

                    <span class="hidden sm:flex items-center gap-2 min-w-0">
                        <span class="font-semibold text-gray-900 dark:text-gray-50 text-sm truncate">
                            <span class="text-gray-500 dark:text-gray-400">Bandara </span><br>
                                <span class="text-gray-400 dark:text-gray-500"> bhāṇḍāra</span>
                        </span>
                        
                    </span>
                </a>
            </div>

            
            <div class="hidden md:flex shrink-0 items-center gap-5 text-[11px]">
                <a href="<?php echo e(route('shop.index')); ?>"
                   class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-50">
                    Shop
                </a>

                <a href="<?php echo e(route('orders.index')); ?>"
                   class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-50">
                    Orders
                </a>

                <?php if(config('features.wishlist', true) && $wishlistUrl): ?>
                    <a href="<?php echo e($wishlistUrl); ?>"
                       class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-50">
                        Wishlist
                    </a>
                <?php endif; ?>

                <a href="<?php echo e(route('tickets.index')); ?>"
                   class="text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-gray-50">
                    Support
                </a>
            </div>

            
            <div class="hidden xl:block min-w-0 flex-1 max-w-[300px] px-1">
                <?php if (isset($component)) { $__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.storefront.search-bar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('storefront.search-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6)): ?>
<?php $attributes = $__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6; ?>
<?php unset($__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6)): ?>
<?php $component = $__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6; ?>
<?php unset($__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6); ?>
<?php endif; ?>
            </div>

            
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <?php if(config('features.wishlist', true) && $wishlistUrl): ?>
                    <a href="<?php echo e($wishlistUrl); ?>"
                       class="relative hidden md:inline-flex items-center justify-center h-8 w-8 rounded-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                       aria-label="Wishlist">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                             class="h-4 w-4 text-gray-700 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 8.25c0-2.485-2.013-4.5-4.5-4.5-1.657 0-3.09.896-3.875 2.227C11.84 4.646 10.407 3.75 8.75 3.75 6.263 3.75 4.25 5.765 4.25 8.25c0 7.063 7.25 9.75 7.25 9.75s7.25-2.687 7.25-9.75z" />
                        </svg>
                        <?php if($wishlistCount > 0): ?>
                            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-1.5 py-0.5 text-[9px] font-semibold">
                                <?php echo e($wishlistCount > 99 ? '99+' : $wishlistCount); ?>

                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if($cartUrl): ?>
                <a href="<?php echo e($cartUrl); ?>"
                   class="relative hidden md:inline-flex items-center justify-center h-8 w-8 rounded-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                   aria-label="Cart">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         class="h-4 w-4 text-gray-700 dark:text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 3.75h1.5l1.5 12.75h12.75l1.5-9.75H6.75" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M10.5 20.25a1.125 1.125 0 11-2.25 0 1.125 1.125 0 012.25 0zM18 20.25a1.125 1.125 0 11-2.25 0 1.125 1.125 0 012.25 0z" />
                    </svg>
                    <?php if($cartCount > 0): ?>
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center rounded-sm bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-1.5 py-0.5 text-[9px] font-semibold">
                            <?php echo e($cartCount > 99 ? '99+' : $cartCount); ?>

                        </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                
                <div class="relative language-switcher" data-language-menu>
                    <button
                        type="button"
                        id="customer-language-toggle"
                        class="lang-btn inline-flex items-center justify-center gap-1.5 h-8 min-w-[38px] rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800"
                        aria-label="Change language"
                        aria-expanded="false"
                        aria-controls="customer-language-menu"
                    >
                        <span class="text-[13px] leading-none"><?php echo e($currentLanguage['flag']); ?></span>

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                             class="h-3 w-3 text-gray-400" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <div
                        id="customer-language-menu"
                        class="lang-dropdown hidden absolute right-0 top-full mt-2 w-44 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg z-50 overflow-hidden text-[11px] p-1"
                    >
                        <?php $__currentLoopData = $languageOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button
                                type="button"
                                onclick="translateLanguage('<?php echo e($code); ?>')"
                                class="flex w-full items-center justify-between rounded-sm px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800 <?php echo e($currentLang === $code ? 'text-gray-900 dark:text-gray-50 font-medium' : 'text-gray-700 dark:text-gray-200'); ?>"
                            >
                                <span>
                                    <span class="mr-2"><?php echo e($language['flag']); ?></span><?php echo e($language['label']); ?>

                                </span>

                                <?php if($currentLang === $code): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                         class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.2 7.2a1 1 0 01-1.414 0l-3.2-3.2a1 1 0 111.414-1.42l2.493 2.493 6.493-6.493a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="if (typeof toggleTheme === 'function') { toggleTheme(); }"
                    class="inline-flex items-center justify-center h-8 w-8 rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800"
                    aria-label="Toggle dark mode"
                >
                    <span id="theme-icon-light" class="hidden dark:inline">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-gray-700 dark:text-gray-200"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M21.752 15.002A9 9 0 0 1 9 2.248a9 9 0 1 0 12.752 12.754Z"/>
                        </svg>
                    </span>

                    <span id="theme-icon-dark" class="inline dark:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-gray-700 dark:text-gray-200"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor">
                            <path stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M12 3v2.25M18.364 5.636l-1.59 1.59M21 12h-2.25M18.364 18.364l-1.59-1.59M12 18.75V21M7.226 16.774l-1.59 1.59M5.25 12H3M7.226 7.226l-1.59-1.59M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/>
                        </svg>
                    </span>
                </button>

                <div class="relative hidden md:flex items-center" data-user-menu>
                    <button
                        type="button"
                        id="customer-account-menu-toggle"
                        data-user-menu-toggle
                        class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-[11px] font-semibold leading-none text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800"
                        aria-label="Account menu"
                        aria-expanded="false"
                        aria-controls="customer-account-menu"
                    >
                        <?php if(auth()->guard()->check()): ?>
                            <?php if($avatarUrl): ?>
                                <img
                                    src="<?php echo e($avatarUrl); ?>"
                                    alt="<?php echo e($user->name); ?>"
                                    class="block h-full w-full object-cover object-center"
                                >
                            <?php else: ?>
                                <span class="flex h-full w-full items-center justify-center bg-gray-900 text-white leading-none dark:bg-gray-100 dark:text-gray-900">
                                    <?php echo e($avatarInitials); ?>

                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                class="block h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 12a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 19.5a5.25 5.25 0 0110.5 0" />
                            </svg>
                        <?php endif; ?>
                    </button>

                    <div
                        id="customer-account-menu"
                        data-user-menu-panel
                        class="hidden absolute right-0 top-full mt-2 w-56 rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg py-1 text-[11px] z-50 origin-top-right"
                    >
                        <?php if(auth()->guard()->check()): ?>
                            <div class="px-3 py-3 border-b border-gray-100 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 overflow-hidden rounded-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shrink-0">
                                        <?php if($avatarUrl): ?>
                                            <img
                                                src="<?php echo e($avatarUrl); ?>"
                                                alt="<?php echo e($user->name); ?>"
                                                class="block h-full w-full object-cover object-center"
                                            >
                                        <?php else: ?>
                                            <div class="flex h-full w-full items-center justify-center bg-gray-900 text-[11px] font-semibold leading-none text-white dark:bg-gray-100 dark:text-gray-900">
                                                <?php echo e($avatarInitials); ?>

                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="text-xs font-medium text-gray-900 dark:text-gray-50 truncate">
                                            <?php echo e($user->name ?: 'Customer'); ?>

                                        </div>
                                        <div class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                                            <?php echo e($user->email); ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="px-3 pb-1 pt-2 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                            Navigation
                        </div>

                        <a href="<?php echo e(route('shop.index')); ?>"
                           class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                            Shop
                        </a>

                        <a href="<?php echo e(route('orders.index')); ?>"
                           class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                            Orders
                        </a>

                        <?php if(config('features.wishlist', true) && $wishlistUrl): ?>
                            <a href="<?php echo e($wishlistUrl); ?>"
                               class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Wishlist
                            </a>
                        <?php endif; ?>

                        <?php if($cartUrl): ?>
                            <a href="<?php echo e($cartUrl); ?>"
                               class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Cart
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo e(route('tickets.index')); ?>"
                           class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                            Support
                        </a>

                        <div class="mt-1 border-t border-gray-100 dark:border-gray-800"></div>

                        <div class="px-3 pb-1 pt-2 text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                            Account
                        </div>

                        <?php if(auth()->guard()->check()): ?>
                            <a href="<?php echo e($customerDashboardUrl); ?>"
                               class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Dashboard
                            </a>

                            <a href="<?php echo e(route('account.profile')); ?>"
                               class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Profile
                            </a>

                            <a href="<?php echo e(url('/account/addresses')); ?>"
                               class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Addresses
                            </a>

                            <form method="POST" action="<?php echo e(route('logout')); ?>" class="border-t border-gray-100 dark:border-gray-800 mt-1">
                                <?php echo csrf_field(); ?>
                                <button type="submit"
                                        class="w-full text-left px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Logout
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?php echo e(route('login')); ?>"
                               class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                Sign in
                            </a>

                            <?php if(Route::has('register')): ?>
                                <a href="<?php echo e(route('register')); ?>"
                                   class="block px-3 py-1.5 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    Register
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="hidden md:flex xl:hidden justify-center pb-2">
            <?php if (isset($component)) { $__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.storefront.search-bar','data' => ['tablet' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('storefront.search-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tablet' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6)): ?>
<?php $attributes = $__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6; ?>
<?php unset($__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6)): ?>
<?php $component = $__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6; ?>
<?php unset($__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6); ?>
<?php endif; ?>
        </div>
    </div>
</nav>

<div id="google_translate_element" class="hidden" aria-hidden="true"></div>

<?php if (! $__env->hasRenderedOnce('3d21df14-eb08-4049-8ac4-9417ceff5d71')): $__env->markAsRenderedOnce('3d21df14-eb08-4049-8ac4-9417ceff5d71'); ?>
<script>
(function () {
    const SOURCE_LANG = <?php echo json_encode($sourceLanguage ?? 'en', 15, 512) ?>;

    function isIpv4(hostname) {
        return /^\d{1,3}(\.\d{1,3}){3}$/.test(hostname);
    }

    function registrableDomain(hostname) {
        const parts = String(hostname || '').split('.').filter(Boolean);
        if (parts.length <= 2) return hostname;

        const commonSecondLevel = ['co', 'com', 'net', 'org', 'gov', 'ac', 'edu'];
        const last = parts[parts.length - 1];
        const secondLast = parts[parts.length - 2];

        if (last.length === 2 && commonSecondLevel.includes(secondLast) && parts.length >= 3) {
            return parts.slice(-3).join('.');
        }

        return parts.slice(-2).join('.');
    }

    function candidateDomains() {
        const host = window.location.hostname;
        const candidates = new Set([null]);

        if (!host) {
            return Array.from(candidates);
        }

        candidates.add(host);

        const normalizedHost = host.startsWith('www.') ? host.slice(4) : host;
        candidates.add(normalizedHost);

        if (!isIpv4(normalizedHost) && normalizedHost !== 'localhost') {
            const root = registrableDomain(normalizedHost);
            candidates.add(root);

            const hostParts = normalizedHost.split('.');
            const rootParts = root.split('.');
            const extraLevels = hostParts.length - rootParts.length;

            for (let i = 1; i < extraLevels; i++) {
                candidates.add(hostParts.slice(i).join('.'));
            }
        }

        return Array.from(candidates);
    }

    function setRawCookie(name, value, domain) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        const domainPart = domain && !isIpv4(domain) && domain !== 'localhost'
            ? '; domain=' + domain
            : '';

        document.cookie =
            name + '=' + value +
            '; path=/' +
            '; max-age=31536000' +
            '; SameSite=Lax' +
            domainPart +
            secure;
    }

    function clearRawCookie(name, domain) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        const domainPart = domain && !isIpv4(domain) && domain !== 'localhost'
            ? '; domain=' + domain
            : '';

        document.cookie =
            name + '=' +
            '; path=/' +
            '; expires=Thu, 01 Jan 1970 00:00:00 GMT' +
            '; SameSite=Lax' +
            domainPart +
            secure;
    }

    function setGoogTrans(targetLang) {
        const value = '/' + SOURCE_LANG + '/' + targetLang;

        candidateDomains().forEach(function (domain) {
            setRawCookie('googtrans', value, domain);
        });
    }

    function clearLegacyGoogTrans() {
        candidateDomains().forEach(function (domain) {
            clearRawCookie('googtrans', domain);
            clearRawCookie('googtransopt', domain);
        });
    }

    function cleanLocationUrl() {
        const url = new URL(window.location.href);

        ['_x_tr_sl', '_x_tr_tl', '_x_tr_hl', '_x_tr_pto', '_x_tr_hist'].forEach(function (param) {
            url.searchParams.delete(param);
        });

        if (url.hash && /googtrans/i.test(url.hash)) {
            url.hash = '';
        }

        return url.toString();
    }

    window.translateLanguage = function (lang) {
        const targetLang = (!lang || lang === SOURCE_LANG) ? SOURCE_LANG : lang;

        // Remove any stale cookie state first
        clearLegacyGoogTrans();

        // Then explicitly write the desired state.
        // For English/original this becomes /en/en, which is more reliable than "clear only".
        setGoogTrans(targetLang);

        window.location.replace(cleanLocationUrl());
    };

    window.googleTranslateElementInit = function () {
        if (!window.google || !google.translate || !google.translate.TranslateElement) return;
        if (window.__customerGoogleTranslateInitDone) return;

        window.__customerGoogleTranslateInitDone = true;

        new google.translate.TranslateElement({
            pageLanguage: SOURCE_LANG,
            autoDisplay: false
        }, 'google_translate_element');
    };

    if (!window.__customerGoogleTranslateScriptAdded) {
        window.__customerGoogleTranslateScriptAdded = true;

        const script = document.createElement('script');
        script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.head.appendChild(script);
    }

    const langRoot = document.querySelector('[data-language-menu]');
    const accRoot = document.querySelector('[data-user-menu]');

    const langBtn = document.getElementById('customer-language-toggle');
    const langMenu = document.getElementById('customer-language-menu');

    const accBtn = document.getElementById('customer-account-menu-toggle');
    const accMenu = document.getElementById('customer-account-menu');

    const closeLangMenu = () => {
        if (langMenu) langMenu.classList.add('hidden');
        if (langBtn) langBtn.setAttribute('aria-expanded', 'false');
    };

    const openLangMenu = () => {
        if (langMenu) langMenu.classList.remove('hidden');
        if (langBtn) langBtn.setAttribute('aria-expanded', 'true');
    };

    const closeAccountMenu = () => {
        if (accMenu) accMenu.classList.add('hidden');
        if (accBtn) accBtn.setAttribute('aria-expanded', 'false');
    };

    const openAccountMenu = () => {
        if (accMenu) accMenu.classList.remove('hidden');
        if (accBtn) accBtn.setAttribute('aria-expanded', 'true');
    };

    if (langBtn && langMenu && !langBtn.dataset.bound) {
        langBtn.dataset.bound = 'true';

        langBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            closeAccountMenu();

            if (langMenu.classList.contains('hidden')) {
                openLangMenu();
            } else {
                closeLangMenu();
            }
        });
    }

    if (accBtn && accMenu && !accBtn.dataset.bound) {
        accBtn.dataset.bound = 'true';

        accBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            closeLangMenu();

            if (accMenu.classList.contains('hidden')) {
                openAccountMenu();
            } else {
                closeAccountMenu();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (langRoot && !langRoot.contains(e.target)) {
            closeLangMenu();
        }

        if (accRoot && !accRoot.contains(e.target)) {
            closeAccountMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLangMenu();
            closeAccountMenu();
        }
    });
})();
</script>
<?php endif; ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/nav/customer.blade.php ENDPATH**/ ?>
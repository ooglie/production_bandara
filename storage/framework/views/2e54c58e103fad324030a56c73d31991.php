<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo $__env->yieldContent('title', config('app.name')); ?></title>

    <script>
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = stored ? stored === 'dark' : prefersDark;

                document.documentElement.classList.toggle('dark', isDark);
                document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';

                window.toggleTheme = function () {
                    const nextDark = !document.documentElement.classList.contains('dark');

                    document.documentElement.classList.toggle('dark', nextDark);
                    document.documentElement.style.colorScheme = nextDark ? 'dark' : 'light';
                    localStorage.setItem('theme', nextDark ? 'dark' : 'light');
                };
            } catch (e) {
                window.toggleTheme = function () {};
            }
        })();
    </script>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <link rel="stylesheet" href="<?php echo e(asset('css/bandara-messages.css')); ?>?v=<?php echo e(file_exists(public_path('css/bandara-messages.css')) ? filemtime(public_path('css/bandara-messages.css')) : '1'); ?>">

    <?php echo $__env->yieldPushContent('head'); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100 overflow-x-hidden">


    <div class="min-h-screen flex flex-col">
        
        <div class="hidden md:block">
            <?php if(view()->exists('partials.nav.customer')): ?>
                <?php echo $__env->make('partials.nav.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php elseif(view()->exists('nav.customer')): ?>
                <?php echo $__env->make('nav.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        </div>

        
        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95 md:hidden">
            <div class="mx-auto max-w-7xl px-3 pb-2 pt-2">
                <div class="mb-2 flex h-9 items-center justify-between">
                    <a href="<?php echo e(route('home')); ?>" class="inline-flex items-center gap-2" aria-label="Bandara home">
                        <img
                            src="<?php echo e(asset('storage/images/logo-bandara.png')); ?>"
                            alt="Bandara"
                            class="h-9 w-9 object-contain invert-0 dark:invert"
                        >
                        <span class="text-[12px] font-medium text-gray-700 dark:text-gray-200">Bandara</span>
                    </a>

                    <a href="<?php echo e(route('shop.index')); ?>" class="text-[11px] text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        Shop all
                    </a>
                </div>

                <?php if (isset($component)) { $__componentOriginalf25a9d72df0c2c8cf66a91889d5172b6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf25a9d72df0c2c8cf66a91889d5172b6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.storefront.search-bar','data' => ['mobile' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('storefront.search-bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['mobile' => true]); ?>
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
        </header>

        
        <main class="flex-1 pt-0 md:pt-24 xl:pt-14 pb-20 md:pb-0">
            <?php echo $__env->make('partials.frontend.messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php if (! empty(trim($__env->yieldContent('content')))): ?>
                <?php echo $__env->yieldContent('content'); ?>
            <?php else: ?>
                <?php echo e($slot ?? ''); ?>

            <?php endif; ?>
        </main>

        
        <?php if(view()->exists('partials.footer.customer')): ?>
            <?php echo $__env->make('partials.footer.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif(view()->exists('partials.footer')): ?>
            <?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>

    
    <div class="md:hidden">
        <?php if(view()->exists('nav.customer-mobile')): ?>
            <?php echo $__env->make('nav.customer-mobile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif(view()->exists('partials.nav.customer-mobile')): ?>
            <?php echo $__env->make('partials.nav.customer-mobile', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>

    <?php echo $__env->yieldPushContent('modals'); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->yieldContent('scripts'); ?>
    <?php echo $__env->make('partials.storefront-ui-refinement-v3', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/layouts/customer.blade.php ENDPATH**/ ?>
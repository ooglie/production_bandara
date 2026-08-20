<?php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $roleName = $user?->getRoleNames()->first();
    $initial = $user ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?';

    // Keep request-scoped values in the view. Named PHP functions declared
    // inside Blade persist for the lifetime of the PHPUnit process and can be
    // redeclared when more than one company-layout page is rendered.
    $dashboardUrl = null;

    if ($user) {
        if ($user->hasRole('Customer')) {
            $dashboardUrl = route('account.dashboard');
        } elseif ($user->hasRole('Admin')) {
            $dashboardUrl = route('admin.dashboard');
        } elseif ($user->hasRole('Manager')) {
            $dashboardUrl = route('manager.dashboard');
        } elseif ($user->hasRole('Support')) {
            $dashboardUrl = route('support.dashboard');
        } elseif ($user->hasRole('Accountant') || $user->hasRole('CAAccountant')) {
            $dashboardUrl = route('accountant.dashboard');
        } elseif ($user->hasRole('Stores')) {
            $dashboardUrl = route('stores.dashboard');
        } elseif ($user->hasRole('DeliveryAgent') && \Illuminate\Support\Facades\Route::has('delivery.index')) {
            $dashboardUrl = route('delivery.index');
        } else {
            $dashboardUrl = route('home');
        }
    }
?>




<?php $__env->startSection('body'); ?>
    <?php if(auth()->check() && session()->has('impersonator_id') && \Illuminate\Support\Facades\Route::has('impersonation.stop')): ?>
        <div class="bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-100 text-[11px] px-4 py-2 flex items-center justify-between">
            <span>
                You are currently impersonating
                <strong><?php echo e(auth()->user()->name); ?></strong>.
            </span>
            <form method="POST" action="<?php echo e(route('impersonation.stop')); ?>">
                <?php echo csrf_field(); ?>
                <button
                    type="submit"
                    class="underline">
                    Stop impersonating
                </button>
            </form>
        </div>
    <?php endif; ?>


    <div class="min-h-screen flex bg-gray-50 dark:bg-gray-950">
        
        <?php echo $__env->make('partials.nav.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div class="flex-1 flex flex-col">
            

            <header class="sticky top-0 z-40 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur">
                <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
                    <div class="flex h-14 items-center justify-between gap-3">
                        

                        
                        <nav class="hidden md:flex items-center gap-4 text-[11px] text-gray-700 dark:text-gray-200">
                            
                        </nav>

                        
                        <div class="flex items-center gap-2">
                            
                            

                            
                            <button
                                type="button"
                                onclick="toggleTheme()"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
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

                            
                            <?php echo $__env->make('partials.user-menu', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                            
                            <button
                                type="button"
                                id="company-mobile-menu-toggle"
                                class="md:hidden inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 ml-1 hover:bg-gray-100 dark:hover:bg-gray-800"
                                aria-controls="company-sidebar"
                                aria-expanded="false"
                            >
                                <span class="sr-only">Open menu</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-700 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 7h16M4 12h16M4 17h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
            </main>

            <?php echo $__env->make('partials.footer.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <div
        id="company-mobile-sidebar-backdrop"
        class="hidden fixed inset-0 z-40 bg-black/40 backdrop-blur-[1px] md:hidden"
        aria-hidden="true"
    ></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('company-sidebar');
            const backdrop = document.getElementById('company-mobile-sidebar-backdrop');
            const openButton = document.getElementById('company-mobile-menu-toggle');
            const closeButton = document.getElementById('company-mobile-menu-close');

            if (!sidebar || !backdrop || !openButton) {
                return;
            }

            const mobileClasses = ['fixed', 'inset-y-0', 'left-0', 'z-50', 'flex', 'flex-col', 'shadow-2xl'];

            function openCompanyMenu() {
                sidebar.classList.add(...mobileClasses);
                sidebar.style.display = 'flex';
                backdrop.classList.remove('hidden');
                openButton.setAttribute('aria-expanded', 'true');
                document.body.classList.add('overflow-hidden');
            }

            function closeCompanyMenu() {
                sidebar.classList.remove(...mobileClasses);
                sidebar.style.display = '';
                backdrop.classList.add('hidden');
                openButton.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('overflow-hidden');
            }

            openButton.addEventListener('click', openCompanyMenu);
            backdrop.addEventListener('click', closeCompanyMenu);

            if (closeButton) {
                closeButton.addEventListener('click', closeCompanyMenu);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeCompanyMenu();
                }
            });

            window.addEventListener('resize', function () {
                if (window.matchMedia('(min-width: 768px)').matches) {
                    closeCompanyMenu();
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.base', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/layouts/company.blade.php ENDPATH**/ ?>
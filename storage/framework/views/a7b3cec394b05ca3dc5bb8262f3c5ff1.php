<?php
    /** @var \App\Models\User|null $user */
    $user = $user ?? auth()->user();
    $hasRoute = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);
    $safeRoute = fn (string $name, array $params = []) => $hasRoute($name) ? route($name, $params) : null;

    $initial = $initial ?? ($user ? mb_strtoupper(mb_substr((string) $user->name, 0, 1)) : '?');
    $roleNames = $user && method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();
    $roleName = $roleName ?? $roleNames->first();
    $hasRole = fn (string $role): bool => $user && method_exists($user, 'hasRole') && $user->hasRole($role);
    $hasAnyRole = fn (array $roles): bool => $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles);

    $staffRoles = ['Admin', 'Manager', 'Accountant', 'CAAccountant', 'Support', 'Stores', 'DeliveryAgent'];
    $isStaff = $hasAnyRole($staffRoles);

    $links = [];

    $addLink = function (string $label, ?string $url) use (&$links): void {
        if ($url) {
            $links[] = ['label' => $label, 'url' => $url];
        }
    };

    if ($user) {
        if ($hasRole('DeliveryAgent')) {
            $addLink('Delivery dashboard', $safeRoute('delivery.index'));
        } elseif ($hasRole('Admin')) {
            $addLink('Admin dashboard', $safeRoute('admin.dashboard'));
        } elseif ($hasRole('Manager')) {
            $addLink('Manager dashboard', $safeRoute('manager.dashboard'));
        } elseif ($hasRole('Stores')) {
            $addLink('Stores dashboard', $safeRoute('stores.dashboard'));
        } elseif ($hasRole('Accountant') || $hasRole('CAAccountant')) {
            $addLink('Accountant dashboard', $safeRoute('accountant.dashboard'));
        } elseif ($hasRole('Support')) {
            $addLink('Support dashboard', $safeRoute('support.dashboard'));
        } elseif ($hasRole('Customer')) {
            $addLink('Dashboard', $safeRoute('account.dashboard') ?: $safeRoute('dashboard.customer'));
        }

        if ($isStaff && ! $hasRole('DeliveryAgent')) {
            $addLink('Orders', $safeRoute('admin.orders.index'));
            $addLink('Invoices', $safeRoute('admin.invoices.index'));
            $addLink('Payment approvals', $safeRoute('admin.invoice-payment-submissions.index'));
            $addLink('Products', $safeRoute('admin.products.index'));
            $addLink('Inventory', $safeRoute('admin.inventory.lots.index'));
            $addLink('Customers', $safeRoute('admin.customers.b2c.index') ?: $safeRoute('admin.users.index'));
        } elseif ($hasRole('Customer')) {
            $addLink('Shop', $safeRoute('shop.index'));
            $addLink('My orders', $safeRoute('orders.index'));
            $addLink('Invoices', $safeRoute('invoices.index'));
            if (config('features.wishlist', true)) {
                $addLink('Wishlist', $safeRoute('wishlist.index'));
            }
        }

        if (empty($links)) {
            $addLink('Home', $safeRoute('home'));
        }
    }
?>


<?php if(auth()->guard()->guest()): ?>
    <div class="flex items-center gap-2 text-xs">
        <a href="<?php echo e(route('login')); ?>"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-gray-800 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800">
            Sign in
        </a>
        <?php if(Route::has('register')): ?>
            <a href="<?php echo e(route('register')); ?>"
               class="rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 hover:bg-gray-800 dark:hover:bg-gray-200">
                Register
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>


<?php if(auth()->guard()->check()): ?>
<div class="relative text-xs" data-user-menu>
    <button type="button"
            data-user-menu-toggle
            aria-expanded="false"
            class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-2 py-1.5 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-[11px] font-semibold text-white dark:bg-gray-100 dark:text-gray-900">
            <?php echo e($initial); ?>

        </span>
        <svg class="h-3 w-3 text-gray-600 dark:text-gray-300"
             xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M6 9l6 6 6-6"/>
        </svg>
    </button>

    <div data-user-menu-panel
     class="hidden absolute left-0 md:left-auto md:right-0 top-full mt-2
            w-[18rem] max-w-[90vw]
            max-h-[75vh] overflow-y-auto
            rounded-xl border border-gray-200
            bg-white shadow-2xl z-[200]">
        
        <div class="border-b border-gray-100 px-3 py-2" style="color:#111827 !important;">
            <div class="truncate text-[11px] font-semibold" style="color:#111827 !important;">
                <?php echo e($user->name ?: 'Signed in user'); ?>

            </div>
            <div class="truncate text-[10px]" style="color:#6b7280 !important;">
                <?php echo e($user->email); ?>

            </div>
            <?php if($roleName): ?>
                <div class="mt-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px]" style="color:#374151 !important;">
                    <?php echo e($roleName); ?>

                </div>
            <?php endif; ?>
        </div>

        
        <div class="py-1 text-[11px]" style="color:#111827 !important;">
            <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($link['url']); ?>"
                   class="block px-3 py-2 hover:bg-gray-50"
                   style="color:#111827 !important;text-decoration:none !important;">
                    <?php echo e($link['label']); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if(session()->has('impersonator_id') && $hasRoute('impersonation.stop')): ?>
                <div class="my-1 border-t border-gray-100"></div>
                <form method="POST" action="<?php echo e(route('impersonation.stop')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            class="w-full px-3 py-2 text-left hover:bg-amber-50"
                            style="color:#92400e !important;">
                        Stop impersonating
                    </button>
                </form>
            <?php endif; ?>
        </div>

        
        <div class="border-t border-gray-100 px-3 py-1.5">
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit"
                        class="w-full py-1.5 text-left text-[11px] hover:underline"
                        style="color:#dc2626 !important;">
                    Logout
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/user-menu.blade.php ENDPATH**/ ?>
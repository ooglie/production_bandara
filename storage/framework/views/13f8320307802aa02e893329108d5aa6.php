<?php
    $user = $user ?? auth()->user();
    $has = fn(string $name) => \Illuminate\Support\Facades\Route::has($name);

    // Prefer new customer modules, fall back to /admin/users if needed
    $b2cIndexUrl  = $has('admin.customers.b2c.index')
        ? route('admin.customers.b2c.index')
        : ($has('admin.users.index') ? route('admin.users.index', ['customer_type' => 'b2c']) : null);

    $b2cCreateUrl = $has('admin.customers.b2c.create')
        ? route('admin.customers.b2c.create')
        : ($has('admin.users.create') ? route('admin.users.create', ['customer_type' => 'b2c']) : null);

    $b2bIndexUrl  = $has('admin.b2b.customers.index')
        ? route('admin.b2b.customers.index')
        : ($has('admin.users.index') ? route('admin.users.index', ['customer_type' => 'b2b']) : null);

    $b2bCreateUrl = $has('admin.b2b.customers.create')
        ? route('admin.b2b.customers.create')
        : ($has('admin.users.create') ? route('admin.users.create', ['customer_type' => 'b2b']) : null);

    $b2bProductRequestsUrl = $has('admin.b2b.product-requests.index')
        ? route('admin.b2b.product-requests.index')
        : null;

    // Stores quick links
    $storesDashboardUrl = $has('admin.stores.dashboard') ? route('admin.stores.dashboard') : null;
    $vendorInvoicesUrl  = $has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index') : null;
    $inventoryLotsUrl   = $has('admin.inventory.lots.index') ? route('admin.inventory.lots.index') : null;
    $inventoryPacksUrl  = $has('admin.inventory.packs.index') ? route('admin.inventory.packs.index') : null;
    $productionUrl      = $has('admin.production.index') ? route('admin.production.index') : null;

    $isStores = $user && method_exists($user, 'hasRole') && $user->hasRole('Stores');
    $isAdmin  = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin');
    $isManager  = $user && method_exists($user, 'hasRole') && $user->hasRole('Manager');
    $isAccount  = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Accountant', 'CAAccountant']);
?>

<aside
    id="company-sidebar"
    class="hidden md:flex md:flex-col w-60 max-w-[82vw] border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900"
>
    <div class="h-14 flex items-center justify-between gap-3 px-4 border-b border-gray-200 dark:border-gray-800">
        <span class="inline-flex min-w-0 items-center gap-2">
            <span class="inline-block h-16 w-16 rounded-full dark:border-gray-700">
                <a href="<?php echo e($dashboardUrl ?? route('home')); ?>">
                    <img src="<?php echo e(asset('storage/images/logo-bandara.png')); ?>"
                         alt="Bandara Logo"
                         class="h-full w-full invert-0 dark:invert">
                </a>
            </span>

            <span class="truncate text-sm font-semibold text-gray-900 dark:text-gray-50">
                Bandara by Maytira
            </span>
        </span>

        <button
            type="button"
            id="company-mobile-menu-close"
            class="md:hidden inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
            aria-label="Close menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm text-gray-600 dark:text-gray-300">

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view customers','manage customers'])): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">
                    Customers
                </p>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check(['manage customers'])): ?>
                    <div class="mt-1">
                        <?php if($b2cIndexUrl): ?>
                            <a href="<?php echo e($b2cIndexUrl); ?>"
                               class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                B2C Customers
                            </a>
                        <?php endif; ?>

                        <?php if($b2bIndexUrl): ?>
                            <a href="<?php echo e($b2bIndexUrl); ?>"
                               class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                B2B Customers
                            </a>
                        <?php endif; ?>

                        <?php if($b2bProductRequestsUrl): ?>
                            <a href="<?php echo e($b2bProductRequestsUrl); ?>"
                               class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                B2B Product Requests
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        
        <div class="mb-4">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view orders')): ?>
            <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Sales</p>
                <?php if($has('admin.orders.index')): ?>
                    <a href="<?php echo e(route('admin.orders.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Orders
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view invoices')): ?>
                <?php if($has('admin.invoices.index')): ?>
                    <a href="<?php echo e(route('admin.invoices.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Invoices
                    </a>
                <?php endif; ?>

                <?php if($has('admin.delivery.index')): ?>
                    <a href="<?php echo e(route('admin.delivery.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Delivery & handling
                    </a>
                <?php endif; ?>
                <?php if($has('admin.invoice-payment-submissions.index')): ?>
                    <a href="<?php echo e(route('admin.invoice-payment-submissions.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Payment approvals
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage coupons')): ?>
                <?php if($has('admin.coupons.index')): ?>
                    <a href="<?php echo e(route('admin.coupons.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Coupons & Discounts
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($has('admin.reports.index')): ?>
                <a href="<?php echo e(route('admin.reports.index')); ?>"
                   class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                    Reports
                </a>
            <?php endif; ?>
        </div>


        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['manage products', 'view labels', 'manage labels'])): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Catalog</p>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage products')): ?>
                    <?php if($has('admin.hsn-codes.index')): ?>
                        <a href="<?php echo e(route('admin.hsn-codes.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            HSN Codes
                        </a>
                    <?php endif; ?>

                    <?php if($has('admin.products.index')): ?>
                        <a href="<?php echo e(route('admin.products.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            Products
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view labels', 'manage labels'])): ?>
                    <?php if($has('admin.labels.index')): ?>
                        <a href="<?php echo e(route('admin.labels.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            Product Labels
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage products')): ?>
                    <?php if($has('admin.categories.index')): ?>
                        <a href="<?php echo e(route('admin.categories.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            Categories
                        </a>
                    <?php endif; ?>

                    <?php if($has('admin.attributes.index')): ?>
                        <a href="<?php echo e(route('admin.attributes.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            Variant Options
                        </a>
                    <?php endif; ?>

                    <?php if($has('admin.variant-option-values.index')): ?>
                        <a href="<?php echo e(route('admin.variant-option-values.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            Variant Option Values
                        </a>
                    <?php endif; ?>

                    <?php if($has('admin.recipes.index')): ?>
                        <a href="<?php echo e(route('admin.recipes.index')); ?>"
                           class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                            Recipes
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view stores', 'manage stores'])): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">
                    Stores
                </p>

                
                <?php if($vendorInvoicesUrl): ?>
                    <a href="<?php echo e($vendorInvoicesUrl); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vendor Invoices
                    </a>
                <?php endif; ?>

                <?php if($inventoryLotsUrl): ?>
                    <a href="<?php echo e($inventoryLotsUrl); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Inventory Lots
                    </a>
                <?php endif; ?>

                <?php if($inventoryPacksUrl): ?>
                    <a href="<?php echo e($inventoryPacksUrl); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Transform Stock
                    </a>
                <?php endif; ?>

                <?php if($productionUrl): ?>
                    <a href="<?php echo e($productionUrl); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Production / Repack
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage vendors')): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Vendors</p>

                <?php if($isAdmin || $isManager): ?>
                <?php if($has('admin.vendors.index')): ?>
                    <a href="<?php echo e(route('admin.vendors.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Manage
                    </a>
                <?php endif; ?>
                <?php endif; ?>

                <?php if($has('admin.vendor-invoices.index')): ?>
                    <a href="<?php echo e(route('admin.vendor-invoices.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vendor Invoices
                    </a>
                <?php endif; ?>

                <?php if($isAdmin || $isManager || $isAccount): ?>
                <?php if($has('admin.vendor-payments.index')): ?>
                    <a href="<?php echo e(route('admin.vendor-payments.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vendor Payment
                    </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>


        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage tickets')): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Support</p>

                <?php if($has('admin.ticket-categories.index')): ?>
                    <a href="<?php echo e(route('admin.ticket-categories.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Ticket Categories
                    </a>
                <?php endif; ?>

                <?php if($has('admin.ticket-tags.index')): ?>
                    <a href="<?php echo e(route('admin.ticket-tags.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Ticket Tags
                    </a>
                <?php endif; ?>

                <?php if($has('support.tickets.index')): ?>
                    <a href="<?php echo e(route('support.tickets.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Tickets
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view content', 'manage content'])): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Content</p>

                <?php if($has('admin.home-sections.index')): ?>
                    <a
                        href="<?php echo e(route('admin.home-sections.index')); ?>"
                        class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                    >
                        Homepage
                    </a>
                <?php endif; ?>

                <a
                    href="<?php echo e(route('admin.announcements.index')); ?>"
                    class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Announcements
                </a>
            

                <a
                    href="<?php echo e(route('admin.product-collections.index')); ?>"
                    class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Collections
                </a>
            </div>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage marketing')): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Marketing</p>

                <?php if($has('admin.newsletter-subscribers.index')): ?>
                    <a href="<?php echo e(route('admin.newsletter-subscribers.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Newsletter
                    </a>
                <?php endif; ?>

                <?php if($has('admin.newsletter-campaigns.index')): ?>
                    <a href="<?php echo e(route('admin.newsletter-campaigns.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Campaigns
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view rewards')): ?>
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Rewards</p>

                <?php if($has('admin.rewards.index')): ?>
                    <a href="<?php echo e(route('admin.rewards.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Bandara Credit
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>



        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage users')): ?>
            <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-800">
                <?php if($has('admin.users.index')): ?>
                    <a href="<?php echo e(route('admin.users.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Manage users
                    </a>
                <?php endif; ?>

                <?php if($has('admin.roles.index')): ?>
                    <a href="<?php echo e(route('admin.roles.index')); ?>"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Roles & Permissions
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage settings')): ?>
            <div class="mt-2">
                <a href="#"
                   class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                    Settings
                </a>
            </div>
        <?php endif; ?>

    </nav>
</aside>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/nav/company.blade.php ENDPATH**/ ?>
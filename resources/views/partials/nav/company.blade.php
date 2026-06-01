@php
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
@endphp

<aside class="hidden md:flex md:flex-col w-60 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
    <div class="h-14 flex items-center px-4 border-b border-gray-200 dark:border-gray-800">
        <span class="inline-flex items-center gap-2">
            <span class="inline-block h-16 w-16 rounded-full dark:border-gray-700">
                <a href="{{ fb_dashboard_route($user) }}">
                    <img src="{{ asset('storage/images/logo-bandara.png') }}"
                         alt="Bandara Logo"
                         class="h-full w-full invert-0 dark:invert">
                </a>
            </span>

            <span class="text-sm font-semibold text-gray-900 dark:text-gray-50">
                Bandara by Maytira
            </span>
        </span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 text-sm text-gray-600 dark:text-gray-300">

        {{-- CUSTOMERS --}}
        @canany(['view customers','manage customers'])
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">
                    Customers
                </p>

                @can(['manage customers'])
                    <div class="mt-1">
                        @if($b2cIndexUrl)
                            <a href="{{ $b2cIndexUrl }}"
                               class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                B2C Customers
                            </a>
                        @endif

                        @if($b2bIndexUrl)
                            <a href="{{ $b2bIndexUrl }}"
                               class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                B2B Customers
                            </a>
                        @endif

                        @if($b2bProductRequestsUrl)
                            <a href="{{ $b2bProductRequestsUrl }}"
                               class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                                B2B Product Requests
                            </a>
                        @endif
                    </div>
                @endcan
            </div>
        @endcanany


        {{-- SALES --}}
        <div class="mb-4">
            @can('view orders')
            <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Sales</p>
                @if($has('admin.orders.index'))
                    <a href="{{ route('admin.orders.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Orders
                    </a>
                @endif
            @endcan

            @can('view invoices')
                @if($has('admin.invoices.index'))
                    <a href="{{ route('admin.invoices.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Invoices
                    </a>
                @endif
            @endcan

            @can('manage coupons')
                @if($has('admin.coupons.index'))
                    <a href="{{ route('admin.coupons.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Coupons & Discounts
                    </a>
                @endif
            @endcan
        </div>


        {{-- CATALOG --}}
        @can('manage products')
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Catalog</p>

                @if($has('admin.hsn-codes.index'))
                    <a href="{{ route('admin.hsn-codes.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        HSN Codes
                    </a>
                @endif

                @if($has('admin.products.index'))
                    <a href="{{ route('admin.products.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Products
                    </a>
                @endif

                @if($has('admin.categories.index'))
                    <a href="{{ route('admin.categories.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Categories
                    </a>
                @endif

                @if($has('admin.attributes.index'))
                    <a href="{{ route('admin.attributes.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Attributes
                    </a>
                @endif

                @if($has('admin.recipes.index'))
                    <a href="{{ route('admin.recipes.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Recipes
                    </a>
                @endif
            </div>
        @endcan

        {{-- STORES (new role) --}}
        @canany(['view stores', 'manage stores'])
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">
                    Stores
                </p>

                
                @if($vendorInvoicesUrl)
                    <a href="{{ $vendorInvoicesUrl }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vendor Invoices
                    </a>
                @endif

                @if($inventoryLotsUrl)
                    <a href="{{ $inventoryLotsUrl }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Inventory Lots
                    </a>
                @endif

                @if($inventoryPacksUrl)
                    <a href="{{ $inventoryPacksUrl }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Inventory Packs
                    </a>
                @endif

                @if($productionUrl)
                    <a href="{{ $productionUrl }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Production / Repack
                    </a>
                @endif
            </div>
        @endcanany


        {{-- VENDORS --}}
        @can('manage vendors')
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Vendors</p>

                @if($isAdmin || $isManager)
                @if($has('admin.vendors.index'))
                    <a href="{{ route('admin.vendors.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Manage
                    </a>
                @endif
                @endif

                @if($has('admin.vendor-invoices.index'))
                    <a href="{{ route('admin.vendor-invoices.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vendor Invoices
                    </a>
                @endif

                @if($isAdmin || $isManager || $isAccount)
                @if($has('admin.vendor-payments.index'))
                    <a href="{{ route('admin.vendor-payments.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Vendor Payment
                    </a>
                @endif
                @endif
            </div>
        @endcan


        {{-- SUPPORT --}}
        @can('manage tickets')
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Support</p>

                @if($has('admin.ticket-categories.index'))
                    <a href="{{ route('admin.ticket-categories.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Ticket Categories
                    </a>
                @endif

                @if($has('admin.ticket-tags.index'))
                    <a href="{{ route('admin.ticket-tags.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Ticket Tags
                    </a>
                @endif

                @if($has('support.tickets.index'))
                    <a href="{{ route('support.tickets.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Tickets
                    </a>
                @endif
            </div>
        @endcan
        
        {{-- CONTENT --}}
        @canany(['view content', 'manage content'])
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Content</p>

                <a
                    href="{{ route('admin.announcements.index') }}"
                    class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Announcements
                </a>
            

                <a
                    href="{{ route('admin.product-collections.index') }}"
                    class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Collections
                </a>
            </div>
        @endcanany

        {{-- MARKETING --}}
        @can('manage marketing')
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Marketing</p>

                @if($has('admin.newsletter-subscribers.index'))
                    <a href="{{ route('admin.newsletter-subscribers.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Newsletter
                    </a>
                @endif

                @if($has('admin.newsletter-campaigns.index'))
                    <a href="{{ route('admin.newsletter-campaigns.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Campaigns
                    </a>
                @endif
            </div>
        @endcan

        {{-- REWARDS --}}
        @can('view rewards')
            <div class="mb-4">
                <p class="text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Rewards</p>

                @if($has('admin.rewards.index'))
                    <a href="{{ route('admin.rewards.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Bandara Credit
                    </a>
                @endif
            </div>
        @endcan



        {{-- USERS (staff / everything) --}}
        @can('manage users')
            <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-800">
                @if($has('admin.users.index'))
                    <a href="{{ route('admin.users.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Manage users
                    </a>
                @endif

                @if($has('admin.roles.index'))
                    <a href="{{ route('admin.roles.index') }}"
                       class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        Roles & Permissions
                    </a>
                @endif
            </div>
        @endcan

        @can('manage settings')
            <div class="mt-2">
                <a href="#"
                   class="block px-2 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                    Settings
                </a>
            </div>
        @endcan

    </nav>
</aside>

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\Product;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\NewsletterController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Admin\ProductCollectionController;

use App\Http\Controllers\Frontend\ProductController as FrontProductController;
// use App\Http\Controllers\Frontend\PageController as FrontPageController;

use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Accountant\DashboardController as AccountantDashboardController;

use App\Http\Controllers\Customer\{
    AccountController,
    OrderController,
    CartController,
    WishlistController,
    CheckoutController,
    AddressController,
    InvoiceController as CustomerInvoiceController,
    PaymentController,
    CustomerDashboardController,
    TicketController as CustomerTicketController,
    ProfileController,
    B2BQuickOrderController,
};

use App\Http\Controllers\Support\{
    FAQController,
    TicketController as SupportTicketController,
    DashboardController as SupportDashboardController,
};

use App\Http\Controllers\Admin\{
    ProductController,
    ProductSellUnitController,
    CategoryController,
    VendorController,
    ProductVariantController,
    AttributeController,
    AttributeValueController,
    ProductImageController,
    CouponController,
    InvoiceController as AdminInvoiceController,
    PaymentController as AdminPaymentController,
    UserController as AdminUserController,
    NewsletterSubscriberController as AdminNewsletterSubscriberController,
    NewsletterCampaignController as AdminNewsletterCampaignController,
    TicketCategoryController,
    TicketTagController,
    VendorInvoiceController,
    VendorPaymentController,
    InventoryPackController,
    DashboardController as AdminDashboardController,
    HsnCodeController,
    OrderPrintController,
    B2BCustomerProductController,
    ProductVariantLookupController,
    B2BCustomerController,
    B2BCustomerMoqController,
    B2BCustomerPriceController,
    B2BProductRequestController,
    RolePermissionController,
    RecipeController,
    AnnouncementController,
    AdminBandaraCreditPreviewController,
    BandaraCreditController,
};

use App\Http\Controllers\Stores\DashboardController as StoresDashboardController;

/*
|--------------------------------------------------------------------------
| FRONTEND / CUSTOMER ROUTES (NON-LOCALIZED)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])
    ->name('home');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');

Route::get('/collections/{collection:slug}', [CollectionController::class, 'show'])
    ->name('collections.show');


// FRONTEND PRODUCT ROUTES
Route::get('/product/{product:slug}', [FrontProductController::class, 'show'])
    ->name('product.show');

Route::get('/products/{product}/variants/options', [FrontProductController::class, 'variantOptions'])
    ->name('product.variants.options');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

// Auth routes
Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// CART ROUTES
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{key}', [CartController::class, 'destroy'])->name('cart.destroy');

// CART COUPON
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// CUSTOMER
Route::middleware(['auth', 'role:Customer'])->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])
        ->name('dashboard.customer');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/dashboard/b2b/quick-add', [B2BQuickOrderController::class, 'quickAdd'])
            ->name('dashboard.b2b.quickAdd');
    });

    Route::get('/account/newsletter', [ProfileController::class, 'newsletter'])
        ->name('account.newsletter');
});

// B2B CUSTOMER COMPATIBILITY ROUTES
Route::middleware(['auth', 'role:Customer', 'verified'])
    ->prefix('b2b')
    ->name('b2b.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard.customer'))->name('dashboard');
        Route::get('/portfolio', fn () => redirect()->route('shop.index'))->name('portfolio');
        Route::get('/catalog', fn () => redirect()->route('shop.index'))->name('catalog.index');
        Route::get('/catalog/{product:slug}', fn (Product $product) => redirect()->route('product.show', $product))->name('catalog.show');

        // Keep old B2B route names as aliases while navigation is unified.
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{key}', [CartController::class, 'destroy'])->name('cart.destroy');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('/wishlist/{item}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
    });

// CUSTOMER ACCOUNT ROUTES
Route::middleware(['auth', 'role:Customer'])->group(function () {
    Route::get('/account', [CustomerDashboardController::class, 'index'])
        ->name('account.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('account.profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('account.profile.password');

    Route::middleware('verified')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');

        Route::get('/orders/{order}/invoice', [OrderController::class, 'invoice'])
            ->name('orders.invoice');

        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.store');
        Route::delete('/wishlist/{item}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        // Checkout routes
        Route::get('/checkout', [CheckoutController::class, 'index'])
            ->name('checkout.index');
        Route::post('/checkout/bandara-credit', [CheckoutController::class, 'applyBandaraCredit'])
            ->name('checkout.bandara-credit.apply');
        Route::delete('/checkout/bandara-credit', [CheckoutController::class, 'removeBandaraCredit'])
            ->name('checkout.bandara-credit.remove');
        Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');

        // Customer Invoice Routes
        Route::get('/invoices', [CustomerInvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('/invoices/{invoice}', [CustomerInvoiceController::class, 'show'])
            ->name('invoices.show');
    });

    // Customer Address Routes
    Route::get('/account/addresses', [AddressController::class, 'index'])
        ->name('account.addresses.index');

    Route::get('/account/addresses/create', [AddressController::class, 'create'])
        ->name('account.addresses.create');

    Route::post('/account/addresses', [AddressController::class, 'store'])
        ->name('account.addresses.store');

    Route::get('/account/addresses/{address}/edit', [AddressController::class, 'edit'])
        ->name('account.addresses.edit');

    Route::put('/account/addresses/{address}', [AddressController::class, 'update'])
        ->name('account.addresses.update');

    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])
        ->name('account.addresses.destroy');

    Route::get('/account/addresses/cities', [AddressController::class, 'cities'])
        ->name('account.addresses.cities');

    Route::get('/account/rewards', [ProfileController::class, 'rewards'])
        ->name('account.rewards');

    // kept as-is so nothing breaks
    Route::get('/invoices', [CustomerInvoiceController::class, 'index'])
        ->name('invoices.index');

    Route::get('/invoices/{invoice}', [CustomerInvoiceController::class, 'show'])
        ->name('invoices.show');

    // Payment routes
    Route::middleware('verified')->group(function () {
        Route::get('/orders/{order}/pay', [PaymentController::class, 'showRazorpayForm'])
            ->name('orders.pay.razorpay');

        Route::post('/payment/razorpay/callback', [PaymentController::class, 'handleRazorpayCallback'])
            ->name('payment.razorpay.callback');
    });

    // Customer Ticket Routes
    Route::get('/tickets', [CustomerTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [CustomerTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [CustomerTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [CustomerTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close', [CustomerTicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [CustomerTicketController::class, 'reopen'])->name('tickets.reopen');
});

// Email Verification Routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('account.dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Newsletter Routes
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->name('newsletter.subscribe');

Route::get('/newsletter/confirm/{subscriber}/{token}', [NewsletterController::class, 'confirm'])
    ->name('newsletter.confirm');

Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
    ->name('newsletter.unsubscribe')
    ->middleware('signed');

/*
|--------------------------------------------------------------------------
| NON-LOCALIZED BACKOFFICE / STAFF ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('can:manage users')->group(function () {
        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}', [RolePermissionController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');

        Route::resource('users', AdminUserController::class);
    });
});

Route::middleware(['auth', 'role:Admin|Manager|Accountant|CAAccountant|Stores'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::resource('announcements', AnnouncementController::class)->except('show');

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::middleware(['role:Stores|Admin'])
            ->prefix('stores')
            ->name('stores.')
            ->group(function () {
                Route::get('/', [StoresDashboardController::class, 'index'])->name('dashboard');
            });

        Route::post('orders/bulk-status', [OrderController::class, 'adminBulkStatusUpdate'])
            ->name('orders.bulk-status');

        Route::resource('products', ProductController::class)->except(['show']);

        Route::resource('products.variants', ProductVariantController::class)
            ->except(['show'])
            ->shallow();

        Route::resource('products.sell-units', ProductSellUnitController::class)
            ->except(['show'])
            ->shallow();

        Route::get('products/barcode/lookup', [ProductController::class, 'barcodeLookup'])
            ->middleware('throttle:60,1')
            ->name('products.barcodeLookup');

        Route::get('products/{product}/barcode-label', [ProductController::class, 'barcodeLabel'])
            ->name('products.barcodeLabel');

        Route::resource('categories', CategoryController::class);
        Route::resource('vendors', VendorController::class);

        Route::get('vendor-invoices/outstanding', [VendorInvoiceController::class, 'outstandingSummary'])
            ->name('vendor-invoices.outstanding');

        Route::get('vendor-invoices', [VendorInvoiceController::class, 'index'])->name('vendor-invoices.index');
        Route::get('vendor-invoices/create', [VendorInvoiceController::class, 'create'])->name('vendor-invoices.create');
        Route::post('vendor-invoices', [VendorInvoiceController::class, 'store'])->name('vendor-invoices.store');
        Route::get('vendor-invoices/{vendorInvoice}', [VendorInvoiceController::class, 'show'])->name('vendor-invoices.show');

        Route::get('vendor-payments', [VendorPaymentController::class, 'index'])->name('vendor-payments.index');
        Route::get('vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
        Route::post('vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');

        Route::resource('attributes', AttributeController::class)->except(['show']);

        Route::resource('attributes.values', AttributeValueController::class)
            ->except(['show'])
            ->shallow();

        Route::resource('products.images', ProductImageController::class)
            ->except(['show'])
            ->shallow();

        Route::resource('coupons', CouponController::class)->except(['show']);

        Route::get('invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/status', [AdminInvoiceController::class, 'updateStatus'])->name('invoices.status');

        Route::post('invoices/bulk-status', [AdminInvoiceController::class, 'bulkStatusUpdate'])
            ->name('invoices.bulk-status');

        Route::post('invoices/payment-form', [AdminInvoiceController::class, 'showPaymentFormForInvoices'])
            ->name('invoices.payment-form');

        Route::post('invoices/record-payment', [AdminInvoiceController::class, 'recordPayment'])
            ->name('invoices.record-payment');

        Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

        Route::middleware('can:manage users')->group(function () {
            Route::resource('users', AdminUserController::class);
        });

        Route::middleware('role:Admin|Manager|Stores')->group(function () {
            Route::get('/products/{product}/variants/options', [ProductVariantLookupController::class, 'byProduct'])
                ->name('products.variants.options');

            Route::get('/inventory/lots', [\App\Http\Controllers\Admin\InventoryLotController::class, 'index'])
                ->name('inventory.lots.index');

            Route::get('/inventory/lots/{lot}', [\App\Http\Controllers\Admin\InventoryLotController::class, 'show'])
                ->name('inventory.lots.show');

            Route::get('/inventory/lots/{lot}/pieces', [\App\Http\Controllers\Admin\InventoryPieceController::class, 'index'])
                ->name('inventory.lots.pieces.index');

            Route::get('/inventory/lots/{lot}/pieces/options', [\App\Http\Controllers\Admin\InventoryPieceController::class, 'options'])
                ->name('inventory.lots.pieces.options');

            Route::get('/inventory/packs', [InventoryPackController::class, 'index'])
                ->name('inventory.packs.index');

            Route::get('/inventory/packs/create', [InventoryPackController::class, 'create'])
                ->name('inventory.packs.create');

            Route::post('/inventory/packs', [InventoryPackController::class, 'store'])
                ->name('inventory.packs.store');

            Route::get('/production', [\App\Http\Controllers\Admin\ProductionRunController::class, 'index'])
                ->name('production.index');

            Route::get('/production/create', [\App\Http\Controllers\Admin\ProductionRunController::class, 'create'])
                ->name('production.create');

            Route::post('/production', [\App\Http\Controllers\Admin\ProductionRunController::class, 'store'])
                ->name('production.store');

            Route::get('/production/{run}', [\App\Http\Controllers\Admin\ProductionRunController::class, 'show'])
                ->name('production.show');
        });

        Route::middleware('role:Admin|Manager')->group(function () {
            // Route::resource('pages', AdminPageController::class)->except(['show']);
            Route::resource('product-collections', ProductCollectionController::class)
                ->parameters(['product-collections' => 'productCollection'])
                ->except('show');

            Route::resource('hsn-codes', HsnCodeController::class)->except(['show']);

            Route::resource('recipes', RecipeController::class)->except(['show']);

            Route::get('newsletter-subscribers', [AdminNewsletterSubscriberController::class, 'index'])
                ->name('newsletter-subscribers.index');
            Route::get('newsletter-subscribers/create', [AdminNewsletterSubscriberController::class, 'create'])
                ->name('newsletter-subscribers.create');
            Route::post('newsletter-subscribers', [AdminNewsletterSubscriberController::class, 'store'])
                ->name('newsletter-subscribers.store');
            Route::get('newsletter-subscribers/{subscriber}/edit', [AdminNewsletterSubscriberController::class, 'edit'])
                ->name('newsletter-subscribers.edit');
            Route::put('newsletter-subscribers/{subscriber}', [AdminNewsletterSubscriberController::class, 'update'])
                ->name('newsletter-subscribers.update');
            Route::delete('newsletter-subscribers/{subscriber}', [AdminNewsletterSubscriberController::class, 'destroy'])
                ->name('newsletter-subscribers.destroy');

            Route::post('newsletter-subscribers/{subscriber}/resend-confirmation', [AdminNewsletterSubscriberController::class, 'resendConfirmation'])
                ->name('newsletter-subscribers.resend-confirmation');

            Route::get('newsletter-campaigns', [AdminNewsletterCampaignController::class, 'index'])
                ->name('newsletter-campaigns.index');
            Route::get('newsletter-campaigns/create', [AdminNewsletterCampaignController::class, 'create'])
                ->name('newsletter-campaigns.create');
            Route::post('newsletter-campaigns', [AdminNewsletterCampaignController::class, 'store'])
                ->name('newsletter-campaigns.store');
            Route::get('newsletter-campaigns/{campaign}/edit', [AdminNewsletterCampaignController::class, 'edit'])
                ->name('newsletter-campaigns.edit');
            Route::put('newsletter-campaigns/{campaign}', [AdminNewsletterCampaignController::class, 'update'])
                ->name('newsletter-campaigns.update');
            Route::delete('newsletter-campaigns/{campaign}', [AdminNewsletterCampaignController::class, 'destroy'])
                ->name('newsletter-campaigns.destroy');

            Route::post('newsletter-campaigns/{campaign}/send', [AdminNewsletterCampaignController::class, 'sendNow'])
                ->name('newsletter-campaigns.send');

            Route::resource('ticket-categories', TicketCategoryController::class)
                ->parameters(['ticket-categories' => 'ticketCategory'])
                ->except(['show']);

            Route::resource('ticket-tags', TicketTagController::class)
                ->parameters(['ticket-tags' => 'ticketTag'])
                ->except(['show']);

            Route::get('/orders', [OrderController::class, 'adminIndex'])->name('orders.index');

            Route::get('orders/create', [OrderController::class, 'adminCreate'])
                ->name('orders.create');

            Route::post('orders', [OrderController::class, 'adminStore'])
                ->name('orders.store');

            Route::get('/orders/{order}', [OrderController::class, 'adminShow'])->name('orders.show');
            Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

            Route::get('/orders/{order}/print', [OrderPrintController::class, 'single'])
                ->name('orders.print');

            Route::post('/orders/print/bulk', [OrderPrintController::class, 'bulk'])
                ->name('orders.print.bulk');

            Route::get('/orders/print/new', [OrderPrintController::class, 'newOrders'])
                ->name('orders.print.new');

            Route::post('/orders/mark-printed', [OrderPrintController::class, 'markPrinted'])
                ->name('orders.markPrinted');

            Route::post('/orders/{order}/mark-unprinted', [OrderPrintController::class, 'markUnprinted'])
                ->name('orders.markUnprinted');

            Route::post('/orders/mark-unprinted/bulk', [OrderPrintController::class, 'bulkMarkUnprinted'])
                ->name('orders.markUnprinted.bulk');

            Route::middleware('can:manage customers')->group(function () {

                Route::prefix('customers')->name('customers.')->group(function () {
                    Route::prefix('b2c')->name('b2c.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\Admin\B2CCustomerController::class, 'index'])
                            ->name('index');
                        Route::get('/create', [\App\Http\Controllers\Admin\B2CCustomerController::class, 'create'])
                            ->name('create');
                        Route::post('/', [\App\Http\Controllers\Admin\B2CCustomerController::class, 'store'])
                            ->name('store');
                        Route::get('/{user}/edit', [\App\Http\Controllers\Admin\B2CCustomerController::class, 'edit'])
                            ->name('edit');
                        Route::put('/{user}', [\App\Http\Controllers\Admin\B2CCustomerController::class, 'update'])
                            ->name('update');
                        Route::delete('/{user}', [\App\Http\Controllers\Admin\B2CCustomerController::class, 'destroy'])
                            ->name('destroy');
                    });
                });

                Route::get('/b2b-customers', [\App\Http\Controllers\Admin\B2BCustomerController::class, 'index'])
                    ->name('b2b.customers.index');

                Route::get('/b2b-customers/create', [\App\Http\Controllers\Admin\B2BCustomerController::class, 'create'])
                    ->name('b2b.customers.create');

                Route::post('/b2b-customers', [\App\Http\Controllers\Admin\B2BCustomerController::class, 'store'])
                    ->name('b2b.customers.store');

                Route::get('/b2b-customers/{user}/edit', [\App\Http\Controllers\Admin\B2BCustomerController::class, 'edit'])
                    ->name('b2b.customers.edit');

                Route::put('/b2b-customers/{user}', [\App\Http\Controllers\Admin\B2BCustomerController::class, 'update'])
                    ->name('b2b.customers.update');

                Route::delete('/b2b-customers/{user}', [\App\Http\Controllers\Admin\B2BCustomerController::class, 'destroy'])
                    ->name('b2b.customers.destroy');

                Route::prefix('/b2b-customers/{user}/products')
                    ->name('customers.b2b-products.')
                    ->group(function () {
                        Route::get('/', [B2BCustomerProductController::class, 'index'])->name('index');
                        Route::get('/create', [B2BCustomerProductController::class, 'create'])->name('create');
                        Route::post('/', [B2BCustomerProductController::class, 'store'])->name('store');
                        Route::get('/{row}/edit', [B2BCustomerProductController::class, 'edit'])->name('edit');
                        Route::put('/{row}', [B2BCustomerProductController::class, 'update'])->name('update');
                        Route::delete('/{row}', [B2BCustomerProductController::class, 'destroy'])->name('destroy');
                    });

                Route::get('/b2b-product-requests', [B2BProductRequestController::class, 'index'])
                    ->name('b2b.product-requests.index');
                Route::post('/b2b-product-requests/{productRequest}/approve', [B2BProductRequestController::class, 'approve'])
                    ->name('b2b.product-requests.approve');
                Route::post('/b2b-product-requests/{productRequest}/reject', [B2BProductRequestController::class, 'reject'])
                    ->name('b2b.product-requests.reject');
            });

            Route::get('/b2b-customers/{user}/moq', [B2BCustomerMoqController::class, 'index'])->name('b2b.moq.index');
            Route::post('/b2b-customers/{user}/moq', [B2BCustomerMoqController::class, 'store'])->name('b2b.moq.store');
            Route::put('/b2b-customers/{user}/moq/{row}', [B2BCustomerMoqController::class, 'update'])->name('b2b.moq.update');
            Route::delete('/b2b-customers/{user}/moq/{row}', [B2BCustomerMoqController::class, 'destroy'])->name('b2b.moq.destroy');

            Route::get('/b2b-customers/{user}/prices', [B2BCustomerPriceController::class, 'index'])->name('b2b.prices.index');
            Route::get('/b2b-customers/{user}/prices/create', [B2BCustomerPriceController::class, 'create'])->name('b2b.prices.create');
            Route::post('/b2b-customers/{user}/prices', [B2BCustomerPriceController::class, 'store'])->name('b2b.prices.store');
            Route::get('/b2b-customers/{user}/prices/{price}/edit', [B2BCustomerPriceController::class, 'edit'])->name('b2b.prices.edit');
            Route::put('/b2b-customers/{user}/prices/{price}', [B2BCustomerPriceController::class, 'update'])->name('b2b.prices.update');
            Route::delete('/b2b-customers/{user}/prices/{price}', [B2BCustomerPriceController::class, 'destroy'])->name('b2b.prices.destroy');

            Route::prefix('/rewards')
                ->middleware('can:view rewards')
                ->name('rewards.')
                ->group(function () {
                    Route::get('/', [BandaraCreditController::class, 'index'])->name('index');
                    Route::get('/tiers', [BandaraCreditController::class, 'tiers'])->name('tiers');
                    Route::put('/tiers', [BandaraCreditController::class, 'updateTiers'])->middleware('can:manage rewards')->name('tiers.update');
                    Route::get('/campaigns', [BandaraCreditController::class, 'campaigns'])->name('campaigns.index');
                    Route::get('/campaigns/create', [BandaraCreditController::class, 'createCampaign'])->middleware('can:manage rewards')->name('campaigns.create');
                    Route::post('/campaigns', [BandaraCreditController::class, 'storeCampaign'])->middleware('can:manage rewards')->name('campaigns.store');
                    Route::get('/campaigns/{campaign}/edit', [BandaraCreditController::class, 'editCampaign'])->middleware('can:manage rewards')->name('campaigns.edit');
                    Route::put('/campaigns/{campaign}', [BandaraCreditController::class, 'updateCampaign'])->middleware('can:manage rewards')->name('campaigns.update');
                    Route::delete('/campaigns/{campaign}', [BandaraCreditController::class, 'destroyCampaign'])->middleware('can:manage rewards')->name('campaigns.destroy');
                    Route::get('/customers', [BandaraCreditController::class, 'customers'])->name('customers');
                    Route::post('/adjustments', [BandaraCreditController::class, 'storeAdjustment'])->middleware('can:manage rewards')->name('adjustments.store');
                    Route::get('/ledger', [BandaraCreditController::class, 'ledger'])->name('ledger');
                });

            Route::get('/bandara-credit/preview', [BandaraCreditController::class, 'index'])
                ->middleware('can:view rewards')
                ->name('bandara-credit.preview');
        });
    });

// SUPPORT
Route::middleware(['auth', 'role:Support'])->group(function () {
    Route::get('/support/dashboard', SupportDashboardController::class)
        ->name('support.dashboard');
});

// SUPPORT / MANAGER / ADMIN
Route::middleware(['auth', 'role:Admin|Manager|Support'])
    ->prefix('support')
    ->name('support.')
    ->group(function () {
        Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/unassigned', [SupportTicketController::class, 'unassigned'])->name('tickets.unassigned');
        Route::get('/tickets/mine', [SupportTicketController::class, 'mine'])->name('tickets.mine');
        Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');

        Route::post('/tickets/{ticket}/assign', [SupportTicketController::class, 'assignToMe'])->name('tickets.assignToMe');
        Route::post('/tickets/{ticket}/reassign', [SupportTicketController::class, 'reassign'])->name('tickets.reassign');
        Route::post('/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/note', [SupportTicketController::class, 'addInternalNote'])->name('tickets.note');
        Route::post('/tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('tickets.status');
        Route::post('/tickets/{ticket}/tags', [SupportTicketController::class, 'updateTags'])->name('tickets.tags');
    });

// MANAGER
Route::middleware(['auth', 'role:Manager'])->group(function () {
    Route::get('/manager/dashboard', [ManagerDashboardController::class, 'index'])
        ->name('manager.dashboard');
});

// ACCOUNTANT
Route::middleware(['auth', 'role:Accountant|CAAccountant'])->group(function () {
    Route::get('/accountant/dashboard', [AccountantDashboardController::class, 'index'])
        ->name('accountant.dashboard');
});

/**
 * Backward-compatible dashboard route name for Stores.
 */
Route::middleware(['auth', 'role:Stores|Admin'])
    ->get('/stores/dashboard', function () {
        return redirect()->route('admin.stores.dashboard');
    })
    ->name('stores.dashboard');
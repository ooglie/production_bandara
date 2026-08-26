<?php return array (
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '12',
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'host' => NULL,
          'port' => 443,
          'scheme' => 'https',
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => NULL,
        'secret' => NULL,
        'app_id' => NULL,
        'options' => 
        array (
          'cluster' => NULL,
          'host' => 'api-mt1.pusher.com',
          'port' => 443,
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views',
    ),
    'compiled' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/framework/views',
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => '*',
    ),
    'allowed_origins' => 
    array (
      0 => '*',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => '*',
    ),
    'exposed_headers' => 
    array (
    ),
    'max_age' => 0,
    'supports_credentials' => false,
  ),
  'image' => 
  array (
    'default' => 'gd',
  ),
  'app' => 
  array (
    'name' => 'Bandara',
    'env' => 'local',
    'debug' => true,
    'url' => 'https://frozen.bandara.in',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'Asia/Kolkata',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:Vs7Nn2Fmeoz5QjlOhv8zRgFQx9/HpfHnTwIn46O2Rf8=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Image\\ImageServiceProvider',
      11 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      12 => 'Illuminate\\Hashing\\HashServiceProvider',
      13 => 'Illuminate\\Mail\\MailServiceProvider',
      14 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      15 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      16 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      17 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      18 => 'Illuminate\\Queue\\QueueServiceProvider',
      19 => 'Illuminate\\Redis\\RedisServiceProvider',
      20 => 'Illuminate\\Session\\SessionServiceProvider',
      21 => 'Illuminate\\Translation\\TranslationServiceProvider',
      22 => 'Illuminate\\Validation\\ValidationServiceProvider',
      23 => 'Illuminate\\View\\ViewServiceProvider',
      24 => 'App\\Providers\\AppServiceProvider',
      25 => 'App\\Providers\\B2BApplicationServiceProvider',
      26 => 'App\\Providers\\StaffAuthenticationServiceProvider',
      27 => 'App\\Providers\\BandaraLaunchUiServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'staff' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'b2b_application' => 
  array (
    'customer_type' => 
    array (
      'b2c' => 'b2c',
      'b2b' => 'b2b',
    ),
    'admin_roles' => 
    array (
      0 => 'Admin',
      1 => 'Manager',
    ),
    'permissions' => 
    array (
      'view' => 'admin.b2b-applications.view',
      'review' => 'admin.b2b-applications.review',
      'approve' => 'admin.b2b-applications.approve',
    ),
    'location' => 
    array (
      'states' => 
      array (
        'table' => 'states',
        'id' => 'id',
        'name' => 'name',
        'active' => 'is_active',
      ),
      'cities' => 
      array (
        'table' => 'cities',
        'id' => 'id',
        'name' => 'name',
        'state_id' => 'state_id',
        'active' => 'is_active',
      ),
    ),
    'business_types' => 
    array (
      'restaurant' => 'Restaurant',
      'hotel' => 'Hotel',
      'cafe' => 'Café',
      'caterer' => 'Caterer',
      'cloud_kitchen' => 'Cloud kitchen',
      'retail_store' => 'Retail store',
      'supermarket' => 'Supermarket',
      'distributor' => 'Distributor',
      'food_manufacturer' => 'Food manufacturer',
      'corporate_institution' => 'Corporate or institution',
      'other' => 'Other',
    ),
    'product_categories' => 
    array (
      'meat' => 'Meat',
      'seafood' => 'Seafood',
      'cheese_dairy' => 'Cheese and dairy',
      'bakery' => 'Bakery',
      'frozen_snacks' => 'Frozen snacks',
      'ready_to_cook' => 'Ready-to-cook products',
      'imported_speciality' => 'Imported speciality products',
      'other' => 'Other',
    ),
    'monthly_purchase_ranges' => 
    array (
      'below_25000' => 'Below ₹25,000',
      '25000_50000' => '₹25,000–₹50,000',
      '50000_100000' => '₹50,000–₹1,00,000',
      '100000_250000' => '₹1,00,000–₹2,50,000',
      'above_250000' => 'Above ₹2,50,000',
      'not_sure' => 'Not sure yet',
    ),
    'purchase_frequencies' => 
    array (
      'daily' => 'Daily',
      'several_weekly' => 'Several times a week',
      'weekly' => 'Weekly',
      'fortnightly' => 'Fortnightly',
      'monthly' => 'Monthly',
      'as_required' => 'As required',
    ),
    'notifications' => 
    array (
      'database' => true,
      'mail' => true,
    ),
    'commercial_integration' => 
    array (
      'user_columns' => 
      array (
        'customer_type' => 'customer_type',
        'pay_later_enabled' => NULL,
        'credit_limit' => NULL,
        'payment_terms_days' => NULL,
        'minimum_order_value' => NULL,
        'price_group_id' => NULL,
        'account_manager_id' => NULL,
      ),
      'existing_terms' => 
      array (
        'table' => 'b2b_customer_terms',
        'user_key' => 'user_id',
        'columns' => 
        array (
          'pay_later_enabled' => 'pay_later_enabled',
          'credit_limit' => 'credit_limit',
          'payment_terms_days' => 'payment_terms_days',
          'minimum_order_value' => NULL,
          'price_group_id' => NULL,
          'account_manager_id' => NULL,
          'updated_at' => 'updated_at',
          'created_at' => 'created_at',
        ),
        'update_existing_only' => true,
      ),
    ),
  ),
  'b2b_application_corrective' => 
  array (
    'location' => 
    array (
      'country_code' => 'IN',
      'states' => 
      array (
        'table' => 'states',
        'id' => 'id',
        'name' => 'name',
        'relation_key' => 'code',
        'country_column' => 'country_code',
        'active' => 'is_active',
        'sort' => 'sort_order',
      ),
      'cities' => 
      array (
        'table' => 'cities',
        'id' => 'id',
        'name' => 'name',
        'state_key' => 'state_code',
        'country_column' => 'country_code',
        'active' => 'is_active',
        'sort' => 'sort_order',
      ),
    ),
    'entry_intent' => 
    array (
      'session_key' => 'bandara.business_account_intent',
      'ttl_seconds' => 7200,
    ),
    'view' => 
    array (
      'customer_layout' => 'layouts.customer',
      'customer_section' => 'content',
      'admin_layout' => 'layouts.company',
      'admin_section' => 'content',
    ),
    'ui' => 
    array (
      'container' => 'mx-auto w-full max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8',
      'panel' => 'mt-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2',
      'panel_compact' => 'mt-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg p-2',
      'heading' => 'text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-50 leading-tight',
      'subheading' => 'text-lg font-semibold text-gray-900 dark:text-gray-50',
      'text' => 'max-w-md text-sm text-gray-600 dark:text-gray-300 leading-relaxed',
      'muted' => 'mt-1 text-xs text-gray-500 dark:text-gray-400',
      'label' => 'block text-xs font-medium text-gray-700 dark:text-gray-300',
      'field' => 'mt-1 w-full rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500',
      'checkbox' => 'rounded-sm',
      'button_primary' => 'inline-flex items-center justify-center rounded-sm border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-2.5 text-xs font-medium hover:bg-gray-800 dark:hover:bg-gray-200',
      'button_secondary' => 'inline-flex text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800',
      'button_danger' => 'inline-flex text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800',
      'link' => 'text-[11px] text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100',
      'alert_success' => 'rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800',
      'alert_error' => 'rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 sm:p-8 space-y-5',
      'alert_info' => 'rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-3 py-1.5 text-xs',
      'badge' => 'inline-flex w-max rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300',
      'table' => 'min-w-full text-xs',
      'table_head' => 'text-left text-gray-500',
      'table_cell' => 'px-3 py-2 text-left font-medium',
      'nav_link' => 'inline-flex items-center gap-2',
      'admin_nav_link' => 'hover:underline',
    ),
  ),
  'bandara_content' => 
  array (
    'company' => 
    array (
      'brand_name' => 'Bandara',
      'legal_name' => 'Bandara LLP',
      'shopping_domain' => 'bandara.shop',
      'corporate_domain' => 'bandarallp.com',
      'registered_address' => '303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001',
      'support_address' => '402B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001',
      'support_email' => 'support@bandarallp.com',
      'privacy_email' => 'privacy@bandarallp.com',
      'support_phone' => '+91 9823170102',
      'support_hours' => 'Monday to Sunday, 10:00 a.m. to 6:00 p.m. IST',
      'gstin' => '27ABEFB3240N1ZE',
      'fssai' => '21526079001348',
      'grievance' => 
      array (
        'name' => 'Parag Parulekar',
        'designation' => 'Chief Executive Officer',
        'email' => 'parag@bandarallp.com',
      ),
    ),
    'delivery' => 
    array (
      'b2c_base_km' => 2,
      'b2c_base_fee' => 50,
      'b2c_extra_per_km' => 15,
      'b2b_free_km' => 3,
      'b2b_extra_per_km' => 15,
      'same_day_cutoff' => '1:00 p.m.',
      'intercity_b2b_min_kg' => 20,
    ),
    'rewards' => 
    array (
      'silver_min' => 0,
      'silver_max' => 999,
      'gold_min' => 1000,
      'gold_max' => 4999,
      'platinum_min' => 5000,
    ),
    'policies' => 
    array (
      'version' => '1.0',
      'effective_date' => '27 July 2026',
      'last_updated' => '27 July 2026',
    ),
    'faq_categories' => 
    array (
      0 => 
      array (
        'slug' => 'orders-and-availability',
        'label' => 'Orders and availability',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'Do I need an account to shop with Bandara?',
            'answer' => '<p>You may browse Bandara’s products without signing in. An account may be required to complete an order and use account features such as saved addresses, order history, Bandara Credit and approved B2B terms.</p>',
          ),
          1 => 
          array (
            'question' => 'When is my order confirmed?',
            'answer' => '<p>A B2C order is normally confirmed after successful payment and issuance of an order confirmation by Bandara.</p>
<p>A B2B order may be confirmed after Bandara verifies stock, pricing, minimum order quantity, delivery feasibility, account status and the customer’s approved payment or credit terms. Depending on those terms, full payment, a partial payment or no immediate payment may be required before confirmation.</p>',
          ),
          2 => 
          array (
            'question' => 'Does adding a product to my cart reserve it?',
            'answer' => '<p>No. Adding a product to the cart does not by itself guarantee availability. Stock is checked again during checkout and order confirmation.</p>',
          ),
          3 => 
          array (
            'question' => 'What happens if a product becomes unavailable after I order it?',
            'answer' => '<p>Bandara will contact you where reasonably possible. We may offer a suitable alternative, arrange partial fulfilment with your approval, defer the affected item, or cancel it and refund the amount collected for that item.</p>',
          ),
          4 => 
          array (
            'question' => 'Will Bandara substitute a product automatically?',
            'answer' => '<p>No. Bandara will not make a material substitution without your consent. Where an alternative is available, we will explain the relevant difference in product, brand, weight, price, ingredients or other important characteristics before you accept it.</p>
<p>We will not knowingly substitute a product in a way that changes an allergen, dietary classification, animal species or another material characteristic without explicit approval.</p>',
          ),
          5 => 
          array (
            'question' => 'Can I modify an order after placing it?',
            'answer' => '<p>Contact Bandara Customer Support as soon as possible. A change may be possible before dispatch, depending on stock, preparation status, payment and delivery arrangements.</p>
<p>B2B modifications are considered case by case and may result in revised quantities, prices, taxes, delivery timing or invoice details.</p>',
          ),
          6 => 
          array (
            'question' => 'Can I cancel my order?',
            'answer' => '<p>You may cancel an order before it has been dispatched or marked out for delivery. Bandara’s ordinary products are generally maintained in pre-cut or pre-packed saleable form, so cancellation may still be possible after picking or packing has begun.</p>
<p>Orders normally cannot be cancelled after dispatch.</p>',
          ),
          7 => 
          array (
            'question' => 'Can Bandara cancel an order?',
            'answer' => '<p>Bandara may cancel all or part of an order where stock is unavailable, the address is not serviceable, payment or credit verification fails, suspected fraud or misuse is identified, fulfilment would be unsafe, or a clear technical or pricing error has occurred. Any amount collected for a cancelled item or order will be refunded.</p>
<hr />',
          ),
        ),
        'description' => 'Order confirmation, stock, changes and cancellations.',
        'icon' => 'bag',
      ),
      1 => 
      array (
        'slug' => 'delivery',
        'label' => 'Delivery',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'Where does Bandara deliver?',
            'answer' => '<p>Bandara provides local B2C and B2B delivery within Pune, subject to address serviceability.</p>
<p>Intercity delivery may be arranged for B2B orders where a suitable courier, refrigerated carrier or cold-chain service is available. Intercity B2B orders are ordinarily considered for shipments of at least 20 kg, unless Bandara agrees otherwise.</p>',
          ),
          1 => 
          array (
            'question' => 'How quickly will my order be delivered?',
            'answer' => '<p>Orders confirmed by 1:00 p.m. are normally delivered within Pune on the same day, subject to stock, delivery capacity, traffic and serviceability.</p>
<p>Orders confirmed after 1:00 p.m. are normally delivered on the next delivery day, although Bandara may fulfil them earlier where capacity permits. Delivery operates seven days a week, subject to notified closures and circumstances beyond Bandara’s reasonable control.</p>',
          ),
          2 => 
          array (
            'question' => 'Does Bandara offer scheduled delivery slots?',
            'answer' => '<p>The current ordering system does not offer customer-selected delivery slots. Bandara will provide or update the expected delivery timing through the order process or customer communication.</p>',
          ),
          3 => 
          array (
            'question' => 'How are B2C delivery charges calculated?',
            'answer' => '<p>For eligible B2C deliveries, the standard charge is ₹50 for the first 2 kilometres and ₹15 for each additional kilometre. The applicable distance and charge are calculated from the delivery address using Bandara’s designated distance service and are shown before the order is confirmed.</p>',
          ),
          4 => 
          array (
            'question' => 'How are local B2B delivery charges calculated?',
            'answer' => '<p>Local B2B delivery is free for the first 3 kilometres. A charge of ₹15 applies for each additional kilometre. The applicable charge is displayed at checkout or communicated before order confirmation.</p>',
          ),
          5 => 
          array (
            'question' => 'How are intercity B2B delivery charges calculated?',
            'answer' => '<p>Intercity B2B delivery is quoted separately for each case. The quotation may take into account destination, shipment weight, packaging, refrigeration or cold-chain requirements, carrier charges, insurance, handling and delivery time.</p>
<p>An intercity order is not treated as finally confirmed until the customer accepts the applicable quotation and completes any payment or deposit required by Bandara.</p>',
          ),
          6 => 
          array (
            'question' => 'Are cold-chain or handling charges applicable?',
            'answer' => '<p>Cold-chain, packaging or handling charges may apply where shown during checkout or disclosed before confirmation. These charges depend on the products, destination and fulfilment requirements of the order.</p>',
          ),
          7 => 
          array (
            'question' => 'What happens if I am unavailable when the order arrives?',
            'answer' => '<p>Please ensure that you or an authorised recipient is available at the delivery address. At your confirmed request, Bandara may hand the order to building security, reception, an employee, a neighbour or another authorised person. Delivery is treated as completed when the order is handed to that person.</p>
<p>If no authorised recipient is available, Bandara may return the order to its facility. Redelivery depends on product condition, cold-chain integrity, remaining shelf life and delivery capacity, and an additional charge may apply. A perishable order may not qualify for a refund where safe redelivery is no longer possible because the customer or authorised recipient was unavailable.</p>',
          ),
          8 => 
          array (
            'question' => 'Can my order be left with security, reception or a neighbour?',
            'answer' => '<p>Yes, but only when you have authorised it. Please ensure that the recipient can place chilled or frozen products into appropriate storage immediately.</p>',
          ),
          9 => 
          array (
            'question' => 'What happens if my address or phone number is incorrect?',
            'answer' => '<p>Customers are responsible for providing a complete address and a reachable phone number. If Bandara identifies an issue before dispatch, we may place the order on hold and contact you by the available details.</p>
<p>If no reply is received within 48 hours, Bandara may cancel the order and refund the amount collected. If the problem is discovered after dispatch, the order may be treated as an unsuccessful delivery and redelivery may be subject to product safety and additional charges.</p>',
          ),
          10 => 
          array (
            'question' => 'What happens if delivery is delayed?',
            'answer' => '<p>Delivery may be affected by weather, flooding, traffic restrictions, vehicle problems, public disruption, government action, power failure, transport interruption or another event beyond Bandara’s reasonable control.</p>
<p>Bandara will provide an updated estimate where reasonably possible. If the order can no longer be delivered safely or the required cold chain cannot be maintained, Bandara may cancel the affected part and refund the amount collected for it.</p>
<hr />',
          ),
        ),
        'description' => 'Pune delivery, B2B freight, charges and handover.',
        'icon' => 'truck',
      ),
      2 => 
      array (
        'slug' => 'cancellations-replacements-and-refunds',
        'label' => 'Cancellations, replacements and refunds',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'Can I return a frozen or perishable product because I changed my mind?',
            'answer' => '<p>No. Frozen, chilled and other perishable food products cannot be returned or exchanged merely because you changed your mind, ordered the wrong quantity, no longer require the item or do not personally prefer its taste, texture, appearance or cooking characteristics.</p>',
          ),
          1 => 
          array (
            'question' => 'What should I do if I receive the wrong product?',
            'answer' => '<p>Contact Bandara Customer Support within one hour of delivery and provide the order number and clear photographs of the product and label. Once verified, Bandara will arrange an appropriate replacement or refund in consultation with you.</p>',
          ),
          2 => 
          array (
            'question' => 'What happens if an item is missing from my order?',
            'answer' => '<p>Report the missing item within one hour of delivery. Bandara will verify the order and delivery record and, where the claim is confirmed, arrange prompt replacement or refund of the missing item.</p>',
          ),
          3 => 
          array (
            'question' => 'What should I do if the packaging is damaged, leaking or unsealed?',
            'answer' => '<p>Do not consume the product if its safety or integrity may have been affected. Keep the product, packaging and label under the stated storage conditions and contact Bandara within one hour with photographs or video showing the issue.</p>',
          ),
          4 => 
          array (
            'question' => 'What should I do if a frozen product arrives completely thawed?',
            'answer' => '<p>Do not consume or refreeze it. Place it in suitable cold storage if it is safe to do so, retain the packaging and contact Bandara within one hour. Bandara will review the delivery conditions and available evidence and arrange an appropriate resolution where the claim is verified.</p>',
          ),
          5 => 
          array (
            'question' => 'What if the product appears slightly softened at the surface?',
            'answer' => '<p>Minor surface softening, frost loss or a change in visible ice crystals does not by itself determine that a product is unsafe or defective. If you are concerned about its condition, do not consume it and contact Bandara within one hour so the circumstances can be reviewed.</p>',
          ),
          6 => 
          array (
            'question' => 'How quickly must I report a problem?',
            'answer' => '<p>Visible delivery issues — such as a missing item, incorrect item, damaged packaging, broken seal, leakage or a fully thawed product — should be reported within one hour of delivery.</p>
<p>If a quality concern was not reasonably visible at handover, contact Bandara as soon as it is discovered. Keep the product, packaging and label available for review.</p>',
          ),
          7 => 
          array (
            'question' => 'What evidence should I provide?',
            'answer' => '<p>Please provide the order number and clear photographs of the product, outer packaging, seal, product label, batch or lot information, use-by or expiry information and the issue complained of. Bandara may request a short video or other reasonable evidence where necessary.</p>',
          ),
          8 => 
          array (
            'question' => 'Must I retain the affected product?',
            'answer' => '<p>Yes. Keep the product and its original packaging under the stated storage conditions until Bandara confirms that they may be discarded or arranges collection. A claim may be difficult to verify if the product or label has been discarded.</p>',
          ),
          9 => 
          array (
            'question' => 'Will Bandara collect a damaged or disputed product?',
            'answer' => '<p>Bandara may arrange collection where inspection or safe disposal is required. We will advise you whether collection is necessary after reviewing the initial information.</p>',
          ),
          10 => 
          array (
            'question' => 'Will I receive a replacement, refund or Bandara Credit?',
            'answer' => '<p>Bandara may offer a replacement, refund or Bandara Credit in consultation with you. Where a suitable replacement is unavailable or cannot be delivered safely within a reasonable period, a refund will normally be offered. Bandara Credit will be used in place of a monetary refund only with your agreement.</p>',
          ),
          11 => 
          array (
            'question' => 'How quickly are approved refunds processed?',
            'answer' => '<p>Bandara normally initiates an approved refund within 48 hours. The time required for the amount to appear in your account may depend on the bank, card network, UPI service or payment provider.</p>',
          ),
          12 => 
          array (
            'question' => 'Are delivery, cold-chain and handling charges refundable?',
            'answer' => '<p>Where the entire order is cancelled or rejected because of an issue attributable to Bandara, the related delivery, cold-chain and handling charges will also be refunded.</p>
<p>Where only one item in an otherwise correctly delivered order is affected, Bandara will refund or replace the affected item and any charge directly attributable to it. General order-level charges are not automatically refundable in every partial claim.</p>',
          ),
          13 => 
          array (
            'question' => 'Can I make a claim after opening the product?',
            'answer' => '<p>Opening a product solely to inspect or prepare it does not automatically invalidate a genuine quality complaint. However, Bandara may be unable to verify a claim where the product has been substantially consumed, discarded, altered, improperly stored or retained without its original packaging and label.</p>',
          ),
          14 => 
          array (
            'question' => 'Are custom cuts, sliced products, repacked products, cheese and baked goods returnable?',
            'answer' => '<p>They are not returnable because of a change of mind. This does not prevent a claim where Bandara supplied the wrong product or a verified delivery, packaging, safety or material quality issue exists.</p>',
          ),
          15 => 
          array (
            'question' => 'Can B2B customers return products?',
            'answer' => '<p>A B2B return requires Bandara’s prior approval. Approval may be considered for an incorrect product, verified damage, a quality or safety issue, or another commercial exception agreed by Bandara. Approved B2B returns may be resolved through replacement, credit note, account adjustment or refund.</p>
<hr />',
          ),
        ),
        'description' => 'Perishable-product claims, evidence and refund timing.',
        'icon' => 'refresh',
      ),
      3 => 
      array (
        'slug' => 'products-storage-and-food-handling',
        'label' => 'Products, storage and food handling',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'Are product photographs exact?',
            'answer' => '<p>Product photographs are representative. Natural products may differ in colour, size, shape, cut, marbling, finish or appearance. These variations do not necessarily indicate a difference in quality.</p>',
          ),
          1 => 
          array (
            'question' => 'Are displayed weights exact?',
            'answer' => '<p>Fixed-weight packs are supplied at the net quantity declared on the pack. Where a product is sold as an individually selected or recorded catch-weight item, the applicable weight and price are shown on the product, package, order record or invoice.</p>
<p>Minor surface moisture, glaze or ice loss may occur during handling. Any material discrepancy will be reviewed against the product label, invoiced quantity and Bandara’s dispatch records.</p>',
          ),
          2 => 
          array (
            'question' => 'How should frozen products be stored?',
            'answer' => '<p>Store frozen products at the temperature stated on the product page and physical label, ordinarily at or below −18°C. Transfer products to appropriate frozen storage promptly after delivery.</p>',
          ),
          3 => 
          array (
            'question' => 'How should chilled products be stored?',
            'answer' => '<p>Store chilled products at the temperature stated on the product page and physical label, ordinarily between 2°C and 4°C. Do not leave chilled products at room temperature longer than necessary for handling or preparation.</p>',
          ),
          4 => 
          array (
            'question' => 'How should I thaw a frozen product?',
            'answer' => '<p>Always follow the product-specific instructions on the physical label and Bandara product page. Keep the product sealed during thawing unless the instructions say otherwise. Detailed guidance may differ between meat, seafood, cheese, bakery and ready-to-cook items.</p>
<p>Do not use standing water or a microwave unless the manufacturer’s instructions specifically permit that method.</p>',
          ),
          5 => 
          array (
            'question' => 'Can I refreeze a product after it has thawed?',
            'answer' => '<p>Do not refreeze a product after it has completely thawed unless the manufacturer’s label expressly permits it. Refreezing can affect safety, texture and quality.</p>',
          ),
          6 => 
          array (
            'question' => 'Where can I find allergen information?',
            'answer' => '<p>Ingredients and allergen information are displayed where available from the manufacturer, importer, supplier or packer and should also appear on the applicable physical label.</p>
<p>Customers with an allergy, intolerance or special dietary requirement should review the delivered label before consumption and contact Bandara where clarification is required.</p>',
          ),
          7 => 
          array (
            'question' => 'Can Bandara products contain traces of other allergens?',
            'answer' => '<p>Bandara handles a range of products that may include seafood, crustaceans, fish, milk, eggs, cereals containing gluten, soy, nuts and other allergens. A “may contain” statement will be displayed where the relevant supplier, manufacturer, packer or Bandara has identified a cross-contact risk.</p>
<p>Unless a product is specifically represented as prepared in a dedicated allergen-controlled environment, Bandara cannot guarantee the complete absence of cross-contact.</p>',
          ),
          8 => 
          array (
            'question' => 'Which information should I follow if the website and physical label differ?',
            'answer' => '<p>Follow the physical label for batch-specific details, ingredients, allergens, storage conditions, preparation instructions, country of origin, manufacturer or importer information and use-by or expiry dates.</p>
<p>Do not consume the product where a material difference creates uncertainty about safety, allergens or suitability. Contact Bandara for clarification.</p>
<hr />',
          ),
        ),
        'description' => 'Storage temperatures, thawing, weights and allergens.',
        'icon' => 'snow',
      ),
      4 => 
      array (
        'slug' => 'payments-and-pricing',
        'label' => 'Payments and pricing',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'Which payment methods does Bandara accept?',
            'answer' => '<p>B2C online payments may be made using the payment methods made available through checkout, including eligible credit cards, debit cards, UPI, net banking and supported wallets.</p>
<p>Approved B2B customers may also use NEFT, RTGS, IMPS, cheque and approved Pay Later or credit terms. Cash on Delivery is not available.</p>',
          ),
          1 => 
          array (
            'question' => 'Does Bandara use Razorpay?',
            'answer' => '<p>Yes. Razorpay is used to process eligible online payments. The payment methods actually available may vary by customer, order and payment provider availability.</p>',
          ),
          2 => 
          array (
            'question' => 'Does Bandara offer Cash on Delivery?',
            'answer' => '<p>No. Cash on Delivery is not currently available for B2C or B2B orders.</p>',
          ),
          3 => 
          array (
            'question' => 'What happens if money is debited but my payment is shown as failed?',
            'answer' => '<p>Do not immediately make a duplicate payment. Contact Bandara Customer Support with the order or payment reference.</p>
<p>If Bandara or the payment provider confirms receipt, Bandara will reconcile the payment with the intended order or initiate a refund. If the amount was not received by Bandara, the reversal is handled by the bank or payment provider according to its processing timeline.</p>',
          ),
          4 => 
          array (
            'question' => 'What happens if payment succeeds but no order confirmation is created?',
            'answer' => '<p>Contact Customer Support with the payment reference. Bandara will verify the transaction and either create or reconcile the intended order where fulfilment is possible, or initiate a refund.</p>',
          ),
          5 => 
          array (
            'question' => 'Are B2C prices inclusive of GST?',
            'answer' => '<p>Yes. B2C prices are displayed inclusive of applicable GST unless the website clearly states otherwise.</p>',
          ),
          6 => 
          array (
            'question' => 'Are B2B prices exclusive of GST?',
            'answer' => '<p>Yes. B2B prices are generally displayed exclusive of applicable GST, which is added or shown in the order and invoice as required.</p>',
          ),
          7 => 
          array (
            'question' => 'What happens if there is a pricing error?',
            'answer' => '<p>Bandara takes reasonable care to display accurate prices, discounts and taxes. If Bandara ordinarily undercharges a customer because of its own error and has accepted the order, Bandara will generally honour the confirmed price.</p>
<p>If a customer is overcharged, the excess amount will be refunded and any related Bandara Credit adjustment will be made.</p>
<p>Bandara may correct or cancel an order before dispatch where a price resulted from a clear technical error, an unauthorised or incompatible discount, manipulation or an error that a reasonable customer would recognise as unintended. Any amount collected for a cancelled order will be refunded.</p>',
          ),
          8 => 
          array (
            'question' => 'How long does a refund take to appear?',
            'answer' => '<p>Bandara normally initiates an approved refund within 48 hours. Your bank, card issuer, UPI service or payment provider may require additional processing time before the refund appears in your account.</p>
<hr />',
          ),
        ),
        'description' => 'Razorpay, GST, failed payments and pricing corrections.',
        'icon' => 'card',
      ),
      5 => 
      array (
        'slug' => 'b2b-orders',
        'label' => 'B2B orders',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'Who can register as a B2B customer?',
            'answer' => '<p>Businesses such as hotels, restaurants, caterers, retailers, resellers, institutions and other approved commercial customers may apply for B2B access. Bandara may review and approve the account before B2B pricing or commercial terms become available.</p>',
          ),
          1 => 
          array (
            'question' => 'Are B2B prices different from B2C prices?',
            'answer' => '<p>Yes. B2B prices, minimum order quantities and product availability may differ from B2C terms and are assigned through Bandara’s administration system.</p>',
          ),
          2 => 
          array (
            'question' => 'Do B2B products have minimum order quantities?',
            'answer' => '<p>They may. The applicable minimum order quantity is shown for the product or provided as part of the customer’s commercial terms.</p>',
          ),
          3 => 
          array (
            'question' => 'Can B2B customers use Pay Later?',
            'answer' => '<p>Yes, where Pay Later or a credit period has been approved for that customer by Bandara. Credit limits, due dates and payment terms are controlled through the B2B account.</p>',
          ),
          4 => 
          array (
            'question' => 'Are partial B2B payments accepted?',
            'answer' => '<p>Yes, where permitted for the relevant account, order or invoice. The required advance amount, remaining balance and due date are shown on the invoice, payment record or separately agreed commercial terms.</p>
<p>A partial payment does not discharge the full invoice. Bandara may hold procurement, preparation, dispatch or further orders until the required amount has been received.</p>',
          ),
          5 => 
          array (
            'question' => 'Which offline B2B payment methods are accepted?',
            'answer' => '<p>Bandara may accept NEFT, RTGS, IMPS and cheque, subject to verification or realisation. Cash is not accepted. Available payment methods and instructions are displayed or communicated for the relevant invoice.</p>',
          ),
          6 => 
          array (
            'question' => 'What happens if a B2B invoice becomes overdue?',
            'answer' => '<p>Bandara may place pending or new orders on hold, reduce or suspend the customer’s credit limit, withdraw Pay Later access or require advance payment. Interest or another late-payment charge will apply only where it was expressly agreed as part of the relevant commercial terms.</p>',
          ),
          7 => 
          array (
            'question' => 'How is local B2B delivery charged?',
            'answer' => '<p>Delivery is free for the first 3 kilometres and ₹15 for each additional kilometre. The calculated charge is displayed at checkout or confirmed before the order is accepted.</p>',
          ),
          8 => 
          array (
            'question' => 'Can Bandara arrange intercity B2B delivery?',
            'answer' => '<p>Yes, where a suitable transport or cold-chain service is available. Intercity arrangements are quoted separately for each order and ordinarily require a minimum shipment of 20 kg unless Bandara approves an exception.</p>',
          ),
          9 => 
          array (
            'question' => 'Can a confirmed B2B order be changed or cancelled?',
            'answer' => '<p>A confirmed B2B order may be changed or cancelled before dispatch only with Bandara’s approval. The change may affect product availability, quantity, price, tax, delivery timing or invoice details. Once dispatched, cancellation is not normally permitted.</p>',
          ),
          10 => 
          array (
            'question' => 'Do B2B customers earn or redeem Bandara Credit?',
            'answer' => '<p>No. Bandara Credit is a B2C-only programme.</p>
<hr />',
          ),
        ),
        'description' => 'MOQ, credit, partial payment and intercity supply.',
        'icon' => 'building',
      ),
      6 => 
      array (
        'slug' => 'bandara-credit',
        'label' => 'Bandara Credit',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'What is Bandara Credit?',
            'answer' => '<p>Bandara Credit is Bandara’s B2C customer rewards balance. Eligible credits may be earned on qualifying purchases and redeemed against future eligible B2C orders in the manner shown at checkout.</p>',
          ),
          1 => 
          array (
            'question' => 'Who is eligible for Bandara Credit?',
            'answer' => '<p>Eligible registered B2C customers may earn and redeem Bandara Credit. B2B accounts and B2B purchases are excluded.</p>',
          ),
          2 => 
          array (
            'question' => 'How is Bandara Credit earned?',
            'answer' => '<p>The current programme awards base credit equal to 1% of eligible merchandise spend, subject to the programme rules shown on the website. Delivery charges, handling charges, taxes and other non-merchandise amounts may be excluded from eligible spend.</p>
<p>A qualifying first order and a qualifying repeat purchase may receive additional promotional credit where the applicable programme conditions are met.</p>',
          ),
          3 => 
          array (
            'question' => 'When is earned credit added to my account?',
            'answer' => '<p>Earned credit may remain pending until the relevant payment and order-completion conditions have been satisfied. It is posted after the order reaches the qualifying successful status defined by the programme.</p>',
          ),
          4 => 
          array (
            'question' => 'What are the Bandara Credit tiers?',
            'answer' => '<p>Tiers are based on eligible B2C spend during the applicable rolling assessment period:</p>
<ul>
<li><strong>Silver:</strong> ₹0 to ₹999</li>
<li><strong>Gold:</strong> ₹1,000 to ₹4,999</li>
<li><strong>Platinum:</strong> ₹5,000 and above</li>
</ul>
<p>The customer account will show the current tier and any applicable benefits.</p>',
          ),
          5 => 
          array (
            'question' => 'Are birthday credits available?',
            'answer' => '<p>Eligible customers who have provided their date of birth may receive a birthday credit according to their current tier and the programme rules active at that time.</p>',
          ),
          6 => 
          array (
            'question' => 'Can I exchange Bandara Credit for cash?',
            'answer' => '<p>No. Bandara Credit is not legal tender and cannot be withdrawn or exchanged for cash.</p>',
          ),
          7 => 
          array (
            'question' => 'Can I transfer Bandara Credit to another customer?',
            'answer' => '<p>No. Bandara Credit is personal to the eligible customer account and cannot be sold or transferred.</p>',
          ),
          8 => 
          array (
            'question' => 'What happens to Bandara Credit if an order is cancelled or refunded?',
            'answer' => '<p>Credit earned from the cancelled or refunded purchase may be reversed. Credit redeemed against that order is restored in proportion to the amount actually refunded, subject to any fraud, misuse or valid account adjustment.</p>',
          ),
          9 => 
          array (
            'question' => 'Can multiple promotions be combined?',
            'answer' => '<p>Sometimes. Whether promotions, coupons and Bandara Credit can be combined depends on the rules attached to the relevant offer and will be shown during checkout.</p>',
          ),
          10 => 
          array (
            'question' => 'Can Bandara change the programme?',
            'answer' => '<p>Yes. Bandara may amend, suspend or withdraw Bandara Credit, an offer or a programme benefit. Material changes will be reflected in the current programme terms and, where appropriate, communicated through the website or customer account.</p>
<hr />',
          ),
        ),
        'description' => 'Eligibility, earning, tiers, redemption and reversals.',
        'icon' => 'sparkle',
      ),
      7 => 
      array (
        'slug' => 'accounts-privacy-and-support',
        'label' => 'Accounts, privacy and support',
        'items' => 
        array (
          0 => 
          array (
            'question' => 'What customer information does Bandara collect?',
            'answer' => '<p>Depending on how you use the website, Bandara may collect your name, email address, phone number, date of birth, billing and delivery addresses, order and invoice information, payment status or references, account activity and customer-support communications.</p>
<p>B2B accounts may also contain business details and commercial terms associated with the account.</p>',
          ),
          1 => 
          array (
            'question' => 'Why does Bandara ask for my date of birth?',
            'answer' => '<p>Bandara collects date of birth for eligible birthday-related Bandara Credit or account benefits and for reasonable account validation. It is not used for general marketing. The Privacy Policy explains how it is used and retained.</p>',
          ),
          2 => 
          array (
            'question' => 'Does Bandara track my location?',
            'answer' => '<p>Bandara may convert the delivery address you enter into geographic coordinates or distance information to verify serviceability, calculate delivery charges and assist delivery. This is different from continuously tracking your device location.</p>',
          ),
          3 => 
          array (
            'question' => 'Can delivery personnel see my phone number and address?',
            'answer' => '<p>Assigned delivery personnel may receive the information reasonably necessary to complete the delivery, including the recipient’s name, address, phone number and relevant delivery instructions.</p>',
          ),
          4 => 
          array (
            'question' => 'Does Bandara send marketing messages?',
            'answer' => '<p>Bandara does not currently send general marketing messages through email, SMS or WhatsApp. Service-related communications concerning account verification, payment, orders, delivery, refunds, support and security may still be sent where necessary.</p>',
          ),
          5 => 
          array (
            'question' => 'Which service providers does Bandara use?',
            'answer' => '<p>Bandara currently uses service providers that may include Razorpay for eligible online payments, Google services for maps or distance, translation and reCAPTCHA, and IBM for hosting or backup. The complete Privacy Policy will explain the purpose of these services and the information involved.</p>',
          ),
          6 => 
          array (
            'question' => 'How can I correct my information or request account deletion?',
            'answer' => '<p>Submit a support ticket through your Bandara account or contact Customer Support. Some order, invoice, tax, fraud-prevention or legal records may need to be retained even after an account-deletion request.</p>',
          ),
          7 => 
          array (
            'question' => 'How can I contact Bandara Customer Support?',
            'answer' => '<p><strong>Email:</strong> support@bandarallp.com<br />
<strong>Phone/WhatsApp:</strong> +91 9823170102<br />
<strong>Hours:</strong> Monday to Sunday, 10:00 a.m. to 6:00 p.m. IST<br />
<strong>Support address:</strong> 402B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001</p>',
          ),
          8 => 
          array (
            'question' => 'How do I escalate an unresolved complaint?',
            'answer' => '<p>You may escalate an unresolved concern to Bandara’s Grievance Officer:</p>
<p><strong>Parag Parulekar</strong><br />
Chief Executive Officer<br />
Bandara LLP<br />
<strong>Email:</strong> parag@bandarallp.com<br />
<strong>Address:</strong> 402B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001</p>
<p>Please include your order number, account email or phone number, a clear description of the issue and any relevant supporting documents.</p>',
          ),
          9 => 
          array (
            'question' => 'What are Bandara’s legal and regulatory details?',
            'answer' => '<p><strong>Legal entity:</strong> Bandara LLP<br />
<strong>Registered address:</strong> 303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001<br />
<strong>GSTIN:</strong> 27ABEFB3240N1ZE<br />
<strong>FSSAI licence:</strong> 21526079001348<br />
<strong>Shopping website:</strong> bandara.shop<br />
<strong>Corporate website:</strong> bandarallp.com</p>
<hr />',
          ),
        ),
        'description' => 'Personal information, support and grievance escalation.',
        'icon' => 'shield',
      ),
    ),
  ),
  'bandara_credit' => 
  array (
    'enabled' => true,
    'shadow_mode' => false,
    'earn_enabled' => true,
    'redeem_enabled' => true,
    'repeat_bonus_enabled' => false,
    'welcome_bonus_enabled' => true,
    'birthday_bonus_enabled' => true,
    'tiers_enabled' => true,
    'auto_post_enabled' => true,
    'redemption' => 
    array (
      'minimum_points' => 1,
      'point_value' => 1.0,
      'max_order_percentage' => 20.0,
      'minimum_payable_amount' => 1.0,
      'reservation_ttl_minutes' => 180,
    ),
    'next_reward_at' => 500,
    'history_limit' => 8,
    'earning' => 
    array (
      'per_amount_spent' => 100,
      'credit_amount' => 1,
      'repeat_window_days' => 10,
      'welcome_credit' => 100,
      'welcome_min_order_value' => 999,
    ),
    'tiers' => 
    array (
      'silver' => 
      array (
        'threshold' => 0,
        'birthday_credit' => 100,
      ),
      'gold' => 
      array (
        'threshold' => 10000,
        'birthday_credit' => 150,
      ),
      'platinum' => 
      array (
        'threshold' => 25000,
        'birthday_credit' => 200,
      ),
    ),
    'order_model' => 'App\\Models\\Order',
    'order_mapping' => 
    array (
      'user_id' => 'user_id',
      'status' => 'status',
      'placed_at' => 'placed_at',
      'eligible_spend' => 'subtotal',
    ),
    'pending_statuses' => 
    array (
      0 => 'processing',
      1 => 'shipped',
    ),
    'successful_statuses' => 
    array (
      0 => 'delivered',
      1 => 'completed',
    ),
    'cancelled_statuses' => 
    array (
      0 => 'cancelled',
    ),
    'eligibility' => 
    array (
      'mode' => 'role',
      'allowed_roles' => 
      array (
        0 => 'Customer',
      ),
      'column' => 'customer_type',
      'b2c_value' => 'b2c',
    ),
  ),
  'cache' => 
  array (
    'default' => 'database',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
        'lock_connection' => NULL,
        'lock_table' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/framework/cache/data',
        'lock_path' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/framework/cache/data',
      ),
      'storage' => 
      array (
        'driver' => 'storage',
        'disk' => NULL,
        'path' => 'framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
    ),
    'prefix' => 'bandara-cache-',
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'bandarafrozen26',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
        'transaction_mode' => 'DEFERRED',
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1:',
        'port' => '8889',
        'database' => 'bandarafrozen26',
        'username' => 'bandarafrozen_usr',
        'password' => 'Champagne2873!',
        'unix_socket' => '/Applications/MAMP/tmp/mysql/mysql.sock',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1:',
        'port' => '8889',
        'database' => 'bandarafrozen26',
        'username' => 'bandarafrozen_usr',
        'password' => 'Champagne2873!',
        'unix_socket' => '/Applications/MAMP/tmp/mysql/mysql.sock',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1:',
        'port' => '8889',
        'database' => 'bandarafrozen26',
        'username' => 'bandarafrozen_usr',
        'password' => 'Champagne2873!',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1:',
        'port' => '8889',
        'database' => 'bandarafrozen26',
        'username' => 'bandarafrozen_usr',
        'password' => 'Champagne2873!',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'bandara-database-',
        'persistent' => false,
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
  ),
  'delivery' => 
  array (
    'store_pincode' => '411001',
    'store_origin_address' => '402B, Nityanand Complex, Narangi Baug Road, Pune 411001',
    'store_origin_lat' => '18.5358492',
    'store_origin_lng' => '73.8798846',
    'distance_enabled' => true,
    'distance_provider' => 'google',
    'distance_required' => false,
    'distance_fallback_to_zone' => true,
    'distance_timeout_seconds' => 6,
    'google_maps_api_key' => 'AIzaSyA3yuY7f4cuQt5xrzQYs4R3g_mjPIWVTPo',
    'google_geocoding_api_key' => 'AIzaSyA3yuY7f4cuQt5xrzQYs4R3g_mjPIWVTPo',
    'require_serviceable_pincode' => false,
    'default_delivery_tax_rate' => 0.0,
    'default_handling_tax_rate' => 0.0,
    'delivery_sac_code' => '',
    'handling_sac_code' => '',
    'default_handling_temperature_mode' => 'frozen',
  ),
  'dompdf' => 
  array (
    'show_warnings' => false,
    'public_path' => NULL,
    'convert_entities' => true,
    'options' => 
    array (
      'font_dir' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/fonts',
      'font_cache' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/fonts',
      'temp_dir' => '/var/folders/jk/62k78j0d4_gc7g34qcrfq4ww0000gn/T',
      'chroot' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen',
      'allowed_protocols' => 
      array (
        'data://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'file://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'http://' => 
        array (
          'rules' => 
          array (
          ),
        ),
        'https://' => 
        array (
          'rules' => 
          array (
          ),
        ),
      ),
      'artifactPathValidation' => NULL,
      'log_output_file' => NULL,
      'enable_font_subsetting' => false,
      'pdf_backend' => 'CPDF',
      'default_media_type' => 'screen',
      'default_paper_size' => 'a4',
      'default_paper_orientation' => 'portrait',
      'default_font' => 'serif',
      'dpi' => 96,
      'enable_php' => false,
      'enable_javascript' => true,
      'enable_remote' => false,
      'allowed_remote_hosts' => NULL,
      'font_height_ratio' => 1.1,
      'enable_html5_parser' => true,
    ),
  ),
  'fb_permissions' => 
  array (
    'guard' => 'web',
    'roles' => 
    array (
      'Admin' => 
      array (
        'label' => 'Admin',
        'description' => 'Full system access. This role is always synced to every permission.',
        'locked' => true,
      ),
      'Manager' => 
      array (
        'label' => 'Manager',
        'description' => 'Operations manager with catalog, order, customer, vendor, store, support, marketing and rewards access; no user/role administration.',
      ),
      'Support' => 
      array (
        'label' => 'Support',
        'description' => 'Support team access to customer/order context and ticket handling.',
      ),
      'Accountant' => 
      array (
        'label' => 'Accountant',
        'description' => 'Finance operations access for invoices, payments, vendor payments, reports and order/customer context.',
      ),
      'CAAccountant' => 
      array (
        'label' => 'CA Accountant',
        'description' => 'CA/accounting view access for invoices, payments, reports and order/customer context.',
      ),
      'Stores' => 
      array (
        'label' => 'Stores',
        'description' => 'Stores/inventory/production access with vendor invoice support.',
      ),
      'DeliveryAgent' => 
      array (
        'label' => 'Delivery Agent',
        'description' => 'Mobile delivery role with access only to assigned deliveries.',
      ),
      'Customer' => 
      array (
        'label' => 'Customer',
        'description' => 'Frontend customer role. No back-office permissions.',
      ),
    ),
    'role_aliases' => 
    array (
      'CA-Accountant' => 'CAAccountant',
      'CA Accountant' => 'CAAccountant',
      'Account' => 'Accountant',
      'admin' => 'Admin',
      'manager' => 'Manager',
      'support' => 'Support',
      'accountant' => 'Accountant',
      'stores' => 'Stores',
      'customer' => 'Customer',
    ),
    'modules' => 
    array (
      'products' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'orders' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'invoices' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'customers' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'vendors' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'coupons' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'payments' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'stores' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'tickets' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'marketing' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'content' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'rewards' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
      'users' => 
      array (
        0 => 'manage',
      ),
      'settings' => 
      array (
        0 => 'manage',
      ),
      'reports' => 
      array (
        0 => 'view',
      ),
      'labels' => 
      array (
        0 => 'view',
        1 => 'manage',
      ),
    ),
    'labels' => 
    array (
      'products' => 'Products / Catalog',
      'orders' => 'Orders',
      'invoices' => 'Invoices',
      'customers' => 'Customers / B2B',
      'vendors' => 'Vendors',
      'coupons' => 'Coupons & Discounts',
      'payments' => 'Payments',
      'stores' => 'Stores / Inventory / Production',
      'tickets' => 'Support Tickets',
      'marketing' => 'Marketing / Newsletters',
      'content' => 'Content / Announcements / Collections',
      'rewards' => 'Bandara Credit / Rewards',
      'users' => 'Users & Roles',
      'settings' => 'Settings',
      'reports' => 'Reports',
      'labels' => 'Product Labels',
    ),
    'extra_permissions' => 
    array (
      0 => 'create vendor invoice',
      1 => 'manage vendor payments',
      2 => 'manage sales',
      3 => 'view assigned deliveries',
      4 => 'update assigned delivery status',
    ),
    'role_permissions' => 
    array (
      'Admin' => 
      array (
        0 => '*',
      ),
      'Manager' => 
      array (
        0 => 'view products',
        1 => 'manage products',
        2 => 'view orders',
        3 => 'manage orders',
        4 => 'manage sales',
        5 => 'view invoices',
        6 => 'manage invoices',
        7 => 'view customers',
        8 => 'manage customers',
        9 => 'view vendors',
        10 => 'manage vendors',
        11 => 'view coupons',
        12 => 'manage coupons',
        13 => 'view payments',
        14 => 'manage payments',
        15 => 'view stores',
        16 => 'manage stores',
        17 => 'view tickets',
        18 => 'manage tickets',
        19 => 'view marketing',
        20 => 'manage marketing',
        21 => 'view content',
        22 => 'manage content',
        23 => 'view rewards',
        24 => 'manage rewards',
        25 => 'view reports',
        26 => 'view labels',
        27 => 'manage labels',
        28 => 'create vendor invoice',
        29 => 'manage vendor payments',
      ),
      'Support' => 
      array (
        0 => 'view customers',
        1 => 'view orders',
        2 => 'view tickets',
        3 => 'manage tickets',
      ),
      'Accountant' => 
      array (
        0 => 'view orders',
        1 => 'view customers',
        2 => 'view invoices',
        3 => 'manage invoices',
        4 => 'view payments',
        5 => 'manage payments',
        6 => 'view vendors',
        7 => 'view reports',
        8 => 'manage vendor payments',
      ),
      'CAAccountant' => 
      array (
        0 => 'view orders',
        1 => 'view customers',
        2 => 'view invoices',
        3 => 'view payments',
        4 => 'view reports',
      ),
      'Stores' => 
      array (
        0 => 'view products',
        1 => 'view vendors',
        2 => 'view stores',
        3 => 'manage stores',
        4 => 'view labels',
        5 => 'manage labels',
        6 => 'create vendor invoice',
      ),
      'DeliveryAgent' => 
      array (
        0 => 'view assigned deliveries',
        1 => 'update assigned delivery status',
      ),
      'Customer' => 
      array (
      ),
    ),
  ),
  'features' => 
  array (
    'dynamic_pricing' => true,
    'out_of_stock_notifications' => true,
    'dark_mode' => true,
    'newsletter' => true,
    'wishlist' => true,
  ),
  'filesystems' => 
  array (
    'default' => 'public',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/app/private',
        'serve' => true,
        'throw' => false,
        'report' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/app/public',
        'url' => 'https://frozen.bandara.in/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => NULL,
        'bucket' => NULL,
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
      ),
      'invoices' => 
      array (
        'driver' => 'local',
        'root' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/app/public',
        'url' => 'https://frozen.bandara.in/storage/invoices',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
    ),
    'links' => 
    array (
      '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/public/storage' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/app/public',
    ),
  ),
  'gst' => 
  array (
    'supplier_gstin' => '27ABEFB3240N1ZE',
    'states' => 
    array (
      '01' => 
      array (
        'code' => 'JK',
        'name' => 'Jammu and Kashmir',
      ),
      '02' => 
      array (
        'code' => 'HP',
        'name' => 'Himachal Pradesh',
      ),
      '03' => 
      array (
        'code' => 'PB',
        'name' => 'Punjab',
      ),
      '04' => 
      array (
        'code' => 'CH',
        'name' => 'Chandigarh',
      ),
      '05' => 
      array (
        'code' => 'UK',
        'name' => 'Uttarakhand',
      ),
      '06' => 
      array (
        'code' => 'HR',
        'name' => 'Haryana',
      ),
      '07' => 
      array (
        'code' => 'DL',
        'name' => 'Delhi',
      ),
      '08' => 
      array (
        'code' => 'RJ',
        'name' => 'Rajasthan',
      ),
      '09' => 
      array (
        'code' => 'UP',
        'name' => 'Uttar Pradesh',
      ),
      10 => 
      array (
        'code' => 'BR',
        'name' => 'Bihar',
      ),
      11 => 
      array (
        'code' => 'SK',
        'name' => 'Sikkim',
      ),
      12 => 
      array (
        'code' => 'AR',
        'name' => 'Arunachal Pradesh',
      ),
      13 => 
      array (
        'code' => 'NL',
        'name' => 'Nagaland',
      ),
      14 => 
      array (
        'code' => 'MN',
        'name' => 'Manipur',
      ),
      15 => 
      array (
        'code' => 'MZ',
        'name' => 'Mizoram',
      ),
      16 => 
      array (
        'code' => 'TR',
        'name' => 'Tripura',
      ),
      17 => 
      array (
        'code' => 'ML',
        'name' => 'Meghalaya',
      ),
      18 => 
      array (
        'code' => 'AS',
        'name' => 'Assam',
      ),
      19 => 
      array (
        'code' => 'WB',
        'name' => 'West Bengal',
      ),
      20 => 
      array (
        'code' => 'JH',
        'name' => 'Jharkhand',
      ),
      21 => 
      array (
        'code' => 'OD',
        'name' => 'Odisha',
      ),
      22 => 
      array (
        'code' => 'CG',
        'name' => 'Chhattisgarh',
      ),
      23 => 
      array (
        'code' => 'MP',
        'name' => 'Madhya Pradesh',
      ),
      24 => 
      array (
        'code' => 'GJ',
        'name' => 'Gujarat',
      ),
      25 => 
      array (
        'code' => 'DH',
        'name' => 'Daman and Diu',
        'canonical_for_address' => false,
      ),
      26 => 
      array (
        'code' => 'DH',
        'name' => 'Dadra and Nagar Haveli and Daman and Diu',
        'canonical_for_address' => true,
      ),
      27 => 
      array (
        'code' => 'MH',
        'name' => 'Maharashtra',
      ),
      29 => 
      array (
        'code' => 'KA',
        'name' => 'Karnataka',
      ),
      30 => 
      array (
        'code' => 'GA',
        'name' => 'Goa',
      ),
      31 => 
      array (
        'code' => 'LD',
        'name' => 'Lakshadweep',
      ),
      32 => 
      array (
        'code' => 'KL',
        'name' => 'Kerala',
      ),
      33 => 
      array (
        'code' => 'TN',
        'name' => 'Tamil Nadu',
      ),
      34 => 
      array (
        'code' => 'PY',
        'name' => 'Puducherry',
      ),
      35 => 
      array (
        'code' => 'AN',
        'name' => 'Andaman and Nicobar Islands',
      ),
      36 => 
      array (
        'code' => 'TS',
        'name' => 'Telangana',
      ),
      37 => 
      array (
        'code' => 'AP',
        'name' => 'Andhra Pradesh',
      ),
      38 => 
      array (
        'code' => 'LA',
        'name' => 'Ladakh',
      ),
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/logs/laravel.log',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'formatter' => NULL,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/logs/laravel.log',
      ),
      'browser' => 
      array (
        'driver' => 'single',
        'path' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/logs/browser.log',
        'level' => 'debug',
        'days' => 14,
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'smtp',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => 'mail.bandara.in',
        'port' => '587',
        'username' => 'info@bandara.in',
        'password' => 'Champagne2873!',
        'timeout' => NULL,
        'local_domain' => 'frozen.bandara.in',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'no-reply@bandara.in',
      'name' => 'Bandara',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/vendor/mail',
      ),
      'extensions' => 
      array (
      ),
    ),
  ),
  'media' => 
  array (
    'public_disk' => 'public',
    'private_disk' => 'local',
    'public_root' => 'media',
    'migration_reports_dir' => 'media-migrations',
    'paths' => 
    array (
      'products' => 'products',
      'recipes' => 'recipes',
      'avatars' => 'avatars',
      'home' => 'home',
      'product_collections' => 'product-collections',
      'announcements' => 'announcements',
      'tickets' => 'tickets',
    ),
    'legacy_roots' => 
    array (
      'public' => 
      array (
        0 => 'products',
        1 => 'recipes',
        2 => 'avatars',
        3 => 'home',
        4 => 'images/home',
        5 => 'images/hero',
        6 => 'product-collections',
        7 => 'announcements',
        8 => 'tickets/attachments',
      ),
      'local' => 
      array (
        0 => 'products',
        1 => 'recipes',
        2 => 'avatars',
        3 => 'home',
        4 => 'product-collections',
        5 => 'announcements',
        6 => 'tickets/attachments',
      ),
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'pricing' => 
  array (
    'b2b_allow_retail_fallback' => false,
    'default_gst_rate' => 5.0,
    'fallback_missing_product_gst_rate' => true,
    'b2b_force_default_gst_when_zero_rate' => true,
  ),
  'queue' => 
  array (
    'default' => 'database',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => NULL,
        'secret' => NULL,
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 5,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
      'background' => 
      array (
        'driver' => 'background',
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
  ),
  'security' => 
  array (
    'trusted_hosts' => 
    array (
      0 => 'frozen.bandara.in',
      1 => 'localhost',
      2 => '127.0.0.1',
    ),
    'redirect_hosts' => 
    array (
      0 => 'frozen.bandara.in',
    ),
    'headers' => 
    array (
      'enabled' => true,
      'permissions_policy' => 'camera=(), microphone=(), geolocation=(), payment=(self)',
      'csp_report_only' => '',
      'hsts_enabled' => false,
      'hsts_value' => 'max-age=31536000',
    ),
    'administrator_provisioning' => 
    array (
      'name' => 'Administrator',
      'email' => 'parag@bandarallp.com',
      'password' => 'Chamapgne2873!',
      'promote_existing' => false,
    ),
    'backup' => 
    array (
      'required' => false,
      'directory' => '/var/backups/bandara',
      'max_age_hours' => 26,
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'key' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => NULL,
      'secret' => NULL,
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'razorpay' => 
    array (
      'key' => 'rzp_test_TDGRka4mxgJVoB',
      'secret' => 'SFHB62e1nEfARnAWAE3ms2tF',
    ),
    'google_translate' => 
    array (
      'driver' => 'google',
      'key' => 'AIzaSyAcz-8uoh4Y3Kmtgp8FYClT8ADDOPW1amI',
    ),
  ),
  'session' => 
  array (
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'bandara-session',
    'path' => '/',
    'domain' => NULL,
    'secure' => NULL,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
  ),
  'staff-auth' => 
  array (
    'staff_guard' => 'staff',
    'customer_guard' => 'web',
    'guard' => 
    array (
      'driver' => 'session',
      'provider' => 'users',
    ),
    'session' => 
    array (
      'cookie' => 'bandara_staff_session',
      'path' => '/',
      'domain' => NULL,
      'secure' => NULL,
      'http_only' => true,
      'same_site' => 'lax',
    ),
    'staff_roles' => 
    array (
      0 => 'Admin',
      1 => 'Super Admin',
      2 => 'SuperAdmin',
      3 => 'Manager',
      4 => 'Accountant',
      5 => 'CAAccountant',
      6 => 'CA Accountant',
      7 => 'Support',
      8 => 'Stores',
      9 => 'DeliveryAgent',
      10 => 'Delivery Agent',
      11 => 'DeliveryBoy',
      12 => 'Delivery Boy',
      13 => 'Staff',
    ),
    'customer_roles' => 
    array (
      0 => 'B2C Customer',
      1 => 'B2B Customer',
      2 => 'B2C',
      3 => 'B2B',
      4 => 'Customer',
    ),
    'allow_non_staff_customer_login' => true,
    'login_columns' => 
    array (
      0 => 'email',
      1 => 'username',
      2 => 'phone',
      3 => 'mobile',
      4 => 'whatsapp_number',
    ),
    'staff_path_prefixes' => 
    array (
      0 => 'admin',
      1 => 'support',
      2 => 'manager',
      3 => 'accountant',
      4 => 'stores',
      5 => 'delivery',
    ),
    'staff_route_name_prefixes' => 
    array (
      0 => 'admin.',
      1 => 'support.',
      2 => 'manager.',
      3 => 'accountant.',
      4 => 'stores.',
      5 => 'delivery.',
    ),
    'public_staff_route_names' => 
    array (
      0 => 'admin.login',
      1 => 'admin.login.store',
    ),
    'shared_staff_route_tokens' => 
    array (
      0 => 'ticket attachment',
      1 => 'ticket-attachment',
      2 => 'ticket_attachment',
      3 => 'ticket.attachments',
      4 => 'tickets.attachments',
    ),
    'role_dashboard_routes' => 
    array (
      0 => 
      array (
        'roles' => 
        array (
          0 => 'Admin',
          1 => 'Super Admin',
          2 => 'SuperAdmin',
        ),
        'routes' => 
        array (
          0 => 'admin.dashboard',
          1 => 'admin.index',
        ),
      ),
      1 => 
      array (
        'roles' => 
        array (
          0 => 'Manager',
        ),
        'routes' => 
        array (
          0 => 'manager.dashboard',
          1 => 'admin.dashboard',
        ),
      ),
      2 => 
      array (
        'roles' => 
        array (
          0 => 'Support',
        ),
        'routes' => 
        array (
          0 => 'support.dashboard',
        ),
      ),
      3 => 
      array (
        'roles' => 
        array (
          0 => 'Accountant',
          1 => 'CAAccountant',
          2 => 'CA Accountant',
        ),
        'routes' => 
        array (
          0 => 'accountant.dashboard',
          1 => 'admin.dashboard',
        ),
      ),
      4 => 
      array (
        'roles' => 
        array (
          0 => 'Stores',
        ),
        'routes' => 
        array (
          0 => 'stores.dashboard',
          1 => 'admin.stores.dashboard',
        ),
      ),
      5 => 
      array (
        'roles' => 
        array (
          0 => 'DeliveryAgent',
          1 => 'Delivery Agent',
          2 => 'DeliveryBoy',
          3 => 'Delivery Boy',
        ),
        'routes' => 
        array (
          0 => 'delivery.index',
        ),
      ),
      6 => 
      array (
        'roles' => 
        array (
          0 => 'Staff',
        ),
        'routes' => 
        array (
          0 => 'admin.dashboard',
          1 => 'admin.index',
        ),
      ),
    ),
    'dashboard_routes' => 
    array (
      0 => 'admin.dashboard',
      1 => 'admin.index',
    ),
    'dashboard_path' => '/admin',
    'impersonation' => 
    array (
      'enabled' => true,
      'ttl_seconds' => 120,
      'allowed_staff_roles' => 
      array (
        0 => 'Admin',
        1 => 'Super Admin',
        2 => 'SuperAdmin',
        3 => 'Manager',
      ),
      'after_start_path' => '/account',
      'after_leave_path' => '/admin',
    ),
  ),
  'store' => 
  array (
    'stock_reservation_ttl_minutes' => 5,
    'accountant_email' => 'accountant@bandara.in',
    'invoice' => 
    array (
      'seller' => 
      array (
        'signature_name' => 'For Bandara',
        'fssai_no' => '21526079001348',
        'gstin_no' => '27ABEFB3240N1ZE',
        'address' => '303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001. MH. India',
      ),
      'bank' => 
      array (
        'account_no' => '129663700000319',
        'account_name' => 'Bandara LLP',
        'ifsc' => 'YESB0001296',
        'bank_name' => 'Yes Bank Ltd.',
      ),
      'qr_payload' => 'upi://pay?mc=5811&pa=yespay.bizsbiz229338@yesbankltd&pn=BANDARA LLP',
    ),
  ),
  'trustedproxy' => 
  array (
    'proxies' => NULL,
  ),
  'boost' => 
  array (
    'enabled' => true,
    'browser_logs_watcher' => true,
    'executable_paths' => 
    array (
      'php' => NULL,
      'composer' => NULL,
      'npm' => NULL,
      'vendor_bin' => NULL,
      'current_directory' => '/Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen',
    ),
  ),
  'mcp' => 
  array (
    'redirect_domains' => 
    array (
      0 => '*',
    ),
    'custom_schemes' => 
    array (
    ),
    'authorization_server' => NULL,
  ),
  'laravellocalization' => 
  array (
    'supportedLocales' => 
    array (
      'en' => 
      array (
        'name' => 'English',
        'script' => 'Latn',
        'native' => 'English',
        'regional' => 'en_GB',
      ),
      'es' => 
      array (
        'name' => 'Spanish',
        'script' => 'Latn',
        'native' => 'español',
        'regional' => 'es_ES',
      ),
    ),
    'useAcceptLanguageHeader' => true,
    'hideDefaultLocaleInURL' => false,
    'localesOrder' => 
    array (
    ),
    'localesMapping' => 
    array (
    ),
    'utf8suffix' => '.UTF-8',
    'urlsIgnored' => 
    array (
      0 => '/skipped',
    ),
    'httpMethodsIgnored' => 
    array (
      0 => 'POST',
      1 => 'PUT',
      2 => 'PATCH',
      3 => 'DELETE',
    ),
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
    'trust_project' => 'always',
  ),
);

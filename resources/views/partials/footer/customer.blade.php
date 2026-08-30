{{-- Footer (Cartzilla-inspired, grayscale) --}}
    <footer class="mt-8 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="max-w-6xl mx-auto px-4 py-8 sm:py-10 space-y-6">

            {{-- Top footer grid --}}
            <div class="grid gap-4 sm:grid-cols-4 lg:grid-cols-4 text-xs">
                {{-- Column 1: Brand / About --}}
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="hidden sm:flex items-center gap-2 min-w-0">
                            <span class="inline-block h-12 w-12 border-gray-300 dark:border-gray-700">
                                <img src="{{ asset('storage/images/logo-bandara.png') }}" alt="Bandara Logo" class="h-full w-full invert-0 dark:invert">
                            </span>
                            <span class="font-semibold tracking-tight text-gray-900 dark:text-gray-50 text-sm">
                                Bandara<br>
                                bhāṇḍāra
                            </span>
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Premium meats, seafood, cheese and speciality foods for homes, chefs and businesses—with GST-ready invoicing and a seamless mobile-first shopping experience from Bandara.
                    </p>
                    <br>
                    <span class="space-y-2 text-[11px] text-gray-600 dark:text-gray-300">
                        <a href="{{ route('content.about') }}" class="hover:text-gray-900 dark:hover:text-gray-100">About Us</a> | 
                        <a href="{{ route('content.terms') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Terms &amp; Conditions</a> | 
                        <a href="{{ route('content.privacy') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Privacy Policy</a> | 
                        <a href="{{ route('content.help') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Help &amp; FAQs</a>
                    </span>
                    
                </div>

                {{-- Column 2: Shop --}}
                <div class="space-y-2">
                    <h3 class="text-[11px] font-semibold tracking-wide uppercase text-gray-500 dark:text-gray-400">
                        Shop
                    </h3>
                    <ul class="space-y-1 text-[11px] text-gray-600 dark:text-gray-300">
                        @if(Route::has('shop.index'))
                            <li><a href="{{ route('shop.index') }}" class="hover:text-gray-900 dark:hover:text-gray-100">All products</a></li>
                        @endif
                        <li><a href="#featured" class="hover:text-gray-900 dark:hover:text-gray-100">Featured products</a></li>
                        <li><a href="#new" class="hover:text-gray-900 dark:hover:text-gray-100">New arrivals</a></li>
                        <li><a href="#specials" class="hover:text-gray-900 dark:hover:text-gray-100">Special offers</a></li>
                    </ul>
                </div>

                {{-- Column 3: Customer --}}
                <div class="space-y-2">
                    <h3 class="text-[11px] font-semibold tracking-wide uppercase text-gray-500 dark:text-gray-400">
                        Customer
                    </h3>
                    <ul class="space-y-2 text-[11px] text-gray-600 dark:text-gray-300">
{{-- BANDARA-B2B-UI-V3:FOOTER:START --}}
<li><a href="{{ route('business-account.index') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Business Accounts</a></li>
{{-- BANDARA-B2B-UI-V3:FOOTER:END --}}

                        @if(Route::has('orders.index'))
                            <li><a href="{{ route('orders.index') }}" class="hover:text-gray-900 dark:hover:text-gray-100">My orders</a></li>
                        @endif
                        @if(config('features.wishlist', true) && Route::has('wishlist.index'))
                            <li><a href="{{ route('wishlist.index') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Wishlist</a></li>
                        @endif
                        @if(Route::has('tickets.index'))
                            <li><a href="{{ route('tickets.index') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Support tickets</a></li>
                        @endif
                        @if(Route::has('account.dashboard'))
                            <li><a href="{{ route('account.dashboard') }}" class="hover:text-gray-900 dark:hover:text-gray-100">Account dashboard</a></li>
                        @endif
                    </ul>
                </div>

                {{-- Column 4: Newsletter / Contact --}}
                <div class="space-y-2 align-right">
                    
                    <h3 class="text-[11px] font-semibold tracking-wide uppercase text-gray-500 dark:text-gray-400">
                        Stay updated
                    </h3>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Subscribe to updates once newsletter is enabled in admin. Ideal for offers, new product launches and stock updates.
                    </p>
                    
                    @include('partials.newsletter_form')

                    <div class="mt-3 text-[11px] text-gray-500 dark:text-gray-400 space-y-1">
                        <p>Mon–Sat, 10:00–19:00 IST</p>
                    </div>
                </div>
            </div>

            {{-- Bottom bar --}}

            <div class="border-t border-gray-200 dark:border-gray-800 pt-3 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                <span>
                    &copy; {{ date('Y') }} - {{ date('Y')+1 }} Bandara LLP (bhāṇḍāra). All rights reserved.
                </span>
                <span class="flex flex-wrap gap-3">
                    <span>Made with care by 
                        <a href="https://dimensions.biz" target="_blank" rel="noopener noreferrer">
                            Dimensions Software
                        </a></span>
                    {{-- <span class="hidden sm:inline">·</span>
                    <span>GST compliant · Razorpay ready · PWA capable</span> --}}
                </span>
            </div>
        </div>
</footer>
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Ticket;
use App\Observers\TicketObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);

        // Share nav badge counts (cart + wishlist) globally.
        // Runs once per request even if multiple views/partials render.

        View::composer('*', function ($view) {
            
            static $shared = null;

            if ($shared !== null) {
                $view->with($shared);
                return;
            }

            $cartCount = 0;
            $wishlistCount = 0;

            try {
                $sessionId = session()->getId();
                $userId    = Auth::id();
                $cartId    = session('cart_id');

                // ---------------------------
                // Resolve current cart
                // ---------------------------
                $cart = null;

                // 1) Prefer session cart_id if valid for current actor (user or guest)
                if ($cartId) {
                    $candidate = DB::table('carts')->where('id', $cartId)->first();

                    if ($candidate) {
                        if (Auth::check()) {
                            // Accept if belongs to user OR is guest cart for this session
                            if (
                                (int) $candidate->user_id === (int) $userId ||
                                (is_null($candidate->user_id) && (string) $candidate->session_id === (string) $sessionId)
                            ) {
                                $cart = $candidate;
                            }
                        } else {
                            // Guest: only accept guest cart for this session
                            if (is_null($candidate->user_id) && (string) $candidate->session_id === (string) $sessionId) {
                                $cart = $candidate;
                            }
                        }
                    }
                }

                // 2) If still no cart, fall back by user_id or session_id
                if (!$cart) {
                    if (Auth::check()) {
                        $userCart = DB::table('carts')
                            ->where('user_id', $userId)
                            ->orderByDesc('id')
                            ->first();

                        $guestCart = DB::table('carts')
                            ->whereNull('user_id')
                            ->where('session_id', $sessionId)
                            ->orderByDesc('id')
                            ->first();

                        // If user has no cart but guest cart exists → claim it
                        if (!$userCart && $guestCart) {
                            DB::table('carts')->where('id', $guestCart->id)->update([
                                'user_id'    => $userId,
                                'session_id' => null,
                                'updated_at' => now(),
                            ]);

                            $cart = DB::table('carts')->where('id', $guestCart->id)->first();
                        } else {
                            // Prefer user cart if present; else fall back to guest cart
                            $cart = $userCart ?: $guestCart;
                        }
                    } else {
                        $cart = DB::table('carts')
                            ->whereNull('user_id')
                            ->where('session_id', $sessionId)
                            ->orderByDesc('id')
                            ->first();
                    }
                }

                // Store cart id in session for consistent future requests
                if ($cart) {
                    session(['cart_id' => $cart->id]);
                }

                // ---------------------------
                // Cart badge count
                // ---------------------------
                if ($cart) {
                    // Option chosen: sum quantities (supports qty increments),
                    // but display as an integer badge. If sum > 0 but rounds to 0 (fractional),
                    // show 1 so badge still appears.
                    $sumQty = (float) DB::table('cart_items')
                        ->where('cart_id', $cart->id)
                        ->sum('quantity');

                    $cartCount = (int) round($sumQty);
                    if ($cartCount === 0 && $sumQty > 0) {
                        $cartCount = 1;
                    }
                }

                // ---------------------------
                // Wishlist badge count (robust)
                // ---------------------------
                if (config('features.wishlist', true) && Auth::check()) {
                    // Case A: wishlist_items has user_id
                    if (Schema::hasTable('wishlist_items') && Schema::hasColumn('wishlist_items', 'user_id')) {
                        $wishlistCount = (int) DB::table('wishlist_items')
                            ->where('user_id', $userId)
                            ->count();
                    }
                    // Case B: wishlists(user_id) + wishlist_items(wishlist_id)
                    elseif (
                        Schema::hasTable('wishlists') &&
                        Schema::hasTable('wishlist_items') &&
                        Schema::hasColumn('wishlists', 'user_id') &&
                        Schema::hasColumn('wishlist_items', 'wishlist_id')
                    ) {
                        $wishlistCount = (int) DB::table('wishlist_items')
                            ->join('wishlists', 'wishlist_items.wishlist_id', '=', 'wishlists.id')
                            ->where('wishlists.user_id', $userId)
                            ->count();
                    }
                    // Case C: pivot-style wishlists table has user_id + product_id
                    elseif (
                        Schema::hasTable('wishlists') &&
                        Schema::hasColumn('wishlists', 'user_id') &&
                        Schema::hasColumn('wishlists', 'product_id')
                    ) {
                        $wishlistCount = (int) DB::table('wishlists')
                            ->where('user_id', $userId)
                            ->count();
                    }
                }
            } catch (\Throwable $e) {
                // If DB not ready / during migrations etc., fail safe with zero counts.
                $cartCount = 0;
                $wishlistCount = 0;
            }

            $shared = [
                'cartCount' => $cartCount,
                'wishlistCount' => $wishlistCount,
            ];

            $view->with($shared);
        });
    }
}

<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\CartService;
use App\Services\PricingService;
use App\Services\B2BTermsService;
use App\Models\Cart;

class MergeGuestCartOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        try {
            /** @var CartService $cartService */
            $cartService = app(CartService::class);

            // Merge guest cart into user cart (existing logic in your CartService)
            $cartService->mergeGuestCartIntoUser($user);

            // Re-price guest cart items after B2B login and remove only items
            // that do not have a configured B2B price.
            if (($user->customer_type ?? 'b2c') !== 'b2b') {
                return;
            }

            /** @var PricingService $pricing */
            $pricing = app(PricingService::class);

            /** @var B2BTermsService $terms */
            $terms = app(B2BTermsService::class);

            $cart = Cart::forUser($user->id)->latest()->first();
            if (!$cart) {
                return;
            }

            $cart->load(['items.product', 'items.productVariant']);

            $adjusted = 0;
            $removed = 0;

            foreach ($cart->items as $item) {
                if (!$item->product) {
                    continue;
                }

                if (! $terms->canBuy($user, $item->product, $item->productVariant?->sellUnit, $item->productVariant)) {
                    $item->delete();
                    $removed++;
                    continue;
                }

                // MOQ enforce (only if override exists; default is 1)
                $min = (float) $terms->minOrderQty($user, $item->product, $item->productVariant?->sellUnit, $item->productVariant);

                $qty = (float) $item->quantity;
                if ($qty < $min) {
                    $item->quantity = $min;
                    $adjusted++;
                }

                // Re-price for B2B user
                $newPrice = (float) $pricing->priceFor($user, $item->product, $item->productVariant);
                $item->unit_price = $newPrice;
                $item->total = round(((float) $item->quantity) * $newPrice, 2);

                $item->save();
            }

            // If cart ends up empty (rare), remove cart_id from session
            if ($cart->items()->count() === 0) {
                session()->forget('cart_id');
                $cart->delete();
            } else {
                session(['cart_id' => $cart->id]);
            }

            if ($removed > 0) {
                session()->flash('status', "Some cart items were removed because B2B pricing is not configured for them.");
            } elseif ($adjusted > 0) {
                session()->flash('status', "Some cart quantities were increased to meet minimum order quantities (MOQ).");
            }

        } catch (\Throwable $e) {
            report($e);
            // Never break login flow
        }
    }
}

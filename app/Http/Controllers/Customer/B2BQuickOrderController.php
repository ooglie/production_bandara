<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\B2BTermsService;
use App\Services\CartService;
use Illuminate\Http\Request;

class B2BQuickOrderController extends Controller
{
    /**
     * Handle "quick order" add-to-cart from the customer dashboard.
     * B2B users may buy any active product that resolves to a B2B/base price;
     * customer-specific rows only override price/MOQ, not catalogue visibility.
     */
    public function quickAdd(Request $request, CartService $cartService, B2BTermsService $terms)
    {
        $user = $request->user();

        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            abort(403);
        }

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.enabled' => ['nullable', 'boolean'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $enabledLines = collect($data['lines'] ?? [])
            ->filter(fn ($l) => ! empty($l['enabled']))
            ->values();

        if ($enabledLines->isEmpty()) {
            return back()->withErrors([
                'quick_order' => 'Please select at least one product to add to cart.',
            ])->withInput();
        }

        $productIds = $enabledLines->pluck('product_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get(['id', 'name', 'type', 'standard_b2b_min_order_quantity', 'base_price', 'standard_b2b_price', 'b2b_price_includes_gst', 'b2c_price_includes_gst', 'gst_rate', 'hsn_code_id'])
            ->keyBy('id');

        $added = 0;
        $skipped = [];

        foreach ($enabledLines as $line) {
            $productId = (int) $line['product_id'];
            $product = $products->get($productId);

            if (! $product) {
                $skipped[] = "Product #{$productId} is not active or was not found.";
                continue;
            }

            $variantId = ! empty($line['product_variant_id']) ? (int) $line['product_variant_id'] : null;
            $variant = null;

            if ($variantId) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $productId)
                    ->first();

                if (! $variant) {
                    $skipped[] = "{$product->name}: invalid variant selected.";
                    continue;
                }
            }

            if (! $terms->canBuy($user, $product, $variant?->sellUnit, $variant)) {
                $skipped[] = "{$product->name}: B2B price is not available.";
                continue;
            }

            $type = (string) ($product->type ?? 'simple');
            $isVariable = ($type !== 'simple');

            if ($isVariable && ! $variantId) {
                $skipped[] = "{$product->name}: please select a variant.";
                continue;
            }

            $min = (float) $terms->minOrderQty($user, $product, $variant?->sellUnit, $variant);
            if ($min <= 0) $min = 1;

            $qty = (float) ($line['quantity'] ?? 0);
            if ($qty <= 0) $qty = $min;
            if ($qty < $min) $qty = $min;

            $cartService->addToCart($productId, $variantId, $qty);
            $added++;
        }

        if ($added <= 0) {
            return back()->withErrors([
                'quick_order' => 'No items were added. Please check variant selection / MOQ.',
            ])->withInput();
        }

        $msg = "Added {$added} item(s) to your cart.";
        if (! empty($skipped)) {
            $msg .= ' Some lines were skipped: ' . implode(' ', array_slice($skipped, 0, 3));
        }

        $targetRoute = \Illuminate\Support\Facades\Route::has('b2b.checkout.index')
            ? 'b2b.checkout.index'
            : 'cart.index';

        return redirect()
            ->route($targetRoute)
            ->with('status', $msg);
    }
}

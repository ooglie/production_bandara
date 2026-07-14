<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $items = WishlistItem::with([
                'product.images',
                'variant.attributeValues.attribute',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $isB2BWishlist = (bool) (
            $request->routeIs('b2b.*')
            || (($user->customer_type ?? 'b2c') === 'b2b')
        );

        if ($isB2BWishlist) {
            $pricing = app(PricingService::class);
            $items = $items
                ->filter(function (WishlistItem $item) use ($pricing, $user) {
                    if (! $item->product) {
                        return false;
                    }

                    return $item->variant
                        ? $pricing->variantIsAvailableToUser($user, $item->product, $item->variant)
                        : $pricing->productIsAvailableToUser($user, $item->product);
                })
                ->values();
        }

        return view('customer.wishlist.index', compact('items', 'isB2BWishlist'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'         => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $user = $request->user();

        $product = Product::query()->with('variants')->findOrFail((int) $data['product_id']);
        $variant = null;

        if (! empty($data['product_variant_id'])) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->findOrFail((int) $data['product_variant_id']);
        }

        $pricing = app(PricingService::class);
        $available = $variant
            ? $pricing->variantIsAvailableToUser($user, $product, $variant)
            : $pricing->productIsAvailableToUser($user, $product);

        if (! $available) {
            abort(404);
        }

        // Prevent duplicates
        WishlistItem::firstOrCreate([
            'user_id'            => $user->id,
            'product_id'         => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
        ]);

        return back()->with('status', 'Added to wishlist.');
    }

    public function destroy(Request $request, WishlistItem $item)
    {
        if ($item->user_id !== $request->user()->id) {
            throw new NotFoundHttpException();
        }

        $item->delete();

        return back()->with('status', 'Removed from wishlist.');
    }
}

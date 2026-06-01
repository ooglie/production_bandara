<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
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

        return view('customer.wishlist.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'         => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $user = $request->user();

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

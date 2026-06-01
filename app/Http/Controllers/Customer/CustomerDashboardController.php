<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Services\BandaraCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    public function index(Request $request, BandaraCreditService $bandaraCreditService)
    {
        $user = Auth::user();

        // Last order for this customer
        $lastOrder = Order::with('items')
            ->where('user_id', $user->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('created_at')
            ->first();

        // Most ordered product by this customer
        $favoriteProduct = null;
        $favoriteProductStats = null;

        $favoriteRow = OrderItem::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', '!=', 'cancelled');
            })
            ->whereNotNull('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_quantity, COUNT(DISTINCT order_id) as orders_count')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->first();

        if ($favoriteRow) {
            $favoriteProduct = Product::with('images')->find($favoriteRow->product_id);

            if ($favoriteProduct) {
                $favoriteProductStats = [
                    'total_quantity' => (float) $favoriteRow->total_quantity,
                    'orders_count'   => (int) $favoriteRow->orders_count,
                ];
            }
        }

        // Personal offers: offers on products this user has already ordered
        $personalOffers = collect();

        $orderedProductIds = OrderItem::whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('status', '!=', 'cancelled');
            })
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($orderedProductIds->isNotEmpty()) {
            $personalOffers = ProductOffer::with('product.images')
                ->whereIn('product_id', $orderedProductIds)
                ->latest()
                ->take(3)
                ->get();
        }

        $creditSnapshot = $bandaraCreditService->snapshotForUser($user->id);

        return view('dashboard.customer', [
            'lastOrder'            => $lastOrder,
            'favoriteProduct'      => $favoriteProduct,
            'favoriteProductStats' => $favoriteProductStats,
            'personalOffers'       => $personalOffers,
            ...$creditSnapshot,
        ]);
    }
}
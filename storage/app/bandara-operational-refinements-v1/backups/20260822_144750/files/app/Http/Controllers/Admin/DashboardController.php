<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now        = now();
        $today      = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $last12Start = $now->copy()->subMonths(11)->startOfMonth();

        // Revenue overview (paid orders only)
        $revenueToday = Order::where('payment_status', 'paid')
            ->whereDate('placed_at', $today)
            ->sum('grand_total');

        $revenueThisMonth = Order::where('payment_status', 'paid')
            ->whereBetween('placed_at', [$monthStart, $now])
            ->sum('grand_total');

        // Orders
        $ordersTodayCount = Order::whereDate('created_at', $today)->count();
        $ordersTotalCount = Order::count();

        // Customers
        $totalCustomers = User::role('Customer')->count();
        $activeCustomersThisMonth = Order::where('payment_status', 'paid')
            ->whereBetween('placed_at', [$monthStart, $now])
            ->distinct('user_id')
            ->count('user_id');

        // Orders by status / payment status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $ordersByPaymentStatus = Order::select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status');

        // Recent orders
        $recentOrders = Order::with('user')
            ->orderByDesc('placed_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // New customers
        $recentCustomers = User::role('Customer')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Top customers (most revenue)
        $topCustomers = Order::with('user')
            ->select(
                'user_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(grand_total) as total_spent')
            )
            ->whereNotNull('user_id')
            ->where('payment_status', 'paid')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // Low stock alerts
        $lowStockProducts = Product::select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold')
            ->where('is_active', true)
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        // Monthly sales (last 12 months)
        $monthlySales = Order::select(
                DB::raw("DATE_FORMAT(placed_at, '%Y-%m') as ym"),
                DB::raw('SUM(grand_total) as total')
            )
            ->where('payment_status', 'paid')
            ->where('placed_at', '>=', $last12Start)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return view('dashboard.admin', [
            'revenueToday'            => $revenueToday,
            'revenueThisMonth'        => $revenueThisMonth,
            'ordersTodayCount'        => $ordersTodayCount,
            'ordersTotalCount'        => $ordersTotalCount,
            'totalCustomers'          => $totalCustomers,
            'activeCustomersThisMonth'=> $activeCustomersThisMonth,
            'ordersByStatus'          => $ordersByStatus,
            'ordersByPaymentStatus'   => $ordersByPaymentStatus,
            'recentOrders'            => $recentOrders,
            'recentCustomers'         => $recentCustomers,
            'topCustomers'            => $topCustomers,
            'lowStockProducts'        => $lowStockProducts,
            'monthlySales'            => $monthlySales,
        ]);
    }
}

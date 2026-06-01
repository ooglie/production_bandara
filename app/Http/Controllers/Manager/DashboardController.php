<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\VendorInvoice;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today      = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        // Orders today
        $ordersTodayCount = Order::whereDate('created_at', $today)->count();

        // Orders by status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // Open orders (processing / shipped)
        $openOrders = Order::with('user')
            ->whereIn('status', ['processing', 'shipped'])
            ->orderByDesc('placed_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Low stock alerts
        $lowStockProducts = Product::select('id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold')
            ->where('is_active', true)
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        // Recent vendor invoices (for inward stock)
        $recentVendorInvoices = VendorInvoice::with('vendor')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Support overview
        $openTicketsCount       = Ticket::whereNotIn('status', ['resolved', 'closed'])->count();
        $unassignedTicketsCount = Ticket::whereNull('assigned_to_id')->count();

        $recentTickets = Ticket::with('user')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.manage', [
            'ordersTodayCount'       => $ordersTodayCount,
            'ordersByStatus'         => $ordersByStatus,
            'openOrders'             => $openOrders,
            'lowStockProducts'       => $lowStockProducts,
            'recentVendorInvoices'   => $recentVendorInvoices,
            'openTicketsCount'       => $openTicketsCount,
            'unassignedTicketsCount' => $unassignedTicketsCount,
            'recentTickets'          => $recentTickets,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;

class DashboardController extends Controller
{
    public function index()
    {
        $today      = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        // Customer revenue
        $customerRevenueToday = Order::where('payment_status', 'paid')
            ->whereDate('placed_at', $today)
            ->sum('grand_total');

        $customerRevenueThisMonth = Order::where('payment_status', 'paid')
            ->whereBetween('placed_at', [$monthStart, now()])
            ->sum('grand_total');

        // Customer invoices outstanding
        $customerInvoicesOpen = Invoice::whereIn('status', ['pending', 'due', 'past_due'])->get();
        $customerOutstanding  = (float) $customerInvoicesOpen->sum('grand_total');
        $customerOverdueCount = $customerInvoicesOpen->where('status', 'past_due')->count();

        // Vendor outstanding (using VendorInvoice accessors)
        $vendorInvoicesOpen = VendorInvoice::with('payments')
            ->whereIn('status', ['pending', 'partially_paid'])
            ->get();

        $vendorOutstanding = (float) $vendorInvoicesOpen->sum(function ($inv) {
            if (!is_null($inv->balance_amount ?? null)) {
                return (float) $inv->balance_amount;
            }

            $paid  = (float) ($inv->paid_amount ?? 0);
            $total = (float) $inv->total_amount;

            return max(0, $total - $paid);
        });

        // Recent customer payments
        $recentCustomerPayments = Payment::where('status', 'captured')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Recent vendor payments
        $recentVendorPayments = VendorPayment::with('vendor', 'invoice')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Recent invoices (customer)
        $recentInvoices = Invoice::with(['order.user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard.accountant', [
            'customerRevenueToday'    => $customerRevenueToday,
            'customerRevenueThisMonth'=> $customerRevenueThisMonth,
            'customerOutstanding'     => $customerOutstanding,
            'customerOverdueCount'    => $customerOverdueCount,
            'vendorOutstanding'       => $vendorOutstanding,
            'recentCustomerPayments'  => $recentCustomerPayments,
            'recentVendorPayments'    => $recentVendorPayments,
            'recentInvoices'          => $recentInvoices,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderPrintController extends Controller
{
    public function single(Request $request, Order $order)
    {
        $order->load(['user', 'items', 'addresses', 'printedBy']);

        return view('admin.orders.print-bulk', [
            'orders' => collect([$order]),
            'markPrintedOnPrint' => true,
        ]);
    }

    /**
     * Print selected orders.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $ids = array_values(array_unique($data['order_ids']));

        $orders = Order::with(['user', 'items', 'addresses', 'printedBy'])
            ->whereIn('id', $ids)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.orders.print-bulk', [
            'orders' => $orders,
            'markPrintedOnPrint' => true,
        ]);
    }

    /**
     * Print all currently unprinted orders.
     * IMPORTANT: Does NOT mark printed here. The print page marks printed when Print is clicked.
     */
    public function newOrders(Request $request)
    {
        $orders = Order::with(['user', 'items', 'addresses', 'printedBy'])
            ->whereNull('printed_at')
            ->orderByRaw('COALESCE(placed_at, created_at) asc')
            ->orderBy('id')
            ->get();

        return view('admin.orders.print-bulk', [
            'orders' => $orders,
            'mode' => 'new',
            'markPrintedOnPrint' => true,
        ]);
    }

    /**
     * Called by the print page AFTER user clicks Print.
     * Marks orders printed (only sets printed_at if currently null, so reprints won't overwrite).
     */
    public function markPrinted(Request $request)
    {
        $data = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $ids = array_values(array_unique($data['order_ids']));

        $marked = Order::whereIn('id', $ids)
            ->whereNull('printed_at')
            ->update([
                'printed_at'    => now(),
                'printed_by_id' => $request->user()->id,
                'updated_at'    => now(),
            ]);

        return response()->json([
            'ok'     => true,
            'marked' => $marked,
        ]);
    }

    /**
     * Undo printing (single).
     */
    public function markUnprinted(Request $request, Order $order)
    {
        $order->printed_at = null;
        $order->printed_by_id = null;
        $order->save();

        return back()->with('status', 'Order marked as unprinted.');
    }

    /**
     * Undo printing (bulk).
     */
    public function bulkMarkUnprinted(Request $request)
    {
        $data = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $ids = array_values(array_unique($data['order_ids']));

        $count = Order::whereIn('id', $ids)->update([
            'printed_at'    => null,
            'printed_by_id' => null,
            'updated_at'    => now(),
        ]);

        return back()->with('status', "{$count} order(s) marked as unprinted.");
    }
}
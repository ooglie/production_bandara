<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class B2BWeightFinalizationController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['user', 'invoice', 'items.product', 'items.sellUnit'])
            ->whereHas('items', function ($q) {
                $q->whereIn('b2b_order_mode', ['pieces', 'weight'])
                    ->whereNull('actual_weight_kg');
            })
            ->latest('orders.created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.b2b.weight-finalization.index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'invoice.items', 'items.product', 'items.sellUnit', 'items.variant']);

        return view('admin.b2b.weight-finalization.show', [
            'order' => $order,
            'pendingItems' => $this->pendingWeightItems($order),
        ]);
    }

    public function finalize(Request $request, Order $order, OrderInventoryService $inventoryService)
    {
        $order->load(['invoice.items', 'items.product', 'items.sellUnit', 'items.variant']);
        $pendingItems = $this->pendingWeightItems($order);

        if ($pendingItems->isEmpty()) {
            return back()->with('status', 'This order has no pending weight lines.');
        }

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.actual_weight_kg' => ['required', 'numeric', 'min:0.001'],
            'items.*.actual_piece_count' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            DB::transaction(function () use ($data, $order, $pendingItems, $request) {
                $subtotal = 0.0;
                $taxTotal = 0.0;

                foreach ($order->items as $item) {
                    $isPending = $pendingItems->contains('id', $item->id);

                    if ($isPending) {
                        $row = $data['items'][$item->id] ?? null;
                        if (! is_array($row)) {
                            throw ValidationException::withMessages([
                                'items.' . $item->id . '.actual_weight_kg' => 'Actual weight is required for ' . ($item->product_name ?? 'this item') . '.',
                            ]);
                        }

                        $actualWeight = round((float) ($row['actual_weight_kg'] ?? 0), 3);
                        $actualPieces = isset($row['actual_piece_count']) && $row['actual_piece_count'] !== ''
                            ? max(1, (int) $row['actual_piece_count'])
                            : max(1, (int) ($item->requested_piece_count ?: $item->quantity ?: 1));

                        $unitPrice = round((float) ($item->unit_price ?? 0), 2);
                        $lineSubtotal = round($actualWeight * $unitPrice, 2);
                        $gstRate = (float) ($item->product?->gst_rate ?? 0);
                        $lineTax = round($lineSubtotal * max(0, $gstRate) / 100, 2);
                        $lineTotal = round($lineSubtotal + $lineTax, 2);

                        $item->quantity = $actualPieces;
                        $item->item_weight = $actualWeight;
                        $item->actual_weight_kg = $actualWeight;
                        $item->subtotal = $lineSubtotal;
                        $item->tax_amount = $lineTax;
                        $item->total = $lineTotal;
                        $item->cgst_amount = $order->gst_type === 'intra_state' ? round($lineTax / 2, 2) : null;
                        $item->sgst_amount = $order->gst_type === 'intra_state' ? round($lineTax - (float) $item->cgst_amount, 2) : null;
                        $item->igst_amount = $order->gst_type === 'inter_state' ? $lineTax : null;
                        if (Schema::hasColumn('order_items', 'weight_finalized_by_id')) {
                            $item->weight_finalized_by_id = $request->user()?->id;
                        }
                        if (Schema::hasColumn('order_items', 'weight_finalized_at')) {
                            $item->weight_finalized_at = now();
                        }
                        $item->save();

                        $invoiceItem = $order->invoice?->items?->firstWhere('order_item_id', $item->id)
                            ?: InvoiceItem::query()->where('order_item_id', $item->id)->first();

                        if ($invoiceItem) {
                            $invoiceItem->quantity = $actualPieces;
                            $invoiceItem->item_weight = $actualWeight;
                            $invoiceItem->actual_weight_kg = $actualWeight;
                            $invoiceItem->unit_price = $unitPrice;
                            $invoiceItem->subtotal = $lineSubtotal;
                            $invoiceItem->tax_amount = $lineTax;
                            $invoiceItem->total = $lineTotal;
                            $invoiceItem->save();
                        }
                    }

                    $subtotal += (float) ($item->subtotal ?? 0);
                    $taxTotal += (float) ($item->tax_amount ?? 0);
                }

                $subtotal = round($subtotal, 2);
                $taxTotal = round($taxTotal, 2);
                $grandTotal = round($subtotal + $taxTotal + (float) ($order->shipping_total ?? 0) - (float) ($order->discount_total ?? 0), 2);

                $order->subtotal = $subtotal;
                $order->tax_total = $taxTotal;
                $order->grand_total = $grandTotal;
                if ($order->gst_type === 'intra_state') {
                    $order->cgst_amount = round($taxTotal / 2, 2);
                    $order->sgst_amount = round($taxTotal - (float) $order->cgst_amount, 2);
                    $order->igst_amount = null;
                } else {
                    $order->cgst_amount = null;
                    $order->sgst_amount = null;
                    $order->igst_amount = $taxTotal;
                }
                $order->save();

                $invoice = $order->invoice;
                if ($invoice) {
                    $invoice->subtotal = $subtotal;
                    $invoice->tax_total = $taxTotal;
                    $invoice->grand_total = $grandTotal;
                    if (Schema::hasColumn('invoices', 'requires_weight_finalization')) {
                        $invoice->requires_weight_finalization = false;
                    }
                    if (Schema::hasColumn('invoices', 'weight_finalized_by_id')) {
                        $invoice->weight_finalized_by_id = $request->user()?->id;
                    }
                    if (Schema::hasColumn('invoices', 'weight_finalized_at')) {
                        $invoice->weight_finalized_at = now();
                    }

                    if ($order->is_pay_later) {
                        $invoice->status = 'due';
                        $invoice->due_date = $order->payment_due_at?->toDateString()
                            ?: now()->addDays((int) ($order->payment_terms_days ?? 7))->toDateString();
                    } else {
                        $invoice->status = 'pending';
                        $invoice->due_date = now()->addDays(7)->toDateString();
                    }

                    $invoice->save();
                }
            }, 3);

            $order->refresh();

            if ($order->is_pay_later) {
                $inventoryService->commitPaidOrder($order);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors(['finalize' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.b2b.weight-finalization.show', $order)
            ->with('status', 'Actual weight saved and invoice finalized.');
    }

    protected function pendingWeightItems(Order $order)
    {
        return $order->items
            ->filter(fn ($item) => in_array((string) ($item->b2b_order_mode ?? ''), ['pieces', 'weight'], true)
                && round((float) ($item->actual_weight_kg ?? 0), 3) <= 0)
            ->values();
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\BandaraCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::query()
            ->where('user_id', $user->id)
            ->with('invoice');

        if ($request->get('period') === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        }

        $orders = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $orderstoday = $orders;

        return view('customer.orders.index', compact('orders', 'orderstoday'));
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            throw new NotFoundHttpException();
        }

        $order->load(['items', 'invoice']);

        $productIds = collect($order->items)->pluck('product_id')->unique()->all();
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return view('customer.orders.show', compact('order', 'products'));
    }

    public function adminCreate()
    {
        $customers = User::role('Customer')
            ->with(['customerAddresses' => function ($q) {
                $q->orderByDesc('is_default_shipping')
                    ->orderBy('created_at');
            }])
            ->orderBy('name')
            ->get();

        $products = Product::orderBy('name')->get();

        return view('admin.orders.create', compact('customers', 'products'));
    }

    public function adminStore(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],

            'full_name'      => ['required', 'string', 'max:191'],
            'phone'          => ['required', 'string', 'max:50'],
            'address_line1'  => ['required', 'string', 'max:255'],
            'address_line2'  => ['nullable', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:100'],
            'state'          => ['required', 'string', 'max:100'],
            'state_code'     => ['nullable', 'string', 'max:10'],
            'country'        => ['nullable', 'string', 'max:100'],
            'pincode'        => ['required', 'string', 'max:20'],
            'gstin'          => ['nullable', 'string', 'max:50'],

            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'customer_note'  => ['nullable', 'string'],

            'items'                => ['required', 'array', 'min:1'],
            'items.*.product_id'   => ['required', 'exists:products,id'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'   => ['required', 'numeric', 'min:0'],
            'items.*.item_weight'  => ['nullable', 'numeric', 'min:0'],
            'items.*.sell_unit'    => ['nullable', 'string', 'max:50'],
            'items.*.pricing_unit' => ['nullable', 'string', 'max:50'],
        ]);

        $items = collect($data['items'])
            ->filter(function ($item) {
                return ! empty($item['product_id']) && (float) ($item['quantity'] ?? 0) > 0;
            })
            ->values()
            ->all();

        if (empty($items)) {
            return back()
                ->withErrors(['items' => 'Please add at least one valid line item.'])
                ->withInput();
        }

        $order = null;

        DB::transaction(function () use ($data, $items, &$order) {
            $userId        = $data['user_id'];
            $shippingTotal = (float) ($data['shipping_total'] ?? 0);

            $productIds = collect($items)->pluck('product_id')->unique()->all();
            $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $subtotal       = 0;
            $discountTotal  = 0;
            $orderItemsData = [];

            $item_weight = 0;
            $sell_unit = null;
            $pricing_unit = null;

            foreach ($items as $item) {
                $product = $products[$item['product_id']] ?? null;
                if (! $product) {
                    continue;
                }

                $qty   = (float) $item['quantity'];
                $price = (float) $item['unit_price'];

                $lineSubtotal = $qty * $price;
                $subtotal    += $lineSubtotal;

                $item_weight = (float) ($item['item_weight'] ?? 0);
                $sell_unit   = $item['sell_unit'] ?? 'pc';
                $pricing_unit = $item['pricing_unit'] ?? null;

                $orderItemsData[] = [
                    'product_id'          => $product->id,
                    'product_variant_id'  => null,
                    'product_name'        => $product->name,
                    'sku'                 => $product->sku ?? null,
                    'attributes_snapshot' => null,
                    'quantity'            => $qty,
                    'unit_price'          => $price,
                    'subtotal'            => $lineSubtotal,
                    'discount_amount'     => 0,
                    'tax_amount'          => 0,
                    'total'               => $lineSubtotal,
                    'item_weight'         => $item_weight,
                    'sell_unit'           => $sell_unit,
                    'pricing_unit'        => $pricing_unit,
                ];
            }

            $baseState     = 'Maharashtra';
            $shippingState = $data['state'];

            $gstType = null;
            $cgst    = 0;
            $sgst    = 0;
            $igst    = 0;

            if ($subtotal > 0) {
                if (strcasecmp($shippingState, $baseState) === 0) {
                    $gstType = 'intra_state';
                    $cgst = round($subtotal * 0.025, 2);
                    $sgst = round($subtotal * 0.025, 2);
                } else {
                    $gstType = 'inter_state';
                    $igst = round($subtotal * 0.05, 2);
                }
            }

            $taxTotal   = $cgst + $sgst + $igst;
            $grandTotal = $subtotal - $discountTotal + $taxTotal + $shippingTotal;

            $order = Order::create([
                'order_number'   => $this->generateOrderNumber(),
                'user_id'        => $userId,
                'status'         => 'processing',
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total'      => $taxTotal,
                'shipping_total' => $shippingTotal,
                'grand_total'    => $grandTotal,
                'coupon_id'      => null,
                'gst_type'       => $gstType,
                'cgst_amount'    => $cgst ?: null,
                'sgst_amount'    => $sgst ?: null,
                'igst_amount'    => $igst ?: null,
                'payment_status' => 'pending',
                'customer_note'  => $data['customer_note'] ?? null,
                'placed_at'      => now(),
                'item_weight'    => $item_weight,
                'sell_unit'      => $sell_unit,
                'pricing_unit'   => $pricing_unit,
            ]);

            foreach (['billing', 'shipping'] as $type) {
                OrderAddress::create([
                    'order_id'      => $order->id,
                    'type'          => $type,
                    'full_name'     => $data['full_name'],
                    'phone'         => $data['phone'],
                    'address_line1' => $data['address_line1'],
                    'address_line2' => $data['address_line2'] ?? null,
                    'city'          => $data['city'],
                    'state'         => $data['state'],
                    'state_code'    => $data['state_code'] ?? null,
                    'country'       => $data['country'] ?? 'India',
                    'pincode'       => $data['pincode'],
                    'gstin'         => $data['gstin'] ?? null,
                ]);
            }

            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            $invoiceNumber = $this->generateInvoiceNumber();

            $invoice = Invoice::create([
                'order_id'       => $order->id,
                'invoice_number' => $invoiceNumber,
                'status'         => 'pending',
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total'      => $taxTotal,
                'grand_total'    => $grandTotal,
                'invoice_date'   => now(),
                'due_date'       => now()->addDays(7),
                'cgst_amount'    => $cgst ?: null,
                'sgst_amount'    => $sgst ?: null,
                'igst_amount'    => $igst ?: null,
                'item_weight'    => $item_weight,
                'sell_unit'      => $sell_unit,
                'pricing_unit'   => $pricing_unit,
            ]);

            app(\App\Services\InvoicePdfService::class)->generateAndStore($invoice);
        });

        if ($order) {
            $this->syncBandaraCreditForOrder($order->fresh(), 'created');
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order and invoice created for the customer.');
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    protected function generateInvoiceNumber(): string
    {
        do {
            $number = 'BA-' . now()->format('dmy') . '-' . Str::upper(Str::random(4));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }

    public function invoice(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            throw new NotFoundHttpException();
        }

        $order->load('invoice');

        $invoice = $order->invoice;

        if (! $invoice) {
            abort(404, 'Invoice not found.');
        }

        if (! empty($invoice->pdf_path) && Storage::disk('invoices')->exists($invoice->pdf_path)) {
            return Storage::disk('invoices')->download($invoice->pdf_path);
        }

        return view('customer.orders.invoice', compact('order', 'invoice'));
    }

    public function adminIndex(Request $request)
    {
        $status = (string) $request->input('status', '');
        $paymentStatus = (string) $request->input('payment_status', '');
        $search = trim((string) $request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $period = (string) $request->input('period', '');
        $unprintedOnly = $request->boolean('unprinted');

        $allowedStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];
        $allowedPaymentStatuses = ['pending', 'paid', 'failed', 'refunded'];

        $query = Order::query()->with(['user', 'printedBy']);

        if ($status !== '' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        if ($paymentStatus !== '' && in_array($paymentStatus, $allowedPaymentStatuses, true)) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($dateFrom) {
            $query->where(function ($q) use ($dateFrom) {
                $q->whereDate('placed_at', '>=', $dateFrom)
                  ->orWhere(function ($q2) use ($dateFrom) {
                      $q2->whereNull('placed_at')
                         ->whereDate('created_at', '>=', $dateFrom);
                  });
            });
        }

        if ($dateTo) {
            $query->where(function ($q) use ($dateTo) {
                $q->whereDate('placed_at', '<=', $dateTo)
                  ->orWhere(function ($q2) use ($dateTo) {
                      $q2->whereNull('placed_at')
                         ->whereDate('created_at', '<=', $dateTo);
                  });
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($period === 'today') {
            $today = now()->toDateString();

            $query->where(function ($q) use ($today) {
                $q->whereDate('placed_at', $today)
                  ->orWhere(function ($q2) use ($today) {
                      $q2->whereNull('placed_at')
                         ->whereDate('created_at', $today);
                  });
            });
        }

        if ($unprintedOnly) {
            $query->whereNull('printed_at');
        }

        $orders = $query
            ->orderByRaw('COALESCE(placed_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => [
                'status'         => $status,
                'payment_status' => $paymentStatus,
                'date_from'      => $dateFrom,
                'date_to'        => $dateTo,
                'search'         => $search,
                'unprinted'      => $unprintedOnly,
            ],
            'statuses'        => $allowedStatuses,
            'paymentStatuses' => $allowedPaymentStatuses,
        ]);
    }

    public function adminShow(Order $order)
    {
        $order->load([
            'user',
            'items.product',
            'items.Variant',
            'addresses',
            'invoice',
            'payments',
        ]);

        $shippingAddress = $order->addresses->firstWhere('type', 'shipping');
        $billingAddress  = $order->addresses->firstWhere('type', 'billing');

        $availableStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];
        $rewardLedgerSummary = null;
        $rewardLedgerRows = collect();

        if (Schema::hasTable('bandara_credit_transactions')) {
            $rewardQuery = DB::table('bandara_credit_transactions')
                ->where('order_id', $order->id);

            $rewardLedgerRows = (clone $rewardQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(12)
                ->get();

            $rewardLedgerSummary = [
                'eligible_customer' => strtolower((string) ($order->user?->customer_type ?? '')) === 'b2c',
                'posted_earned' => (int) (clone $rewardQuery)->where('status', 'posted')->where('amount', '>', 0)->sum('amount'),
                'posted_redeemed' => abs((int) (clone $rewardQuery)->where('status', 'posted')->where('amount', '<', 0)->whereIn('type', ['redeem', 'redeemed', 'redemption', 'debit', 'use', 'admin_debit'])->sum('amount')),
                'earned_reversed' => abs((int) (clone $rewardQuery)->where('status', 'posted')->where('amount', '<', 0)->whereIn('type', ['earn_reversal', 'reversal'])->sum('amount')),
                'reserved_redeem' => abs((int) (clone $rewardQuery)->where('status', 'reserved')->where('amount', '<', 0)->sum('amount')),
                'tier_points' => (int) (clone $rewardQuery)->where('status', 'posted')->sum('tier_points'),
            ];
        }

        return view('admin.orders.show', [
            'order'             => $order,
            'shippingAddress'   => $shippingAddress,
            'billingAddress'    => $billingAddress,
            'availableStatuses' => $availableStatuses,
            'rewardLedgerSummary' => $rewardLedgerSummary,
            'rewardLedgerRows' => $rewardLedgerRows,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $status = $data['status'];
        $previousStatus = (string) $order->status;

        $update = [
            'status' => $status,
        ];

        $now = now();

        if ($status === 'processing') {
            $update['shipped_at'] = null;
            $update['delivered_at'] = null;
            $update['cancelled_at'] = null;
            $update['cancelled_by_id'] = null;
        }

        if ($status === 'shipped' && ! $order->shipped_at) {
            $update['shipped_at'] = $now;
        }

        if ($status === 'delivered') {
            if (! $order->shipped_at) {
                $update['shipped_at'] = $order->shipped_at ?: $now;
            }
            $update['delivered_at'] = $now;
        }

        if ($status === 'cancelled') {
            $update['cancelled_at'] = $now;
            $update['cancelled_by_id'] = auth()->id();
        }

        $order->update($update);

        $creditResult = $this->syncBandaraCreditForOrder($order->fresh(), $previousStatus);

        $creditAction = (string) ($creditResult['action'] ?? '');

        $message = 'Order status updated.';
        if ($creditAction === 'queued') {
            $message .= ' Pending Bandara Credit updated.';
        } elseif ($creditAction === 'posted' || ($creditResult['posted'] ?? false) === true) {
            $message .= ' Bandara Credit posted.';
        } elseif (in_array($creditAction, ['cancelled', 'reversed'], true) || ($creditResult['cancelled'] ?? false) === true) {
            $message .= ' Bandara Credit adjusted.';
        }

        return back()->with('status', $message);
    }

    public function adminBulkStatusUpdate(Request $request)
    {
        $data = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'new_status'  => ['required', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $orders = Order::whereIn('id', $data['order_ids'])->get();
        $newStatus = $data['new_status'];

        $processedCount = 0;
        $statusChangedCount = 0;
        $creditQueuedCount = 0;
        $creditPostedCount = 0;
        $creditCancelledCount = 0;
        $unchangedCount = 0;

        foreach ($orders as $order) {
            $previousStatus = (string) $order->status;

            $update = [
                'status' => $newStatus,
            ];

            $now = now();

            if ($newStatus === 'processing') {
                $update['shipped_at'] = null;
                $update['delivered_at'] = null;
                $update['cancelled_at'] = null;
                $update['cancelled_by_id'] = null;
            }

            if ($newStatus === 'shipped' && ! $order->shipped_at) {
                $update['shipped_at'] = $now;
            }

            if ($newStatus === 'delivered') {
                if (! $order->shipped_at) {
                    $update['shipped_at'] = $order->shipped_at ?: $now;
                }
                $update['delivered_at'] = $now;
            }

            if ($newStatus === 'cancelled') {
                $update['cancelled_at'] = $now;
                $update['cancelled_by_id'] = auth()->id();
            }

            $order->update($update);
            $processedCount++;

            $freshOrder = $order->fresh();

            if ($previousStatus !== (string) $freshOrder->status) {
                $statusChangedCount++;
            } else {
                $unchangedCount++;
            }

            $creditResult = $this->syncBandaraCreditForOrder($freshOrder, $previousStatus);

            $creditAction = (string) ($creditResult['action'] ?? '');

            if ($creditAction === 'queued') {
                $creditQueuedCount++;
            } elseif ($creditAction === 'posted' || ($creditResult['posted'] ?? false) === true) {
                $creditPostedCount++;
            } elseif (in_array($creditAction, ['cancelled', 'reversed'], true) || ($creditResult['cancelled'] ?? false) === true) {
                $creditCancelledCount++;
            }
        }

        $message = "Processed {$processedCount} order(s). {$statusChangedCount} status change(s).";

        if ($creditQueuedCount > 0) {
            $message .= " Pending Bandara Credit updated for {$creditQueuedCount} order(s).";
        }

        if ($creditPostedCount > 0) {
            $message .= " Bandara Credit posted for {$creditPostedCount} order(s).";
        }

        if ($creditCancelledCount > 0) {
            $message .= " Bandara Credit adjusted for {$creditCancelledCount} order(s).";
        }

        if ($unchangedCount > 0) {
            $message .= " {$unchangedCount} order(s) were already in that status.";
        }

        return redirect()
            ->back()
            ->with('status', trim($message));
    }

    /**
     * Keep Bandara Credit rows aligned with order lifecycle.
     *
     * processing/shipped -> queue pending rewards when auto-posting is enabled
     * delivered          -> post rewards through configured BandaraCreditService
     * cancelled          -> cancel pending / reverse posted rewards safely
     */
    protected function syncBandaraCreditForOrder(Order $order, string $previousStatus): array
    {
        if (! $order->user_id) {
            return ['action' => 'skipped', 'reason' => 'no_user'];
        }

        try {
            return app(BandaraCreditService::class)
                ->syncOrderLifecycle($order, $previousStatus);
        } catch (\Throwable $e) {
            Log::error('Bandara credit sync failed for order', [
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => $order->status,
                'error' => $e->getMessage(),
            ]);

            report($e);

            return ['action' => 'error', 'reason' => $e->getMessage()];
        }
    }
}

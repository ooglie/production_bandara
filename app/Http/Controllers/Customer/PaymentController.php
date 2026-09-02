<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\InvoicePaidMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BandaraCreditService;
use App\Services\InvoicePdfService;
use App\Services\OrderInventoryService;
use App\Services\StockReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Show Razorpay payment page for an order with pending payment.
     */
    public function showRazorpayForm(Request $request, Order $order)
    {
        $user = $request->user();

        // Ensure this order belongs to the logged in customer
        if ($order->user_id !== $user->id) {
            abort(404);
        }

        if (($order->payment_method ?? 'razorpay') === 'pay_later') {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'This order was placed on Pay Later terms.');
        }

        $paymentStatus = strtolower((string) ($order->payment_status ?? 'pending'));
        if ($paymentStatus === 'paid') {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'This order is already paid.');
        }

        if (! in_array($paymentStatus, ['pending', 'failed', 'expired'], true)) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'This order is not available for online payment.');
        }

        $reservation = app(StockReservationService::class)->ensureOrderReservedForPayment($order);
        if (! ($reservation['ok'] ?? false)) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['payment' => $reservation['message'] ?? 'Stock is no longer available for this order. Please place the order again.']);
        }

        // A retry after a failed/expired payment must create a fresh Razorpay
        // attempt, but only after stock has been re-checked and reserved again.
        if ($order->status !== 'pending_payment' || $order->payment_status !== 'pending') {
            $order->status = 'pending_payment';
            $order->payment_status = 'pending';
            $order->razorpay_order_id = null;
            $order->razorpay_payment_id = null;
            $order->razorpay_signature = null;
            $order->save();
        }

        $reservationExpiresAt = $reservation['expires_at'] ?? null;
        $reservationExpiresAt = $reservationExpiresAt ? \Illuminate\Support\Carbon::parse($reservationExpiresAt) : null;
        $reservationTimeoutSeconds = $reservationExpiresAt
            ? max(60, min(600, now()->diffInSeconds($reservationExpiresAt, false)))
            : app(StockReservationService::class)->holdSeconds();

        $razorpayKey    = config('services.razorpay.key');
        $razorpaySecret = config('services.razorpay.secret');

        if (! $razorpayKey || ! $razorpaySecret) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Razorpay is not configured.');
        }

        $amountPaise = (int) round($order->grand_total * 100);

        if ($amountPaise <= 0) {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'Order amount is invalid for payment.');
        }

        // Create Razorpay order (and Payment row) if not done yet
        if (! $order->razorpay_order_id) {
            $response = Http::withBasicAuth($razorpayKey, $razorpaySecret)
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount'          => $amountPaise,
                    'currency'        => 'INR',
                    'receipt'         => $order->order_number,
                    'payment_capture' => 1,
                    'notes'           => [
                        'internal_order_id' => $order->id,
                        'payment_context' => 'order_payment',
                    ],
                ]);

            if (! $response->successful()) {
                return redirect()
                    ->route('orders.show', $order)
                    ->with('status', 'Unable to initiate Razorpay payment. Please try again.');
            }

            $data = $response->json();
            $order->razorpay_order_id = $data['id'] ?? null;
            $order->save();

            Payment::create([
                'order_id'       => $order->id,
                'user_id'        => $order->user_id,
                'amount'         => $order->grand_total,
                'currency'       => 'INR',
                'method'         => 'razorpay',
                'status'         => 'created',
                'transaction_id' => null,
                'razorpay_order_id' => $order->razorpay_order_id,
                'payment_data'   => [
                    'context' => 'order_payment',
                    'razorpay_order' => $data,
                ],
            ]);
        }

        return view('customer.payments.razorpay', [
            'order'           => $order,
            'razorpayKey'     => $razorpayKey,
            'razorpayOrderId' => $order->razorpay_order_id,
            'amountPaise'     => $amountPaise,
            'user'            => $user,
            'reservationExpiresAt' => $reservationExpiresAt,
            'reservationTimeoutSeconds' => $reservationTimeoutSeconds,
        ]);
    }

    /**
     * Mark an order payment attempt as failed from Razorpay's client-side
     * failure event. This releases the short stock hold and keeps the order
     * out of fulfillment until the customer retries with a fresh stock check.
     */
    public function handleRazorpayFailure(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'error' => ['nullable', 'array'],
        ]);

        $order = Order::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found.',
            ], 404);
        }

        if (! Auth::check() || (int) Auth::id() !== (int) $order->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 403);
        }

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->latest()->first();

        $this->markOnlinePaymentFailed(
            $order,
            $payment,
            'payment_failed',
            'failed',
            'razorpay_payment_failed',
            ['razorpay_failure' => $data['error'] ?? []]
        );

        return response()->json([
            'status' => 'ok',
            'redirect_url' => route('orders.show', $order),
        ]);
    }

    /**
     * Show Razorpay payment page for a customer invoice balance.
     *
     * This supports B2B Pay Later/credit invoices and normal pending invoices.
     * A customer may pay the full balance or any partial amount up to the balance.
     */
    public function showInvoiceRazorpayForm(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        $invoice->load(['order.user', 'payments']);

        if (! $invoice->order || (int) $invoice->order->user_id !== (int) $user->id) {
            abort(404);
        }

        if (($invoice->order->payment_method ?? 'razorpay') === 'pay_later'
            && (($user->customer_type ?? 'b2c') === 'b2c')) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('status', 'Payment for this order will be recorded separately by Bandara.');
        }

        if (($invoice->order->payment_method ?? 'razorpay') !== 'pay_later'
            && strtolower((string) ($invoice->order->payment_status ?? 'pending')) !== 'paid') {
            return redirect()
                ->route('orders.pay.razorpay', $invoice->order)
                ->with('status', 'We will re-check stock availability before starting payment again.');
        }

        $balance = round((float) $invoice->balance_amount, 2);

        if ($balance <= 0.00001 || $invoice->status === 'paid') {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('status', 'This invoice does not have an outstanding balance.');
        }

        $amount = $request->query('amount', $balance);
        $amount = is_numeric($amount) ? round((float) $amount, 2) : 0.0;

        if ($amount <= 0 || $amount - $balance > 0.01) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors(['amount' => 'Please enter a payment amount up to the outstanding balance of ₹' . number_format($balance, 2) . '.']);
        }

        $razorpayKey    = config('services.razorpay.key');
        $razorpaySecret = config('services.razorpay.secret');

        if (! $razorpayKey || ! $razorpaySecret) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('status', 'Razorpay is not configured.');
        }

        $amountPaise = (int) round($amount * 100);

        if ($amountPaise <= 0) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->withErrors(['amount' => 'Payment amount is invalid for Razorpay.']);
        }

        $receipt = 'INV-' . $invoice->id . '-' . now()->timestamp;

        $response = Http::withBasicAuth($razorpayKey, $razorpaySecret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount'          => $amountPaise,
                'currency'        => 'INR',
                'receipt'         => $receipt,
                'payment_capture' => 1,
                'notes'           => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'internal_order_id' => $invoice->order_id,
                    'payment_context' => 'invoice_payment',
                ],
            ]);

        if (! $response->successful()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('status', 'Unable to initiate Razorpay payment. Please try again.');
        }

        $data = $response->json();
        $razorpayOrderId = $data['id'] ?? null;

        if (! $razorpayOrderId) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('status', 'Unable to initiate Razorpay payment. Please try again.');
        }

        $payment = Payment::create([
            'order_id'          => $invoice->order_id,
            'user_id'           => $user->id,
            'amount'            => $amount,
            'currency'          => 'INR',
            'method'            => 'razorpay',
            'status'            => 'created',
            'transaction_id'    => null,
            'razorpay_order_id' => $razorpayOrderId,
            'payment_data'      => [
                'context' => 'invoice_payment',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount_requested' => $amount,
                'balance_before_payment' => $balance,
                'razorpay_order' => $data,
            ],
        ]);

        return view('customer.payments.invoice-razorpay', [
            'invoice'         => $invoice,
            'order'           => $invoice->order,
            'payment'         => $payment,
            'razorpayKey'     => $razorpayKey,
            'razorpayOrderId' => $razorpayOrderId,
            'amountPaise'     => $amountPaise,
            'amountToPay'     => $amount,
            'balance'         => $balance,
            'user'            => $user,
        ]);
    }

    /**
     * Handle client-side callback after Razorpay success and verify payment.
     *
     * This is called via fetch() from the Razorpay JS handler.
     */
    public function handleRazorpayCallback(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id'   => ['required', 'string', 'max:255'],
            'razorpay_payment_id' => ['required', 'string', 'max:255'],
            'razorpay_signature'  => ['required', 'string', 'max:512'],
        ]);

        $order = Order::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order not found.',
            ], 404);
        }

        if (! Auth::check() || (int) Auth::id() !== (int) $order->user_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized.',
            ], 403);
        }

        $secret = config('services.razorpay.secret');
        if (! $secret) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Razorpay not configured.',
            ], 500);
        }

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first()
            ?? Payment::where('order_id', $order->id)->latest()->first();

        $generatedSignature = hash_hmac(
            'sha256',
            $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'],
            $secret
        );

        if (! hash_equals($generatedSignature, $data['razorpay_signature'])) {
            $this->markOnlinePaymentFailed(
                $order,
                $payment,
                'payment_failed',
                'failed',
                'payment_verification_failed',
                ['callback' => $data]
            );

            return response()->json([
                'status'  => 'error',
                'message' => 'Payment verification failed.',
            ], 422);
        }

        $stockHold = app(StockReservationService::class)->assertOrderStillReservedForPayment($order->fresh());
        if (! ($stockHold['ok'] ?? false)) {
            $this->markOnlinePaymentFailed(
                $order,
                $payment,
                'payment_expired',
                'expired',
                'stock_reservation_expired_before_payment_verification',
                [
                    'callback' => $data,
                    'stock_reservation_error' => [
                        'message' => $stockHold['message'] ?? 'Stock hold expired before payment verification.',
                        'at' => now()->toDateTimeString(),
                    ],
                ]
            );

            Log::warning('Razorpay payment callback arrived after stock reservation expired', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $stockHold['message'] ?? 'The stock hold for this order has expired. If payment was debited, please contact support for help.',
            ], 409);
        }

        try {
            [$order, $payment, $invoice] = DB::transaction(function () use ($order, $data): array {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedOrder->user_id !== (int) Auth::id()) {
                    abort(403);
                }

                $lockedPayment = Payment::query()
                    ->where('razorpay_order_id', $data['razorpay_order_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $lockedPayment) {
                    $lockedPayment = Payment::query()
                        ->where('order_id', $lockedOrder->id)
                        ->latest('id')
                        ->lockForUpdate()
                        ->first();
                }

                if (
                    strtolower((string) $lockedOrder->payment_status) === 'paid'
                    && $lockedOrder->razorpay_payment_id
                    && ! hash_equals((string) $lockedOrder->razorpay_payment_id, $data['razorpay_payment_id'])
                ) {
                    throw new \RuntimeException('This order is already linked to a different captured payment.');
                }

                if (
                    $lockedPayment
                    && strtolower((string) $lockedPayment->status) === 'captured'
                    && $lockedPayment->transaction_id
                    && ! hash_equals((string) $lockedPayment->transaction_id, $data['razorpay_payment_id'])
                ) {
                    throw new \RuntimeException('This payment attempt is already linked to a different transaction.');
                }

                if (strtolower((string) $lockedOrder->payment_status) !== 'paid') {
                    $lockedStockHold = app(StockReservationService::class)
                        ->assertOrderStillReservedForPayment($lockedOrder);

                    if (! ($lockedStockHold['ok'] ?? false)) {
                        throw new \DomainException(
                            (string) ($lockedStockHold['message'] ?? 'The stock reservation is no longer valid.')
                        );
                    }
                }

                $lockedOrder->forceFill([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'razorpay_payment_id' => $data['razorpay_payment_id'],
                    'razorpay_signature' => $data['razorpay_signature'],
                ])->save();

                if (! $lockedPayment) {
                    $lockedPayment = Payment::create([
                        'order_id' => $lockedOrder->id,
                        'user_id' => $lockedOrder->user_id,
                        'amount' => $lockedOrder->grand_total,
                        'currency' => 'INR',
                        'method' => 'razorpay',
                        'status' => 'created',
                        'razorpay_order_id' => $data['razorpay_order_id'],
                        'payment_data' => ['context' => 'order_payment'],
                    ]);
                }

                $payload = $lockedPayment->payment_data ?? [];
                $payload['callback'] = $data;

                $lockedPayment->forceFill([
                    'status' => 'captured',
                    'transaction_id' => $data['razorpay_payment_id'],
                    'reference' => $data['razorpay_payment_id'],
                    'received_date' => now()->toDateString(),
                    'paid_at' => now(),
                    'payment_data' => $payload,
                ])->save();

                $invoice = Invoice::query()
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->first();

                if ($invoice) {
                    $amountToApply = min(
                        (float) ($lockedPayment->amount ?? $lockedOrder->grand_total),
                        (float) $invoice->grand_total
                    );

                    $lockedPayment->invoices()->syncWithoutDetaching([
                        $invoice->id => ['amount_applied' => $amountToApply],
                    ]);

                    if ($invoice->status !== 'paid') {
                        $invoice->status = 'paid';
                        $invoice->save();
                    }
                }

                return [$lockedOrder, $lockedPayment, $invoice];
            }, 3);
        } catch (\DomainException $e) {
            Log::warning('Verified Razorpay callback could not be finalized because stock was no longer reserved', [
                'order_id' => $order->id,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'reason' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage().' If payment was debited, please contact support.',
            ], 409);
        } catch (\RuntimeException $e) {
            Log::warning('Rejected conflicting Razorpay callback', [
                'order_id' => $order->id,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'reason' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'This payment callback conflicts with an already processed payment.',
            ], 409);
        } catch (\Throwable $e) {
            Log::error('Unable to finalize verified Razorpay payment', [
                'order_id' => $order->id,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'error' => $e->getMessage(),
            ]);

            // Razorpay may retry the callback. Do not mark the payment failed or
            // release stock after a verified capture when only local finalization failed.
            return response()->json([
                'status' => 'error',
                'message' => 'Payment was verified but finalization is pending. Please refresh shortly or contact support.',
            ], 503);
        }

        // Post-commit operations are expected to be idempotent. Running them on
        // duplicate callbacks also repairs a previous request that committed the
        // payment but failed before inventory, rewards, PDF, or email completed.
        try {
            app(BandaraCreditService::class)->postReservedRedemptionForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to post Bandara Credit redemption after payment success', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(OrderInventoryService::class)->commitPaidOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to commit inventory for paid order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            $payload = $payment->payment_data ?? [];
            $payload['inventory_commit_error'] = [
                'message' => $e->getMessage(),
                'at' => now()->toDateTimeString(),
            ];
            $payment->payment_data = $payload;
            $payment->save();
        }

        if ($invoice) {
            app(InvoicePdfService::class)->generateAndStore($invoice);

            if ($order->user && $order->user->email && ! $invoice->mailed_to_customer_at) {
                Mail::to($order->user->email)->send(new InvoicePaidMail($invoice));
                $invoice->mailed_to_customer_at = now();
            }

            $accountantEmail = config('store.accountant_email');
            if ($accountantEmail && ! $invoice->mailed_to_accountant_at) {
                Mail::to($accountantEmail)->send(new InvoicePaidMail($invoice));
                $invoice->mailed_to_accountant_at = now();
            }

            $invoice->save();
        }

        return response()->json([
            'status'       => 'ok',
            'redirect_url' => route('orders.show', $order),
        ]);
    }

    /**
     * Handle Razorpay callback for customer invoice payments.
     *
     * Unlike the main order callback, this can be a partial payment. It should
     * update invoice payment allocation and only mark the order paid/commit
     * unpaid online stock when the invoice balance is fully cleared.
     */
    public function handleInvoiceRazorpayCallback(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id'   => ['required', 'string', 'max:255'],
            'razorpay_payment_id' => ['required', 'string', 'max:255'],
            'razorpay_signature'  => ['required', 'string', 'max:512'],
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $payment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payment not found.',
            ], 404);
        }

        $invoiceId = (int) data_get($payment->payment_data, 'invoice_id');
        $invoice = Invoice::with(['order.user', 'payments'])->find($invoiceId);

        if (! $invoice || ! $invoice->order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invoice not found.',
            ], 404);
        }

        if (
            ! Auth::check()
            || (int) Auth::id() !== (int) $invoice->order->user_id
            || (int) $payment->user_id !== (int) Auth::id()
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized.',
            ], 403);
        }

        $secret = config('services.razorpay.secret');
        if (! $secret) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Razorpay not configured.',
            ], 500);
        }

        $generatedSignature = hash_hmac(
            'sha256',
            $data['razorpay_order_id'].'|'.$data['razorpay_payment_id'],
            $secret
        );

        if (! hash_equals($generatedSignature, $data['razorpay_signature'])) {
            DB::transaction(function () use ($payment, $data): void {
                $lockedPayment = Payment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedPayment || strtolower((string) $lockedPayment->status) === 'captured') {
                    return;
                }

                $payload = $lockedPayment->payment_data ?? [];
                $payload['invalid_callback'] = [
                    'razorpay_order_id' => $data['razorpay_order_id'],
                    'razorpay_payment_id' => $data['razorpay_payment_id'],
                    'received_at' => now()->toDateTimeString(),
                ];

                $lockedPayment->forceFill([
                    'status' => 'failed',
                    'payment_data' => $payload,
                ])->save();
            }, 3);

            return response()->json([
                'status'  => 'error',
                'message' => 'Payment verification failed.',
            ], 422);
        }

        try {
            [$invoiceId, $orderId] = DB::transaction(function () use ($payment, $data): array {
                $lockedPayment = Payment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedInvoiceId = (int) data_get($lockedPayment->payment_data, 'invoice_id');
                $lockedInvoice = Invoice::query()
                    ->whereKey($lockedInvoiceId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedOrder = Order::query()
                    ->whereKey($lockedInvoice->order_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $lockedOrder->user_id !== (int) Auth::id()
                    || (int) $lockedPayment->user_id !== (int) Auth::id()
                ) {
                    abort(403);
                }

                if (strtolower((string) $lockedPayment->status) === 'captured') {
                    if (
                        $lockedPayment->transaction_id
                        && ! hash_equals((string) $lockedPayment->transaction_id, $data['razorpay_payment_id'])
                    ) {
                        throw new \RuntimeException('This invoice payment is already linked to another transaction.');
                    }

                    return [$lockedInvoice->id, $lockedOrder->id];
                }

                $balance = round((float) $lockedInvoice->balance_amount, 2);
                $amountToApply = min(round((float) $lockedPayment->amount, 2), $balance);

                $payload = $lockedPayment->payment_data ?? [];
                $payload['callback'] = $data;
                $payload['amount_applied'] = $amountToApply;
                $payload['balance_before_apply'] = $balance;

                $lockedPayment->forceFill([
                    'status' => 'captured',
                    'transaction_id' => $data['razorpay_payment_id'],
                    'reference' => $data['razorpay_payment_id'],
                    'received_date' => now()->toDateString(),
                    'paid_at' => now(),
                    'payment_data' => $payload,
                ])->save();

                if ($amountToApply > 0) {
                    $lockedPayment->invoices()->syncWithoutDetaching([
                        $lockedInvoice->id => ['amount_applied' => $amountToApply],
                    ]);
                }

                $lockedInvoice->refresh();
                $lockedInvoice->syncStatusFromPayments();
                $lockedInvoice->refresh();

                $lockedOrder->payment_status = $lockedInvoice->status === 'paid' ? 'paid' : 'pending';

                if ($lockedInvoice->status === 'paid') {
                    $lockedOrder->razorpay_payment_id = $data['razorpay_payment_id'];
                    $lockedOrder->razorpay_signature = $data['razorpay_signature'];
                }

                $lockedOrder->save();

                return [$lockedInvoice->id, $lockedOrder->id];
            }, 3);
        } catch (\RuntimeException $e) {
            Log::warning('Rejected conflicting invoice Razorpay callback', [
                'payment_id' => $payment->id,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'reason' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'This payment callback conflicts with an already processed payment.',
            ], 409);
        } catch (\Throwable $e) {
            Log::error('Unable to finalize verified invoice Razorpay payment', [
                'payment_id' => $payment->id,
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment was verified but finalization is pending. Please refresh shortly or contact support.',
            ], 503);
        }

        $invoice = Invoice::with(['order.user', 'payments'])->findOrFail($invoiceId);
        $order = $invoice->order ?: Order::find($orderId);

        if ($invoice->status === 'paid' && $order && ($order->payment_method ?? 'razorpay') !== 'pay_later') {
            try {
                app(OrderInventoryService::class)->commitPaidOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::error('Failed to commit inventory after invoice Razorpay payment', [
                    'invoice_id' => $invoice->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            app(InvoicePdfService::class)->generateAndStore($invoice);

            if ($invoice->status === 'paid') {
                if ($order?->user && $order->user->email && ! $invoice->mailed_to_customer_at) {
                    Mail::to($order->user->email)->send(new InvoicePaidMail($invoice));
                    $invoice->mailed_to_customer_at = now();
                }

                $accountantEmail = config('store.accountant_email');
                if ($accountantEmail && ! $invoice->mailed_to_accountant_at) {
                    Mail::to($accountantEmail)->send(new InvoicePaidMail($invoice));
                    $invoice->mailed_to_accountant_at = now();
                }

                $invoice->save();
            }
        } catch (\Throwable $e) {
            Log::error('Failed to refresh/send invoice after customer invoice payment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status'       => 'ok',
            'redirect_url' => route('invoices.show', $invoice),
        ]);
    }

    protected function markOnlinePaymentFailed(
        Order $order,
        ?Payment $payment,
        string $orderStatus,
        string $paymentStatus,
        string $reason,
        array $payloadUpdates = []
    ): void {
        DB::transaction(function () use (
            $order,
            $payment,
            $orderStatus,
            $paymentStatus,
            $reason,
            $payloadUpdates
        ): void {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                return;
            }

            $lockedPayment = $payment
                ? Payment::query()->whereKey($payment->id)->lockForUpdate()->first()
                : null;

            $orderAlreadyPaid = strtolower((string) $lockedOrder->payment_status) === 'paid';
            $paymentAlreadyCaptured = $lockedPayment
                && strtolower((string) $lockedPayment->status) === 'captured';

            if ($orderAlreadyPaid || $paymentAlreadyCaptured) {
                Log::warning('Ignored stale online payment failure after capture', [
                    'order_id' => $lockedOrder->id,
                    'payment_id' => $lockedPayment?->id,
                    'reason' => $reason,
                ]);

                return;
            }

            $lockedOrder->forceFill([
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
            ])->save();

            if ($lockedPayment) {
                $payload = $lockedPayment->payment_data ?? [];
                $payload['failure_reason'] = $reason;
                $payload['failed_at'] = now()->toDateTimeString();

                foreach ($payloadUpdates as $key => $value) {
                    $payload[$key] = $value;
                }

                $lockedPayment->forceFill([
                    'status' => 'failed',
                    'payment_data' => $payload,
                ])->save();
            }

            try {
                app(BandaraCreditService::class)
                    ->releaseReservedRedemptionForOrder($lockedOrder, $reason);
            } catch (\Throwable $e) {
                Log::error('Failed to release Bandara Credit reservation after online payment failure', [
                    'order_id' => $lockedOrder->id,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                app(StockReservationService::class)->releaseForOrder($lockedOrder, $reason);
            } catch (\Throwable $e) {
                Log::error('Failed to release stock reservation after online payment failure', [
                    'order_id' => $lockedOrder->id,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                ]);
            }
        }, 3);
    }

}

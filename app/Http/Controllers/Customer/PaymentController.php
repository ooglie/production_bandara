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

        if ($order->payment_status !== 'pending') {
            return redirect()
                ->route('orders.show', $order)
                ->with('status', 'This order is not pending payment.');
        }

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
            'razorpay_order_id'   => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature'  => ['required', 'string'],
        ]);

        $order = Order::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order not found.',
            ], 404);
        }

        if (! Auth::check() || Auth::id() !== $order->user_id) {
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

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $payment) {
            $payment = Payment::where('order_id', $order->id)
                ->latest()
                ->first();
        }

        $generatedSignature = hash_hmac(
            'sha256',
            $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'],
            $secret
        );

        if (! hash_equals($generatedSignature, $data['razorpay_signature'])) {
            // Avoid changing a successfully paid order back to failed
            if ($order->payment_status !== 'paid') {
                $order->payment_status = 'failed';
                $order->save();

                try {
                    app(BandaraCreditService::class)->releaseReservedRedemptionForOrder($order->fresh(), 'payment_verification_failed');
                } catch (\Throwable $e) {
                    Log::error('Failed to release Bandara Credit reservation after payment verification failure', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($payment) {
                $payload = $payment->payment_data ?? [];
                $payload['callback'] = $data;

                $payment->status = 'failed';
                $payment->payment_data = $payload;
                $payment->save();
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Payment verification failed.',
            ], 422);
        }

        // Signature OK → mark order as paid (if not already)
        if ($order->payment_status !== 'paid') {
            $order->payment_status      = 'paid';
            $order->razorpay_payment_id = $data['razorpay_payment_id'];
            $order->razorpay_signature  = $data['razorpay_signature'];
            $order->save();
        }

        try {
            app(BandaraCreditService::class)->postReservedRedemptionForOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to post Bandara Credit redemption after payment success', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Update payment record
        if ($payment) {
            $payload = $payment->payment_data ?? [];
            $payload['callback'] = $data;

            $payment->status         = 'captured';
            $payment->transaction_id = $data['razorpay_payment_id'];
            $payment->reference      = $data['razorpay_payment_id'];
            $payment->received_date  = now()->toDateString();
            $payment->paid_at        = now();
            $payment->payment_data   = $payload;
            $payment->save();

            // Attach payment to invoice via pivot (invoice_payments)
            $invoice = $order->invoice()->first();
            if ($invoice) {
                $amountToApply = min(
                    (float) ($payment->amount ?? $order->grand_total),
                    (float) $invoice->grand_total
                );

                $payment->invoices()->syncWithoutDetaching([
                    $invoice->id => ['amount_applied' => $amountToApply],
                ]);
            }
        }

        // Mark invoice as paid + send "paid" emails
        $invoice = $order->invoice()->first();
        if ($invoice && $invoice->status !== 'paid') {
            $invoice->status = 'paid';
            $invoice->save();
        }

        // NEW: commit inventory after payment is confirmed.
        // This is intentionally wrapped in try/catch so your payment flow does not break.
        try {
            app(OrderInventoryService::class)->commitPaidOrder($order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to commit inventory for paid order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            if ($payment) {
                $payload = $payment->payment_data ?? [];
                $payload['inventory_commit_error'] = [
                    'message' => $e->getMessage(),
                    'at' => now()->toDateTimeString(),
                ];
                $payment->payment_data = $payload;
                $payment->save();
            }
        }

        // Ensure PDF exists
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
            'razorpay_order_id'   => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature'  => ['required', 'string'],
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])->first();

        if (! $payment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Payment not found.',
            ], 404);
        }

        if ($payment->status === 'captured') {
            $invoiceId = (int) data_get($payment->payment_data, 'invoice_id');
            return response()->json([
                'status' => 'ok',
                'redirect_url' => $invoiceId ? route('invoices.show', $invoiceId) : route('invoices.index'),
            ]);
        }

        $invoiceId = (int) data_get($payment->payment_data, 'invoice_id');
        $invoice = Invoice::with(['order.user', 'payments'])->find($invoiceId);

        if (! $invoice || ! $invoice->order) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invoice not found.',
            ], 404);
        }

        if (! Auth::check() || (int) Auth::id() !== (int) $invoice->order->user_id || (int) $payment->user_id !== (int) Auth::id()) {
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
            $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'],
            $secret
        );

        if (! hash_equals($generatedSignature, $data['razorpay_signature'])) {
            $payload = $payment->payment_data ?? [];
            $payload['callback'] = $data;

            $payment->status = 'failed';
            $payment->payment_data = $payload;
            $payment->save();

            return response()->json([
                'status'  => 'error',
                'message' => 'Payment verification failed.',
            ], 422);
        }

        DB::transaction(function () use ($payment, $invoice, $data) {
            /** @var \App\Models\Invoice $lockedInvoice */
            $lockedInvoice = Invoice::with(['order', 'payments'])
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $balance = round((float) $lockedInvoice->balance_amount, 2);
            $amountToApply = min(round((float) $payment->amount, 2), $balance);

            $payload = $payment->payment_data ?? [];
            $payload['callback'] = $data;
            $payload['amount_applied'] = $amountToApply;
            $payload['balance_before_apply'] = $balance;

            $payment->status = 'captured';
            $payment->transaction_id = $data['razorpay_payment_id'];
            $payment->reference = $data['razorpay_payment_id'];
            $payment->received_date = now()->toDateString();
            $payment->paid_at = now();
            $payment->payment_data = $payload;
            $payment->save();

            if ($amountToApply > 0) {
                $payment->invoices()->syncWithoutDetaching([
                    $lockedInvoice->id => ['amount_applied' => $amountToApply],
                ]);
            }

            $lockedInvoice->refresh()->load(['order', 'payments']);
            $lockedInvoice->syncStatusFromPayments();

            if ($lockedInvoice->order) {
                $lockedInvoice->order->payment_status = $lockedInvoice->status === 'paid' ? 'paid' : 'pending';

                if ($lockedInvoice->status === 'paid') {
                    $lockedInvoice->order->razorpay_payment_id = $data['razorpay_payment_id'];
                    $lockedInvoice->order->razorpay_signature = $data['razorpay_signature'];
                }

                $lockedInvoice->order->save();
            }
        });

        $invoice = $invoice->fresh(['order.user', 'payments']);
        $order = $invoice->order;

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
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Mail\InvoicePaidMail;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BandaraCreditService;
use App\Services\InvoicePdfService;
use App\Services\OrderInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'amount'         => $order->grand_total,
                'currency'       => 'INR',
                'method'         => 'razorpay',
                'status'         => 'created',
                'transaction_id' => null,
                'payment_data'   => [
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

        $payment = Payment::where('order_id', $order->id)
            ->latest()
            ->first();

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
}
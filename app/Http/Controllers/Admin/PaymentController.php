<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;


class PaymentController extends Controller
{
    /**
     * List all payments with filters.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.user'])
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->get('method')) {
            $query->where('method', $method);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', '%' . $search . '%')
                    ->orWhereHas('order', function ($q2) use ($search) {
                        $q2->where('order_number', 'like', '%' . $search . '%');
                    });
            });
        }

        $payments = $query->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Show a single payment (with order + invoice link).
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'order.user',
            'order.invoice',
        ]);

        return view('admin.payments.show', compact('payment'));
    }
}

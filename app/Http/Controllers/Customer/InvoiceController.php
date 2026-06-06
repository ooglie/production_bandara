<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $invoices = Invoice::forUser($userId)
            ->with(['order', 'payments', 'paymentSubmissions'])
            ->latest()
            ->paginate(20);

        return view('customer.invoices.index', compact('invoices'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $userId = $request->user()->id;

        if (!$invoice->order || $invoice->order->user_id !== $userId) {
            abort(404);
        }

        $invoice->load([
            'order.addresses',
            'order.user',
            'items',
            'payments',
            'paymentSubmissions' => fn ($q) => $q->latest(),
        ]);

        return view('customer.invoices.show', compact('invoice'));
    }
}

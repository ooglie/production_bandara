<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Admin invoices index with filters.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['order.user', 'payments', 'paymentSubmissions'])
            ->orderByDesc('created_at');

        // Status filter
        $allowedStatuses = ['pending', 'due', 'part_payment', 'past_due', 'paid'];
        $statusFilter = $request->get('status');
        if ($statusFilter && in_array($statusFilter, $allowedStatuses, true)) {
            $query->where('status', $statusFilter);
        }

        // Customer filter
        if ($customerId = $request->get('customer_id')) {
            $query->whereHas('order', function ($q) use ($customerId) {
                $q->where('user_id', $customerId);
            });
        }

        // Month filter (YYYY-MM) using created_at; change to invoice_date if you prefer
        if ($month = $request->get('month')) {
            try {
                [$year, $monthNumber] = explode('-', $month);
                $year        = (int) $year;
                $monthNumber = (int) $monthNumber;

                if ($year && $monthNumber) {
                    $query->whereYear('created_at', $year)
                          ->whereMonth('created_at', $monthNumber);
                }
            } catch (\Throwable $e) {
                // ignore malformed month
            }
        }

        $invoices = $query->paginate(20)->withQueryString();

        // Customers for filter
        $customers = User::role('Customer')
            ->orderBy('name')
            ->limit(100)
            ->get();

        // Months that actually have invoices
        $availableMonths = Invoice::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym")
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->pluck('ym');

        return view('admin.invoices.index', compact('invoices', 'customers', 'availableMonths'));
    }

    /**
     * Show a single invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['order.user', 'order.addresses', 'items', 'payments', 'paymentSubmissions.user', 'paymentSubmissions.approvedBy', 'paymentSubmissions.rejectedBy']);

        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Bulk status update for selected invoices.
     */
    public function bulkStatusUpdate(Request $request)
    {
        $data = $request->validate([
            'invoice_ids'   => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'exists:invoices,id'],
            'status'        => ['required', 'in:pending,due,part_payment,past_due,paid'],
        ]);

        // A partial/paid state must be backed by an actual payment row.
        // Send the user to the payment-entry screen instead of allowing a
        // status-only update that would not record any amount.
        if (in_array($data['status'], ['part_payment', 'paid'], true)) {
            return $this->showPaymentFormForInvoices($request);
        }

        // Never allow changing status of invoices that are already PAID.
        $query = Invoice::whereIn('id', $data['invoice_ids'])
            ->where('status', '!=', 'paid');

        $updatedCount = $query->update(['status' => $data['status']]);

        return redirect()
            ->back()
            ->with('status', 'Updated status for '.$updatedCount.' invoice(s). Paid invoices were not changed.');
    }

    /**
     * Record a payment allocated across multiple selected invoices.
     */
    public function recordPayment(Request $request)
    {
        $baseData = $request->validate([
            'invoice_ids'      => ['required', 'array', 'min:1'],
            'invoice_ids.*'    => ['integer', 'exists:invoices,id'],
            'amount_received'  => ['required', 'numeric', 'min:0.01'],
            'currency'         => ['nullable', 'string', 'size:3'],
            'payment_method'   => ['required', 'in:razorpay,cash,cheque,bank_transfer,upi,other'],
            'reference'        => ['nullable', 'string', 'max:191'],
            'received_date'    => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
            'cheque_number'    => ['nullable', 'string', 'max:191'],
            'cheque_date'      => ['nullable', 'date'],
            'cheque_bank_name' => ['nullable', 'string', 'max:191'],
            'cheque_branch_name' => ['nullable', 'string', 'max:191'],
        ]);

        // Extra required fields for cheque payments
        if ($baseData['payment_method'] === 'cheque') {
            $request->validate([
                'cheque_number'    => ['required', 'string', 'max:191'],
                'cheque_date'      => ['required', 'date'],
                'cheque_bank_name' => ['required', 'string', 'max:191'],
            ]);
        }

        $invoices = Invoice::with('order.user')
            ->whereIn('id', $baseData['invoice_ids'])
            ->get();

        if ($invoices->isEmpty()) {
            return redirect()
                ->back()
                ->withErrors(['invoice_ids' => 'No invoices found for the given IDs.'])
                ->withInput();
        }

        // Ensure (optionally) same customer; if not, we can still proceed but better to warn.
        $customerIds = $invoices->pluck('order.user_id')->filter()->unique();
        $customerId = $customerIds->count() === 1 ? $customerIds->first() : null;

        // Total outstanding on selected invoices
        $totalOutstanding = $invoices->sum(function (Invoice $invoice) {
            return $invoice->balance_amount ?? (float) $invoice->grand_total;
        });

        if ($baseData['amount_received'] - $totalOutstanding > 0.01) {
            return redirect()
                ->back()
                ->withErrors([
                    'amount_received' => 'Amount received cannot exceed total outstanding on selected invoices.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($baseData, $invoices, $customerId) {
            $payment = Payment::create([
                'order_id'          => null, // not tied to a single order here
                'user_id'           => $customerId,
                'amount'            => $baseData['amount_received'],
                'currency'          => $baseData['currency'] ?? 'INR',
                'method'            => $baseData['payment_method'],
                'status'            => 'captured', // or 'created' if you want a pending state
                'transaction_id'    => null,
                'payment_data'      => null,
                'reference'         => $baseData['reference'] ?: ($baseData['cheque_number'] ?? null),
                'received_date'     => $baseData['received_date'] ?? now()->toDateString(),
                'notes'             => $baseData['notes'] ?? null,
                'recorded_by_id'    => auth()->id(),
                'cheque_number'     => $baseData['cheque_number'] ?? null,
                'cheque_date'       => $baseData['cheque_date'] ?? null,
                'cheque_bank_name'  => $baseData['cheque_bank_name'] ?? null,
                'cheque_branch_name'=> $baseData['cheque_branch_name'] ?? null,
                'paid_at'           => now(),
            ]);

            $remaining = (float) $baseData['amount_received'];

            // Allocate in order of oldest invoice first
            $invoicesSorted = $invoices->sortBy('created_at');

            foreach ($invoicesSorted as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $balance = $invoice->balance_amount ?? (float) $invoice->grand_total;

                if ($balance <= 0) {
                    continue;
                }

                $apply = min($balance, $remaining);

                $payment->invoices()->attach($invoice->id, [
                    'amount_applied' => $apply,
                ]);

                $remaining -= $apply;

                $invoice->refresh()->syncStatusFromPayments();

                if ($invoice->order) {
                    $invoice->order->payment_status = $invoice->status === 'paid' ? 'paid' : 'pending';
                    $invoice->order->save();
                }
            }
        });

        // return redirect()
        //     ->back()
        //     ->with('status', 'Payment recorded and allocated to selected invoices.');
        return redirect()
            ->route('admin.invoices.index')
            ->with('status', 'Payment recorded and allocated to selected invoices.');
    }

    /**
     * When status=paid is selected, show a dedicated payment form for selected invoices.
     */
    public function showPaymentFormForInvoices(Request $request)
    {
        $data = $request->validate([
            'invoice_ids'   => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'exists:invoices,id'],
            'status'        => ['nullable', 'in:part_payment,paid'],
        ]);

        $requestedStatus = $data['status'] ?? 'part_payment';

        $invoices = Invoice::with(['order.user', 'payments'])
            ->whereIn('id', $data['invoice_ids'])
            ->get();

        if ($invoices->isEmpty()) {
            return redirect()
                ->route('admin.invoices.index')
                ->withErrors(['invoice_ids' => 'No invoices found for the selected IDs.']);
        }

        // Total outstanding across these invoices
        $totalOutstanding = $invoices->sum(function (Invoice $invoice) {
            return $invoice->balance_amount ?? (float) $invoice->grand_total;
        });

        if ($totalOutstanding <= 0.00001) {
            return redirect()
                ->route('admin.invoices.index')
                ->withErrors(['invoice_ids' => 'Selected invoice(s) do not have an outstanding balance.']);
        }

        // Optional: check if all invoices belong to one customer
        $customerIds = $invoices->pluck('order.user_id')->filter()->unique();
        $customerId = $customerIds->count() === 1 ? $customerIds->first() : null;

        return view('admin.invoices.record-payment', [
            'invoices'         => $invoices,
            'totalOutstanding' => $totalOutstanding,
            'customerId'       => $customerId,
            'requestedStatus'  => $requestedStatus,
        ]);
    }

    /**
     * Single-invoice status update endpoint. Payment statuses must go through
     * the payment-entry screen so a paid/part-payment amount is recorded.
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,due,part_payment,past_due,paid'],
        ]);

        if (in_array($data['status'], ['part_payment', 'paid'], true)) {
            $request->merge([
                'invoice_ids' => [$invoice->id],
                'status' => $data['status'],
            ]);

            return $this->showPaymentFormForInvoices($request);
        }

        if ($invoice->status === 'paid') {
            return redirect()
                ->back()
                ->withErrors(['status' => 'Paid invoices cannot be reopened from this screen.']);
        }

        $invoice->status = $data['status'];
        $invoice->save();

        return redirect()
            ->back()
            ->with('status', 'Invoice status updated.');
    }
}

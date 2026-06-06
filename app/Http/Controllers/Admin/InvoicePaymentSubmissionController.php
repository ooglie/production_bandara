<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePaymentSubmission;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoicePaymentSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoicePaymentSubmission::query()
            ->with(['invoice.order.user', 'user', 'approvedBy', 'rejectedBy', 'payment'])
            ->latest();

        if ($status = $request->get('status')) {
            if (in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
                $query->where('status', $status);
            }
        } else {
            $query->where('status', 'pending');
        }

        if ($method = $request->get('method')) {
            if (in_array($method, ['bank_transfer', 'upi', 'cheque', 'cash', 'other'], true)) {
                $query->where('method', $method);
            }
        }

        $submissions = $query->paginate(20)->withQueryString();

        return view('admin.invoice_payment_submissions.index', compact('submissions'));
    }

    public function approve(Request $request, InvoicePaymentSubmission $submission)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->load(['invoice.order', 'invoice.payments', 'user']);

        if (! $submission->isPending()) {
            return back()->withErrors(['submission' => 'Only pending payment submissions can be approved.']);
        }

        $invoice = $submission->invoice;
        if (! $invoice) {
            return back()->withErrors(['submission' => 'The related invoice could not be found.']);
        }

        $balance = (float) ($invoice->balance_amount ?? 0);
        if ($balance <= 0.00001) {
            return back()->withErrors(['submission' => 'This invoice already has no balance due.']);
        }

        if ((float) $submission->amount - $balance > 0.01) {
            return back()->withErrors([
                'submission' => 'The submitted amount is greater than the current invoice balance. Reject it or ask the customer to resubmit the correct amount.',
            ]);
        }

        DB::transaction(function () use ($submission, $invoice, $data) {
            $reference = $submission->reference ?: $submission->cheque_number;

            $payment = Payment::create([
                'order_id' => $invoice->order_id,
                'user_id' => $submission->user_id,
                'amount' => $submission->amount,
                'currency' => $submission->currency ?: 'INR',
                'method' => $submission->method,
                'status' => 'captured',
                'transaction_id' => null,
                'reference' => $reference,
                'received_date' => $submission->paid_on ?? now()->toDateString(),
                'notes' => trim(implode("\n", array_filter([
                    'Customer-submitted offline payment approved.',
                    $submission->customer_note ? 'Customer note: ' . $submission->customer_note : null,
                    ($data['admin_note'] ?? null) ? 'Approval note: ' . $data['admin_note'] : null,
                ]))) ?: null,
                'recorded_by_id' => auth()->id(),
                'cheque_number' => $submission->cheque_number,
                'cheque_date' => $submission->cheque_date,
                'cheque_bank_name' => $submission->cheque_bank_name,
                'cheque_branch_name' => $submission->cheque_branch_name,
                'paid_at' => now(),
                'payment_data' => [
                    'source' => 'customer_offline_submission',
                    'invoice_payment_submission_id' => $submission->id,
                    'bank_name' => $submission->bank_name,
                    'account_holder_name' => $submission->account_holder_name,
                    'proof_path' => $submission->proof_path,
                ],
            ]);

            $payment->invoices()->attach($invoice->id, [
                'amount_applied' => $submission->amount,
            ]);

            $invoice->refresh()->syncStatusFromPayments();

            if ($invoice->order) {
                $invoice->order->payment_status = $invoice->status === 'paid' ? 'paid' : 'pending';
                $invoice->order->save();
            }

            $submission->payment_id = $payment->id;
            $submission->status = 'approved';
            $submission->approved_by_id = auth()->id();
            $submission->approved_at = now();
            $submission->admin_note = $data['admin_note'] ?? $submission->admin_note;
            $submission->save();
        });

        return back()->with('status', 'Payment submission approved and applied to the invoice.');
    }

    public function reject(Request $request, InvoicePaymentSubmission $submission)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note' => 'rejection note',
        ]);

        if (! $submission->isPending()) {
            return back()->withErrors(['submission' => 'Only pending payment submissions can be rejected.']);
        }

        $submission->status = 'rejected';
        $submission->rejected_by_id = auth()->id();
        $submission->rejected_at = now();
        $submission->admin_note = $data['admin_note'];
        $submission->save();

        return back()->with('status', 'Payment submission rejected.');
    }

    public function downloadProof(InvoicePaymentSubmission $submission)
    {
        abort_unless($submission->proof_path && Storage::disk('local')->exists($submission->proof_path), 404);

        return Storage::disk('local')->download($submission->proof_path);
    }
}

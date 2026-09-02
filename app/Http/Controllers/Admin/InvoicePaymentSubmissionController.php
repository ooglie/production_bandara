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
use Illuminate\Validation\ValidationException;

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

        DB::transaction(function () use ($submission, $data): void {
            $lockedSubmission = InvoicePaymentSubmission::query()
                ->whereKey($submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSubmission->isPending()) {
                throw ValidationException::withMessages([
                    'submission' => 'Only pending payment submissions can be approved.',
                ]);
            }

            $invoice = Invoice::query()
                ->whereKey($lockedSubmission->invoice_id)
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                throw ValidationException::withMessages([
                    'submission' => 'The related invoice could not be found.',
                ]);
            }

            $order = $invoice->order_id
                ? \App\Models\Order::query()->whereKey($invoice->order_id)->lockForUpdate()->first()
                : null;

            $balance = round((float) $invoice->balance_amount, 2);
            if ($balance <= 0.00001) {
                throw ValidationException::withMessages([
                    'submission' => 'This invoice already has no balance due.',
                ]);
            }

            if ((float) $lockedSubmission->amount - $balance > 0.01) {
                throw ValidationException::withMessages([
                    'submission' => 'The submitted amount is greater than the current invoice balance. Reject it or ask the customer to resubmit the correct amount.',
                ]);
            }

            $reference = $lockedSubmission->reference ?: $lockedSubmission->cheque_number;

            $payment = Payment::create([
                'order_id' => $invoice->order_id,
                'user_id' => $lockedSubmission->user_id,
                'amount' => $lockedSubmission->amount,
                'currency' => $lockedSubmission->currency ?: 'INR',
                'method' => $lockedSubmission->method,
                'status' => 'captured',
                'transaction_id' => null,
                'reference' => $reference,
                'received_date' => $lockedSubmission->paid_on ?? now()->toDateString(),
                'notes' => trim(implode("\n", array_filter([
                    'Customer-submitted offline payment approved.',
                    $lockedSubmission->customer_note ? 'Customer note: '.$lockedSubmission->customer_note : null,
                    ($data['admin_note'] ?? null) ? 'Approval note: '.$data['admin_note'] : null,
                ]))) ?: null,
                'recorded_by_id' => auth()->id(),
                'cheque_number' => $lockedSubmission->cheque_number,
                'cheque_date' => $lockedSubmission->cheque_date,
                'cheque_bank_name' => $lockedSubmission->cheque_bank_name,
                'cheque_branch_name' => $lockedSubmission->cheque_branch_name,
                'paid_at' => now(),
                'payment_data' => [
                    'source' => 'customer_offline_submission',
                    'invoice_payment_submission_id' => $lockedSubmission->id,
                    'bank_name' => $lockedSubmission->bank_name,
                    'account_holder_name' => $lockedSubmission->account_holder_name,
                    'proof_path' => $lockedSubmission->proof_path,
                ],
            ]);

            $payment->invoices()->attach($invoice->id, [
                'amount_applied' => $lockedSubmission->amount,
            ]);

            $invoice->refresh();
            $invoice->syncStatusFromPayments();
            $invoice->refresh();

            if ($order) {
                $order->payment_status = $invoice->status === 'paid' ? 'paid' : 'pending';
                $order->save();
            }

            $lockedSubmission->forceFill([
                'payment_id' => $payment->id,
                'status' => 'approved',
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
                'admin_note' => $data['admin_note'] ?? $lockedSubmission->admin_note,
            ])->save();
        }, 3);

        return back()->with('status', 'Payment submission approved and applied to the invoice.');
    }

    public function reject(Request $request, InvoicePaymentSubmission $submission)
    {
        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ], [], [
            'admin_note' => 'rejection note',
        ]);

        DB::transaction(function () use ($submission, $data): void {
            $lockedSubmission = InvoicePaymentSubmission::query()
                ->whereKey($submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSubmission->isPending()) {
                throw ValidationException::withMessages([
                    'submission' => 'Only pending payment submissions can be rejected.',
                ]);
            }

            $lockedSubmission->forceFill([
                'status' => 'rejected',
                'rejected_by_id' => auth()->id(),
                'rejected_at' => now(),
                'admin_note' => $data['admin_note'],
            ])->save();
        }, 3);

        return back()->with('status', 'Payment submission rejected.');
    }

    public function downloadProof(InvoicePaymentSubmission $submission)
    {
        abort_unless($submission->proof_path && Storage::disk('local')->exists($submission->proof_path), 404);

        return Storage::disk('local')->download($submission->proof_path, null, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}

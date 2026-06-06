<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePaymentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoicePaymentSubmissionController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $invoice->loadMissing(['order', 'paymentSubmissions']);

        if (! $invoice->order || (int) $invoice->order->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $balance = (float) ($invoice->balance_amount ?? 0);
        if ($balance <= 0.00001) {
            return back()->withErrors([
                'offline_amount' => 'This invoice does not have any balance due.',
            ]);
        }

        $pendingAmount = (float) $invoice->paymentSubmissions()
            ->where('status', 'pending')
            ->sum('amount');

        $availableToSubmit = max(0, $balance - $pendingAmount);
        if ($availableToSubmit <= 0.00001) {
            return back()->withErrors([
                'offline_amount' => 'You already have pending payment details covering the outstanding balance. Please wait for approval.',
            ]);
        }

        $data = $request->validate([
            'offline_amount' => ['required', 'numeric', 'min:0.01', 'max:' . number_format($availableToSubmit, 2, '.', '')],
            'offline_method' => ['required', Rule::in(['bank_transfer', 'upi', 'cheque', 'cash', 'other'])],
            'offline_reference' => ['nullable', 'string', 'max:191'],
            'offline_paid_on' => ['required', 'date'],
            'offline_bank_name' => ['nullable', 'string', 'max:191'],
            'offline_account_holder_name' => ['nullable', 'string', 'max:191'],
            'offline_cheque_number' => ['nullable', 'string', 'max:191'],
            'offline_cheque_date' => ['nullable', 'date'],
            'offline_cheque_bank_name' => ['nullable', 'string', 'max:191'],
            'offline_cheque_branch_name' => ['nullable', 'string', 'max:191'],
            'offline_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'offline_note' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'offline_amount' => 'amount',
            'offline_method' => 'payment method',
            'offline_reference' => 'reference / UTR',
            'offline_paid_on' => 'payment date',
            'offline_proof' => 'payment proof',
        ]);

        if (in_array($data['offline_method'], ['bank_transfer', 'upi'], true) && empty($data['offline_reference'])) {
            return back()->withErrors([
                'offline_reference' => 'Please enter the UTR / transaction reference for bank transfer or UPI payment.',
            ])->withInput();
        }

        if ($data['offline_method'] === 'cheque') {
            $request->validate([
                'offline_cheque_number' => ['required', 'string', 'max:191'],
                'offline_cheque_date' => ['required', 'date'],
                'offline_cheque_bank_name' => ['required', 'string', 'max:191'],
            ], [], [
                'offline_cheque_number' => 'cheque number',
                'offline_cheque_date' => 'cheque date',
                'offline_cheque_bank_name' => 'cheque bank name',
            ]);
        }

        $proofPath = null;
        if ($request->hasFile('offline_proof')) {
            $proofPath = $request->file('offline_proof')->store('payment-submissions', 'local');
        }

        InvoicePaymentSubmission::create([
            'invoice_id' => $invoice->id,
            'user_id' => $request->user()->id,
            'amount' => $data['offline_amount'],
            'currency' => 'INR',
            'method' => $data['offline_method'],
            'status' => 'pending',
            'reference' => $data['offline_reference'] ?? null,
            'paid_on' => $data['offline_paid_on'],
            'bank_name' => $data['offline_bank_name'] ?? null,
            'account_holder_name' => $data['offline_account_holder_name'] ?? null,
            'cheque_number' => $data['offline_cheque_number'] ?? null,
            'cheque_date' => $data['offline_cheque_date'] ?? null,
            'cheque_bank_name' => $data['offline_cheque_bank_name'] ?? null,
            'cheque_branch_name' => $data['offline_cheque_branch_name'] ?? null,
            'proof_path' => $proofPath,
            'customer_note' => $data['offline_note'] ?? null,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'Payment details submitted. They will be applied after approval by Admin / Manager / Accounts.');
    }
}

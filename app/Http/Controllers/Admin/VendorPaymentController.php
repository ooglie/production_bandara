<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorPayment;
use App\Services\VendorInvoiceBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorPaymentController extends Controller
{
    public function __construct(
        private readonly VendorInvoiceBalanceService $balances,
    ) {
    }

    public function index(Request $request)
    {
        $query = VendorPayment::with(['vendor', 'invoice'])
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            })
            ->when($request->filled('vendor_invoice_id'), function ($q) use ($request) {
                $q->where('vendor_invoice_id', $request->vendor_invoice_id);
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        $payments = $query->paginate(20)->withQueryString();
        $vendors = Vendor::orderBy('name')->get();

        return view('admin.vendor_payments.index', compact('payments', 'vendors'));
    }

    /**
     * Single or bulk create screen.
     * - Single: vendor_invoice_id
     * - Bulk: invoice_ids[] from the vendor-invoice index
     */
    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get();

        $selectedVendor = $request->filled('vendor_id')
            ? Vendor::find($request->vendor_id)
            : null;

        $invoiceIds = $request->input('invoice_ids', []);
        if (! is_array($invoiceIds)) {
            $invoiceIds = [];
        }

        $bulkMode = $invoiceIds !== [];

        if ($bulkMode) {
            $invoiceIds = array_values(array_unique(array_map('intval', $invoiceIds)));

            $selectedInvoices = $this->invoiceBalanceQuery()
                ->whereIn('id', $invoiceIds)
                ->whereNotIn('status', ['cancelled'])
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->get();

            if ($selectedInvoices->isEmpty()) {
                return redirect()
                    ->route('admin.vendor-invoices.index')
                    ->with('status', 'No valid invoices selected for bulk payment.');
            }

            $vendorIds = $selectedInvoices->pluck('vendor_id')->unique()->values();
            if ($vendorIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'invoice_ids' => 'Please select invoices from only one vendor for bulk payment.',
                ]);
            }

            $selectedVendor = Vendor::find($vendorIds->first());

            $rows = $selectedInvoices->map(function (VendorInvoice $invoice) {
                $summary = $this->balances->summary($invoice);

                return [
                    'invoice' => $invoice,
                    'original_total' => $summary['original_total'],
                    'adjustment_total' => $summary['adjustment_total'],
                    'total' => $summary['adjusted_total'],
                    'paid' => $summary['paid'],
                    'outstanding' => $summary['outstanding'],
                    'vendor_credit_due' => $summary['vendor_credit_due'],
                    'default_pay' => $summary['outstanding'],
                ];
            });

            return view('admin.vendor_payments.bulk_create', [
                'vendors' => $vendors,
                'selectedVendor' => $selectedVendor,
                'rows' => $rows,
                'invoiceIds' => $invoiceIds,
            ]);
        }

        $invoicesQuery = $this->invoiceBalanceQuery()
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if ($selectedVendor) {
            $invoicesQuery->where('vendor_id', $selectedVendor->id);
        }

        $invoices = $invoicesQuery
            ->take(200)
            ->get()
            ->filter(fn (VendorInvoice $invoice) => $this->balances->outstanding($invoice) > 0.005)
            ->values();

        $selectedInvoice = $request->filled('vendor_invoice_id')
            ? $this->invoiceBalanceQuery()
                ->whereNotIn('status', ['cancelled'])
                ->find($request->vendor_invoice_id)
            : null;

        return view('admin.vendor_payments.create', compact(
            'vendors',
            'invoices',
            'selectedVendor',
            'selectedInvoice'
        ));
    }

    /**
     * Single or bulk payment store.
     */
    public function store(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        if (! is_array($invoiceIds)) {
            $invoiceIds = [];
        }

        if ($invoiceIds !== []) {
            return $this->storeBulk($request);
        }

        return $this->storeSingle($request);
    }

    private function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['integer', 'exists:vendor_invoices,id'],
            'amounts' => ['required', 'array'],
        ]);

        $vendorId = (int) $validated['vendor_id'];
        $invoiceIds = array_values(array_unique(array_map('intval', $validated['invoice_ids'])));
        $amounts = $validated['amounts'] ?? [];
        $created = 0;

        DB::transaction(function () use ($vendorId, $invoiceIds, $validated, $amounts, &$created) {
            $invoices = VendorInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages(['invoice_ids' => 'No valid invoices found.']);
            }

            $vendorIds = $invoices->pluck('vendor_id')->unique();
            if ($vendorIds->count() !== 1 || (int) $vendorIds->first() !== $vendorId) {
                throw ValidationException::withMessages([
                    'vendor_id' => 'Selected invoices must belong to the chosen vendor.',
                ]);
            }

            foreach ($invoices as $invoice) {
                if ((string) $invoice->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'invoice_ids' => "Invoice {$invoice->invoice_number} is cancelled and cannot receive a payment.",
                    ]);
                }

                $invoice->load(['postedAdjustments', 'payments']);
                $summary = $this->balances->summary($invoice);

                $raw = $amounts[(string) $invoice->id] ?? $amounts[$invoice->id] ?? null;
                $paymentAmount = round((float) $raw, 2);

                if ($paymentAmount <= 0) {
                    continue;
                }

                if ($paymentAmount > $summary['outstanding'] + 0.005) {
                    throw ValidationException::withMessages([
                        "amounts.{$invoice->id}" => "Payment for {$invoice->invoice_number} exceeds its adjusted outstanding amount of ₹" . number_format($summary['outstanding'], 2) . '.',
                    ]);
                }

                VendorPayment::create([
                    'vendor_id' => $vendorId,
                    'vendor_invoice_id' => $invoice->id,
                    'amount' => $paymentAmount,
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $this->balances->syncStatus($invoice->fresh());
                $created++;
            }
        }, 3);

        if ($created === 0) {
            throw ValidationException::withMessages([
                'amounts' => 'Enter a positive payment amount for at least one selected invoice.',
            ]);
        }

        return redirect()
            ->route('admin.vendor-payments.index')
            ->with('status', 'Bulk vendor payment recorded.');
    }

    private function storeSingle(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vendor_invoice_id' => ['nullable', 'exists:vendor_invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $invoice = null;

            if (! empty($validated['vendor_invoice_id'])) {
                $invoice = VendorInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail((int) $validated['vendor_invoice_id']);

                if ((int) $invoice->vendor_id !== (int) $validated['vendor_id']) {
                    throw ValidationException::withMessages([
                        'vendor_invoice_id' => 'The selected invoice does not belong to the chosen vendor.',
                    ]);
                }

                if ((string) $invoice->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'vendor_invoice_id' => 'A cancelled vendor invoice cannot receive another payment.',
                    ]);
                }

                $invoice->load(['postedAdjustments', 'payments']);
                $summary = $this->balances->summary($invoice);
                $amount = round((float) $validated['amount'], 2);

                if ($summary['outstanding'] <= 0.005) {
                    throw ValidationException::withMessages([
                        'amount' => 'The selected invoice has no adjusted outstanding amount.',
                    ]);
                }

                if ($amount > $summary['outstanding'] + 0.005) {
                    throw ValidationException::withMessages([
                        'amount' => 'Payment exceeds the adjusted outstanding amount of ₹' . number_format($summary['outstanding'], 2) . '.',
                    ]);
                }
            }

            VendorPayment::create($validated);

            if ($invoice) {
                $this->balances->syncStatus($invoice->fresh());
            }
        }, 3);

        return redirect()
            ->route('admin.vendor-payments.index')
            ->with('status', 'Vendor payment recorded.');
    }

    private function invoiceBalanceQuery()
    {
        return VendorInvoice::query()
            ->with('vendor')
            ->withSum('payments as paid_total', 'amount')
            ->withSum([
                'adjustments as posted_adjustment_total' => fn ($adjustments) => $adjustments->where('status', VendorInvoiceAdjustment::STATUS_POSTED),
            ], 'total_delta');
    }
}

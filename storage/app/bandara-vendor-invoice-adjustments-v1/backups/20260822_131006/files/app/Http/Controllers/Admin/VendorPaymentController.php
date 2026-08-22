<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorPaymentController extends Controller
{
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
        $vendors  = Vendor::orderBy('name')->get();

        return view('admin.vendor_payments.index', compact('payments', 'vendors'));
    }

    /**
     * Single OR Bulk create screen.
     * - Single: uses vendor_invoice_id
     * - Bulk: uses invoice_ids[] (from vendor_invoices index)
     */
    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get();

        $selectedVendor = $request->filled('vendor_id')
            ? Vendor::find($request->vendor_id)
            : null;

        // Bulk mode: invoice_ids[] present
        $invoiceIds = $request->input('invoice_ids', []);
        if (!is_array($invoiceIds)) $invoiceIds = [];

        $bulkMode = !empty($invoiceIds);

        if ($bulkMode) {
            $invoiceIds = array_values(array_unique(array_map('intval', $invoiceIds)));

            $selectedInvoices = VendorInvoice::with('vendor')
                ->whereIn('id', $invoiceIds)
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->get();

            if ($selectedInvoices->isEmpty()) {
                return redirect()
                    ->route('admin.vendor-invoices.index')
                    ->with('status', 'No valid invoices selected for bulk payment.');
            }

            // Ensure single vendor
            $vendorIds = $selectedInvoices->pluck('vendor_id')->unique()->values();
            if ($vendorIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'invoice_ids' => 'Please select invoices from only one vendor for bulk payment.',
                ]);
            }

            $selectedVendor = Vendor::find($vendorIds->first());

            // preload paid amounts
            $paidMap = VendorPayment::query()
                ->selectRaw('vendor_invoice_id, SUM(amount) as paid')
                ->whereIn('vendor_invoice_id', $invoiceIds)
                ->groupBy('vendor_invoice_id')
                ->pluck('paid', 'vendor_invoice_id');

            // Compute outstanding + defaults
            $rows = $selectedInvoices->map(function ($inv) use ($paidMap) {
                $paid = (float) ($paidMap[$inv->id] ?? 0);
                $total = (float) ($inv->total_amount ?? 0);
                $outstanding = max($total - $paid, 0);

                return [
                    'invoice'      => $inv,
                    'total'        => $total,
                    'paid'         => $paid,
                    'outstanding'  => $outstanding,
                    'default_pay'  => $outstanding,
                ];
            });

            return view('admin.vendor_payments.bulk_create', [
                'vendors'         => $vendors,
                'selectedVendor'  => $selectedVendor,
                'rows'            => $rows,
                'invoiceIds'      => $invoiceIds,
            ]);
        }

        // Single payment mode (existing)
        $invoicesQuery = VendorInvoice::with('vendor')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if ($selectedVendor) {
            $invoicesQuery->where('vendor_id', $selectedVendor->id);
        }

        $invoices = $invoicesQuery->take(100)->get();

        $selectedInvoice = $request->filled('vendor_invoice_id')
            ? VendorInvoice::find($request->vendor_invoice_id)
            : null;

        return view('admin.vendor_payments.create', compact(
            'vendors',
            'invoices',
            'selectedVendor',
            'selectedInvoice'
        ));
    }

    /**
     * Single OR bulk store.
     * - Single: vendor_invoice_id + amount
     * - Bulk: invoice_ids[] + amounts[invoice_id]
     */
    public function store(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids', []);
        if (!is_array($invoiceIds)) $invoiceIds = [];
        $bulkMode = !empty($invoiceIds);

        if ($bulkMode) {
            $validated = $request->validate([
                'vendor_id'            => ['required', 'exists:vendors,id'],
                'payment_date'         => ['required', 'date'],
                'payment_method'       => ['nullable', 'string', 'max:50'],
                'reference_number'     => ['nullable', 'string', 'max:100'],
                'notes'                => ['nullable', 'string'],
                'invoice_ids'          => ['required', 'array', 'min:1'],
                'invoice_ids.*'        => ['integer', 'exists:vendor_invoices,id'],
                'amounts'              => ['required', 'array'],
            ]);

            $vendorId = (int) $validated['vendor_id'];
            $invoiceIds = array_values(array_unique(array_map('intval', $validated['invoice_ids'])));
            $amounts = $validated['amounts'] ?? [];

            DB::transaction(function () use ($vendorId, $invoiceIds, $validated, $amounts) {
                $invoices = VendorInvoice::query()
                    ->lockForUpdate()
                    ->whereIn('id', $invoiceIds)
                    ->get();

                if ($invoices->isEmpty()) {
                    throw ValidationException::withMessages(['invoice_ids' => 'No valid invoices found.']);
                }

                // Enforce single vendor
                $vendorIds = $invoices->pluck('vendor_id')->unique();
                if ($vendorIds->count() !== 1 || (int)$vendorIds->first() !== $vendorId) {
                    throw ValidationException::withMessages([
                        'vendor_id' => 'Selected invoices must belong to the chosen vendor.',
                    ]);
                }

                foreach ($invoices as $inv) {
                    $total = (float) ($inv->total_amount ?? 0);
                    $paid  = (float) $inv->payments()->sum('amount');
                    $outstanding = max($total - $paid, 0);

                    $raw = $amounts[(string)$inv->id] ?? $amounts[$inv->id] ?? null;
                    $pay = (float) $raw;

                    if ($pay <= 0) {
                        continue; // skip zeros
                    }

                    // clamp to outstanding
                    if ($pay > $outstanding) $pay = $outstanding;

                    if ($pay <= 0) continue;

                    VendorPayment::create([
                        'vendor_id'         => $vendorId,
                        'vendor_invoice_id' => $inv->id,
                        'amount'            => $pay,
                        'payment_date'      => $validated['payment_date'],
                        'payment_method'    => $validated['payment_method'] ?? null,
                        'reference_number'  => $validated['reference_number'] ?? null,
                        'notes'             => $validated['notes'] ?? null,
                    ]);

                    // Update invoice status after inserting this payment
                    $newPaid = (float) $inv->payments()->sum('amount');
                    $status = 'pending';

                    if ($total > 0 && $newPaid >= $total) {
                        $status = 'paid';
                    } elseif ($newPaid > 0 && $newPaid < $total) {
                        $status = 'partially_paid';
                    }

                    $inv->update(['status' => $status]);
                }
            });

            return redirect()
                ->route('admin.vendor-payments.index')
                ->with('status', 'Bulk vendor payment recorded.');
        }

        // ✅ Existing single payment behavior (unchanged)
        $validated = $request->validate([
            'vendor_id'        => ['required', 'exists:vendors,id'],
            'vendor_invoice_id'=> ['nullable', 'exists:vendor_invoices,id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'payment_method'   => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string'],
        ]);

        $payment = null;

        DB::transaction(function () use ($validated, &$payment) {
            $payment = VendorPayment::create($validated);

            if (!empty($validated['vendor_invoice_id'])) {
                $invoice = VendorInvoice::find($validated['vendor_invoice_id']);

                if ($invoice) {
                    $paid  = $invoice->payments()->sum('amount');
                    $total = (float) $invoice->total_amount;

                    $status = 'pending';
                    if ($total > 0 && $paid >= $total) {
                        $status = 'paid';
                    } elseif ($paid > 0 && $paid < $total) {
                        $status = 'partially_paid';
                    }

                    $invoice->update(['status' => $status]);
                }
            }
        });

        return redirect()
            ->route('admin.vendor-payments.index')
            ->with('status', 'Vendor payment recorded.');
    }
}
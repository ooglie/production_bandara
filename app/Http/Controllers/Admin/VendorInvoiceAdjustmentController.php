<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorInvoiceItem;
use App\Models\VendorReturn;
use App\Services\VendorInvoiceAdjustmentService;
use App\Services\VendorInvoiceBalanceService;
use App\Services\VendorReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorInvoiceAdjustmentController extends Controller
{
    public function __construct(
        private readonly VendorInvoiceAdjustmentService $adjustments,
        private readonly VendorInvoiceBalanceService $balances,
        private readonly VendorReturnService $returns,
    ) {
    }

    public function editDetails(VendorInvoice $vendorInvoice): View
    {
        $vendorInvoice->loadMissing('vendor');

        return view('admin.vendor_invoices.edit_details', [
            'invoice' => $vendorInvoice,
        ]);
    }

    public function updateDetails(Request $request, VendorInvoice $vendorInvoice): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vendor_invoices', 'invoice_number')
                    ->where(fn ($query) => $query->where('vendor_id', $vendorInvoice->vendor_id))
                    ->ignore($vendorInvoice->id),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'tally_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'correction_reason' => ['required', 'string', 'max:500'],
        ]);

        $after = [
            'invoice_number' => trim((string) $validated['invoice_number']),
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'] ?? null,
            'tally_reference' => $this->nullableTrim($validated['tally_reference'] ?? null),
            'notes' => $this->nullableTrim($validated['notes'] ?? null),
        ];

        DB::transaction(function () use ($vendorInvoice, $request, $validated, $after) {
            $locked = VendorInvoice::query()->lockForUpdate()->findOrFail($vendorInvoice->id);

            if ((string) $locked->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice' => 'A cancelled vendor invoice cannot be edited.',
                ]);
            }

            $normalizedBefore = [
                'invoice_number' => (string) $locked->invoice_number,
                'invoice_date' => $locked->invoice_date?->format('Y-m-d'),
                'due_date' => $locked->due_date?->format('Y-m-d'),
                'tally_reference' => $locked->tally_reference,
                'notes' => $locked->notes,
            ];

            $normalizedAfter = [
                'invoice_number' => $after['invoice_number'],
                'invoice_date' => (string) $after['invoice_date'],
                'due_date' => $after['due_date'] ? (string) $after['due_date'] : null,
                'tally_reference' => $after['tally_reference'],
                'notes' => $after['notes'],
            ];

            if ($normalizedBefore === $normalizedAfter) {
                throw ValidationException::withMessages([
                    'invoice' => 'No invoice-detail changes were detected.',
                ]);
            }

            $locked->fill($after);
            $locked->save();

            $this->adjustments->recordMetadataCorrection(
                invoice: $locked,
                actor: $request->user(),
                before: $normalizedBefore,
                after: $normalizedAfter,
                reason: (string) $validated['correction_reason'],
            );
        }, 3);

        return redirect()
            ->route('admin.vendor-invoices.show', $vendorInvoice)
            ->with('status', 'Vendor invoice details updated. The correction was added to the audit history.');
    }

    public function createFinancial(VendorInvoice $vendorInvoice, string $direction): View
    {
        $this->assertDirection($direction);
        $vendorInvoice->loadMissing([
            'vendor',
            'items.product',
            'items.productVariant',
            'items.inventoryLot',
        ]);

        return view('admin.vendor_invoices.adjustment_form', [
            'invoice' => $vendorInvoice,
            'direction' => $direction,
            'linkedReturn' => null,
            'balance' => $this->balances->summary($vendorInvoice),
        ]);
    }

    public function createReturnCredit(VendorInvoice $vendorInvoice, VendorReturn $vendorReturn): View
    {
        $this->assertReturnBelongsToInvoice($vendorInvoice, $vendorReturn);

        if ($vendorReturn->status !== VendorReturn::STATUS_CREDIT_PENDING || $vendorReturn->supplier_credit_adjustment_id) {
            throw ValidationException::withMessages([
                'vendor_return' => 'This purchase return is not awaiting a supplier credit note.',
            ]);
        }

        $vendorInvoice->loadMissing('vendor');
        $vendorReturn->loadMissing([
            'items.invoiceItem.product',
            'items.invoiceItem.productVariant',
            'items.inventoryLot',
        ]);

        return view('admin.vendor_invoices.adjustment_form', [
            'invoice' => $vendorInvoice,
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'linkedReturn' => $vendorReturn,
            'balance' => $this->balances->summary($vendorInvoice),
        ]);
    }

    public function storeFinancial(Request $request, VendorInvoice $vendorInvoice, string $direction): RedirectResponse
    {
        $this->assertDirection($direction);

        $validated = $request->validate([
            'supplier_document_number' => ['required', 'string', 'max:120'],
            'supplier_document_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'linked_vendor_return_id' => ['nullable', 'integer', 'exists:vendor_returns,id'],
            'lines' => ['nullable', 'array'],
            'lines.*.subtotal_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.revised_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'general_subtotal_amount' => ['nullable', 'numeric', 'min:0'],
            'general_tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $documentNumber = trim((string) $validated['supplier_document_number']);
        $duplicate = VendorInvoiceAdjustment::query()
            ->where('supplier_document_number', $documentNumber)
            ->whereHas('invoice', fn ($query) => $query->where('vendor_id', $vendorInvoice->vendor_id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'supplier_document_number' => 'This supplier document number is already recorded for the same vendor.',
            ]);
        }

        $linkedReturn = null;
        $lines = [];

        if (! empty($validated['linked_vendor_return_id'])) {
            $linkedReturn = VendorReturn::query()
                ->with(['items.invoiceItem'])
                ->findOrFail((int) $validated['linked_vendor_return_id']);
            $this->assertReturnBelongsToInvoice($vendorInvoice, $linkedReturn);

            if ($direction !== VendorInvoiceAdjustment::DIRECTION_CREDIT) {
                throw ValidationException::withMessages([
                    'direction' => 'A pending purchase return can only be linked to a supplier credit note.',
                ]);
            }

            if ($linkedReturn->status !== VendorReturn::STATUS_CREDIT_PENDING || $linkedReturn->supplier_credit_adjustment_id) {
                throw ValidationException::withMessages([
                    'linked_vendor_return_id' => 'This purchase return is no longer awaiting a supplier credit note.',
                ]);
            }

            $lines = $linkedReturn->items->map(fn ($item) => [
                'vendor_invoice_item_id' => $item->vendor_invoice_item_id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'inventory_lot_id' => $item->inventory_lot_id,
                'quantity' => (float) $item->quantity,
                'weight_kg' => (float) $item->weight_kg,
                'piece_count' => (int) $item->piece_count,
                'original_unit_cost' => $item->invoiceItem?->unit_cost,
                'subtotal_amount' => (float) $item->subtotal_amount,
                'tax_amount' => (float) $item->tax_amount,
                'meta' => ['linked_vendor_return_item_id' => $item->id],
            ])->all();
        } else {
            $invoiceItems = VendorInvoiceItem::query()
                ->where('vendor_invoice_id', $vendorInvoice->id)
                ->with(['product', 'productVariant', 'inventoryLot'])
                ->get()
                ->keyBy('id');

            foreach ((array) ($validated['lines'] ?? []) as $invoiceItemId => $submitted) {
                $item = $invoiceItems->get((int) $invoiceItemId);
                if (! $item || ! is_array($submitted)) {
                    continue;
                }

                $subtotal = round(max(0, (float) ($submitted['subtotal_amount'] ?? 0)), 2);
                $tax = round(max(0, (float) ($submitted['tax_amount'] ?? 0)), 2);

                if ($subtotal + $tax <= 0.005) {
                    continue;
                }

                $lines[] = [
                    'vendor_invoice_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'inventory_lot_id' => $item->inventoryLot?->id,
                    'original_unit_cost' => $item->unit_cost,
                    'revised_unit_cost' => $submitted['revised_unit_cost'] ?? null,
                    'subtotal_amount' => $subtotal,
                    'tax_amount' => $tax,
                ];
            }

            $generalSubtotal = round(max(0, (float) ($validated['general_subtotal_amount'] ?? 0)), 2);
            $generalTax = round(max(0, (float) ($validated['general_tax_amount'] ?? 0)), 2);
            if ($generalSubtotal + $generalTax > 0.005) {
                $lines[] = [
                    'subtotal_amount' => $generalSubtotal,
                    'tax_amount' => $generalTax,
                    'meta' => ['general_adjustment' => true],
                ];
            }
        }

        $adjustment = $this->adjustments->createFinancialDraft(
            invoice: $vendorInvoice,
            actor: $request->user(),
            direction: $direction,
            header: [
                'supplier_document_number' => $documentNumber,
                'supplier_document_date' => $validated['supplier_document_date'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
            ],
            lines: $lines,
            linkedReturn: $linkedReturn,
        );

        return redirect()
            ->route('admin.vendor-invoices.adjustments.show', [$vendorInvoice, $adjustment])
            ->with('status', 'Adjustment draft created. Review it before posting.');
    }

    public function showAdjustment(VendorInvoice $vendorInvoice, VendorInvoiceAdjustment $adjustment): View
    {
        $this->assertAdjustmentBelongsToInvoice($vendorInvoice, $adjustment);

        $adjustment->loadMissing([
            'invoice.vendor',
            'items.invoiceItem.product',
            'items.invoiceItem.productVariant',
            'items.inventoryLot',
            'creator',
            'postedBy',
            'reversesAdjustment',
            'reversal',
            'vendorReturn',
        ]);

        return view('admin.vendor_invoices.adjustment_show', [
            'invoice' => $vendorInvoice->loadMissing('vendor'),
            'adjustment' => $adjustment,
            'balance' => $this->balances->summary($vendorInvoice),
        ]);
    }

    public function postAdjustment(Request $request, VendorInvoice $vendorInvoice, VendorInvoiceAdjustment $adjustment): RedirectResponse
    {
        $this->assertAdjustmentBelongsToInvoice($vendorInvoice, $adjustment);
        $posted = $this->adjustments->post($adjustment, $request->user());

        return redirect()
            ->route('admin.vendor-invoices.adjustments.show', [$vendorInvoice, $posted])
            ->with('status', 'Vendor invoice adjustment posted. The adjusted payable has been updated.');
    }

    public function destroyAdjustment(VendorInvoice $vendorInvoice, VendorInvoiceAdjustment $adjustment): RedirectResponse
    {
        $this->assertAdjustmentBelongsToInvoice($vendorInvoice, $adjustment);
        $this->adjustments->deleteDraft($adjustment);

        return redirect()
            ->route('admin.vendor-invoices.show', $vendorInvoice)
            ->with('status', 'Adjustment draft deleted.');
    }

    public function reverseAdjustment(Request $request, VendorInvoice $vendorInvoice, VendorInvoiceAdjustment $adjustment): RedirectResponse
    {
        $this->assertAdjustmentBelongsToInvoice($vendorInvoice, $adjustment);
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:500'],
        ]);

        $reversal = $this->adjustments->reversePostedAdjustment(
            adjustment: $adjustment,
            actor: $request->user(),
            reason: $validated['reversal_reason'],
        );

        return redirect()
            ->route('admin.vendor-invoices.adjustments.show', [$vendorInvoice, $reversal])
            ->with('status', 'The financial adjustment was reversed through a new audit entry.');
    }

    public function createReturn(VendorInvoice $vendorInvoice): View
    {
        $vendorInvoice->loadMissing('vendor');

        return view('admin.vendor_invoices.return_form', [
            'invoice' => $vendorInvoice,
            'options' => $this->returns->options($vendorInvoice),
        ]);
    }

    public function storeReturn(Request $request, VendorInvoice $vendorInvoice): RedirectResponse
    {
        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'credit_note_received' => ['nullable', 'boolean'],
            'supplier_credit_note_number' => ['nullable', 'string', 'max:120'],
            'supplier_credit_note_date' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.piece_ids' => ['nullable', 'array'],
            'items.*.piece_ids.*' => ['integer'],
            'items.*.pack_ids' => ['nullable', 'array'],
            'items.*.pack_ids.*' => ['integer'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.piece_count' => ['nullable', 'integer', 'min:0'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $vendorReturn = $this->returns->createDraft(
            invoice: $vendorInvoice,
            actor: $request->user(),
            data: $validated,
        );

        return redirect()
            ->route('admin.vendor-invoices.returns.show', [$vendorInvoice, $vendorReturn])
            ->with('status', 'Purchase-return draft created. Review the stock and financial impact before posting.');
    }

    public function showReturn(VendorInvoice $vendorInvoice, VendorReturn $vendorReturn): View
    {
        $this->assertReturnBelongsToInvoice($vendorInvoice, $vendorReturn);
        $vendorReturn->loadMissing([
            'invoice.vendor',
            'items.invoiceItem.product',
            'items.invoiceItem.productVariant',
            'items.inventoryLot',
            'supplierCreditAdjustment',
            'creator',
            'postedBy',
        ]);

        return view('admin.vendor_invoices.return_show', [
            'invoice' => $vendorInvoice->loadMissing('vendor'),
            'vendorReturn' => $vendorReturn,
            'balance' => $this->balances->summary($vendorInvoice),
        ]);
    }

    public function postReturn(Request $request, VendorInvoice $vendorInvoice, VendorReturn $vendorReturn): RedirectResponse
    {
        $this->assertReturnBelongsToInvoice($vendorInvoice, $vendorReturn);
        $posted = $this->returns->post($vendorReturn, $request->user());

        return redirect()
            ->route('admin.vendor-invoices.returns.show', [$vendorInvoice, $posted])
            ->with('status', $posted->credit_note_received
                ? 'Purchase return posted and supplier credit recorded.'
                : 'Purchase return posted. The supplier credit note is still pending.');
    }

    public function destroyReturn(VendorInvoice $vendorInvoice, VendorReturn $vendorReturn): RedirectResponse
    {
        $this->assertReturnBelongsToInvoice($vendorInvoice, $vendorReturn);
        $this->returns->deleteDraft($vendorReturn);

        return redirect()
            ->route('admin.vendor-invoices.show', $vendorInvoice)
            ->with('status', 'Purchase-return draft deleted.');
    }

    public function reverseConfirm(VendorInvoice $vendorInvoice): View
    {
        $vendorInvoice->loadMissing('vendor');

        return view('admin.vendor_invoices.reverse_confirm', [
            'invoice' => $vendorInvoice,
            'assessment' => $this->returns->assessFullReversal($vendorInvoice),
            'balance' => $this->balances->summary($vendorInvoice),
        ]);
    }

    public function reverseInvoice(Request $request, VendorInvoice $vendorInvoice): RedirectResponse
    {
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:500'],
            'confirm_reversal' => ['accepted'],
        ]);

        $reversed = $this->returns->reverseInvoice(
            invoice: $vendorInvoice,
            actor: $request->user(),
            reason: $validated['reversal_reason'],
        );

        return redirect()
            ->route('admin.vendor-invoices.show', $reversed)
            ->with('status', 'Vendor invoice fully reversed. Original stock was removed and an audit-linked credit was posted.');
    }

    private function assertDirection(string $direction): void
    {
        abort_unless(in_array($direction, [
            VendorInvoiceAdjustment::DIRECTION_CREDIT,
            VendorInvoiceAdjustment::DIRECTION_DEBIT,
        ], true), 404);
    }

    private function assertAdjustmentBelongsToInvoice(VendorInvoice $invoice, VendorInvoiceAdjustment $adjustment): void
    {
        abort_unless((int) $adjustment->vendor_invoice_id === (int) $invoice->id, 404);
    }

    private function assertReturnBelongsToInvoice(VendorInvoice $invoice, VendorReturn $vendorReturn): void
    {
        abort_unless((int) $vendorReturn->vendor_invoice_id === (int) $invoice->id, 404);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}

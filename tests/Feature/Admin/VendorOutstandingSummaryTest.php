<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\VendorInvoiceController;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class VendorOutstandingSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_credit_on_one_invoice_does_not_automatically_offset_another_invoice_outstanding(): void
    {
        $vendor = Vendor::query()->create([
            'name' => 'Independent Invoice Balance Vendor',
            'is_active' => true,
        ]);

        $creditedInvoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'VCREDIT-001',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'paid',
        ]);

        VendorInvoiceAdjustment::query()->create([
            'vendor_invoice_id' => $creditedInvoice->id,
            'adjustment_number' => 'VADJ-CREDIT-001',
            'type' => VendorInvoiceAdjustment::TYPE_CREDIT_NOTE,
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'reason' => 'Supplier issued a credit after full payment.',
            'subtotal_delta' => -500,
            'tax_delta' => 0,
            'total_delta' => -500,
            'posted_at' => now(),
        ]);

        VendorPayment::query()->create([
            'vendor_id' => $vendor->id,
            'vendor_invoice_id' => $creditedInvoice->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
        ]);

        VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'VOPEN-001',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 200,
            'tax_amount' => 0,
            'total_amount' => 200,
            'status' => 'pending',
        ]);

        $view = app(VendorInvoiceController::class)->outstandingSummary(
            Request::create('/admin/vendor-invoices/outstanding', 'GET')
        );

        $row = $view->getData()['rows']->firstWhere('vendor_id', $vendor->id);

        $this->assertNotNull($row);
        $this->assertSame(700.0, round((float) $row->inv_total, 2));
        $this->assertSame(1000.0, round((float) $row->paid_total, 2));
        $this->assertSame(200.0, round((float) $row->outstanding_total, 2));
        $this->assertSame(500.0, round((float) $row->vendor_credit_due, 2));
        $this->assertSame(200.0, round((float) $view->getData()['totalOutstandingAllVendors'], 2));
        $this->assertSame(500.0, round((float) $view->getData()['totalVendorCreditDue'], 2));
    }
}

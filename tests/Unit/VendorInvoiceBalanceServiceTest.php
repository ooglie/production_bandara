<?php

namespace Tests\Unit;

use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorPayment;
use App\Services\VendorInvoiceBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorInvoiceBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_posted_adjustments_change_payable_without_changing_original_invoice_totals(): void
    {
        $vendor = Vendor::query()->create(['name' => 'Balance Test Vendor', 'is_active' => true]);
        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'BAL-001',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_amount' => 180,
            'total_amount' => 1180,
            'status' => 'pending',
        ]);

        VendorInvoiceAdjustment::query()->create([
            'vendor_invoice_id' => $invoice->id,
            'adjustment_number' => 'VADJ-TEST-001',
            'type' => VendorInvoiceAdjustment::TYPE_CREDIT_NOTE,
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'supplier_document_number' => 'CN-001',
            'supplier_document_date' => now()->toDateString(),
            'reason' => 'Price reduction',
            'subtotal_delta' => -100,
            'tax_delta' => -18,
            'total_delta' => -118,
            'posted_at' => now(),
        ]);

        VendorPayment::query()->create([
            'vendor_id' => $vendor->id,
            'vendor_invoice_id' => $invoice->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
        ]);

        $summary = app(VendorInvoiceBalanceService::class)->summary($invoice->fresh());

        $this->assertSame(1180.0, $summary['original_total']);
        $this->assertSame(-118.0, $summary['adjustment_total']);
        $this->assertSame(1062.0, $summary['adjusted_total']);
        $this->assertSame(500.0, $summary['paid']);
        $this->assertSame(562.0, $summary['outstanding']);
        $this->assertSame(0.0, $summary['vendor_credit_due']);

        $invoice->refresh();
        $this->assertSame(1180.0, (float) $invoice->total_amount);
    }

    public function test_vendor_credit_due_is_reported_when_payments_exceed_adjusted_payable(): void
    {
        $vendor = Vendor::query()->create(['name' => 'Credit Due Vendor', 'is_active' => true]);
        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'BAL-002',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'status' => 'paid',
        ]);

        VendorInvoiceAdjustment::query()->create([
            'vendor_invoice_id' => $invoice->id,
            'adjustment_number' => 'VADJ-TEST-002',
            'type' => VendorInvoiceAdjustment::TYPE_CREDIT_NOTE,
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'reason' => 'Post-payment credit',
            'subtotal_delta' => -100,
            'tax_delta' => 0,
            'total_delta' => -100,
            'posted_at' => now(),
        ]);

        VendorPayment::query()->create([
            'vendor_id' => $vendor->id,
            'vendor_invoice_id' => $invoice->id,
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
        ]);

        $summary = app(VendorInvoiceBalanceService::class)->summary($invoice->fresh());

        $this->assertSame(900.0, $summary['adjusted_total']);
        $this->assertSame(0.0, $summary['outstanding']);
        $this->assertSame(100.0, $summary['vendor_credit_due']);
    }
}

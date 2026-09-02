<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardVendorInvoiceMonthTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_current_month_vendor_invoice_gross_and_adjusted_net(): void
    {
        $vendor = Vendor::query()->create([
            'name' => 'Dashboard Vendor ' . uniqid(),
            'code' => 'DASH-' . strtoupper(substr(uniqid(), -6)),
            'is_active' => true,
        ]);

        $current = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'DASH-CURRENT-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_amount' => 180,
            'total_amount' => 1180,
            'status' => 'pending',
        ]);

        VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'DASH-OLD-' . uniqid(),
            'invoice_date' => now()->subMonthNoOverflow()->toDateString(),
            'subtotal' => 5000,
            'tax_amount' => 900,
            'total_amount' => 5900,
            'status' => 'pending',
        ]);

        VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'DASH-CANCELLED-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 2000,
            'tax_amount' => 360,
            'total_amount' => 2360,
            'status' => 'cancelled',
        ]);

        VendorInvoiceAdjustment::query()->create([
            'vendor_invoice_id' => $current->id,
            'adjustment_number' => 'VIA-DASH-' . uniqid(),
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'type' => VendorInvoiceAdjustment::TYPE_CREDIT_NOTE,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'reason' => 'Current-month test credit.',
            'subtotal_delta' => -100,
            'tax_delta' => -18,
            'total_delta' => -118,
            'posted_at' => now(),
        ]);

        $view = app(DashboardController::class)->index();
        $data = $view->getData();

        $this->assertSame(1, $data['vendorInvoiceCountThisMonth']);
        $this->assertSame(1180.0, (float) $data['vendorInvoiceGrossThisMonth']);
        $this->assertSame(-118.0, (float) $data['vendorInvoiceAdjustmentDeltaThisMonth']);
        $this->assertSame(1062.0, (float) $data['vendorInvoiceNetThisMonth']);
    }
}

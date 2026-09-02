<?php

namespace Tests\Feature\Admin;

use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Services\VendorInvoiceMonthSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorInvoiceMonthSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_summary_uses_invoice_date_excludes_cancelled_and_applies_posted_adjustments(): void
    {
        $vendor = Vendor::query()->create([
            'name' => 'Dashboard Vendor ' . uniqid(),
            'code' => 'DASH-' . strtoupper(substr(uniqid(), -6)),
            'is_active' => true,
        ]);

        $first = $this->invoice($vendor->id, '2026-08-02', 1000, 'pending');
        $second = $this->invoice($vendor->id, '2026-08-18', 500, 'paid');
        $this->invoice($vendor->id, '2026-08-10', 900, 'cancelled');
        $this->invoice($vendor->id, '2026-07-31', 700, 'pending');

        VendorInvoiceAdjustment::query()->create([
            'vendor_invoice_id' => $first->id,
            'adjustment_number' => 'VIA-TEST-' . strtoupper(substr(uniqid(), -8)),
            'type' => VendorInvoiceAdjustment::TYPE_CREDIT_NOTE,
            'direction' => VendorInvoiceAdjustment::DIRECTION_CREDIT,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'reason' => 'Price credit',
            'subtotal_delta' => -100,
            'tax_delta' => -5,
            'total_delta' => -105,
            'affects_stock' => false,
            'posted_at' => '2026-08-20 10:00:00',
        ]);

        VendorInvoiceAdjustment::query()->create([
            'vendor_invoice_id' => $second->id,
            'adjustment_number' => 'VIA-DRAFT-' . strtoupper(substr(uniqid(), -8)),
            'type' => VendorInvoiceAdjustment::TYPE_DEBIT_NOTE,
            'direction' => VendorInvoiceAdjustment::DIRECTION_DEBIT,
            'status' => VendorInvoiceAdjustment::STATUS_DRAFT,
            'reason' => 'Draft must not affect dashboard',
            'subtotal_delta' => 50,
            'tax_delta' => 2.50,
            'total_delta' => 52.50,
            'affects_stock' => false,
        ]);

        $summary = app(VendorInvoiceMonthSummaryService::class)
            ->forMonth(Carbon::parse('2026-08-22 12:00:00'));

        $this->assertSame(2, $summary['count']);
        $this->assertSame(1500.0, $summary['original_total']);
        $this->assertSame(-105.0, $summary['posted_adjustment_total']);
        $this->assertSame(1395.0, $summary['adjusted_total']);
        $this->assertSame('2026-08-01', $summary['from']);
        $this->assertSame('2026-08-22', $summary['to']);
    }

    private function invoice(int $vendorId, string $date, float $total, string $status): VendorInvoice
    {
        return VendorInvoice::query()->create([
            'vendor_id' => $vendorId,
            'invoice_number' => 'SUP-DASH-' . strtoupper(substr(uniqid(), -8)),
            'invoice_date' => $date,
            'subtotal' => round($total / 1.05, 2),
            'tax_amount' => round($total - ($total / 1.05), 2),
            'total_amount' => $total,
            'status' => $status,
        ]);
    }
}

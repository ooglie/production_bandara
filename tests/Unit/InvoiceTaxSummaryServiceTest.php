<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceTaxSummaryService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class InvoiceTaxSummaryServiceTest extends TestCase
{
    public function test_it_builds_an_intra_state_summary_with_cgst_and_sgst_only(): void
    {
        $invoice = (new Invoice())->forceFill([
            'gst_type' => 'intra_state',
            'cgst_amount' => 172.88,
            'sgst_amount' => 172.88,
            'igst_amount' => 0,
            'delivery_fee' => 100,
            'delivery_tax_rate' => 18,
            'delivery_tax_amount' => 18,
            'delivery_sac_code' => '996812',
            'handling_fee' => 50,
            'handling_tax_rate' => 18,
            'handling_tax_amount' => 9,
            'handling_sac_code' => '996719',
        ]);

        $invoice->setRelation('order', null);
        $invoice->setRelation('items', new Collection([
            (new InvoiceItem())->forceFill([
                'description' => 'NZ Lamb curry cut boneless',
                'hsn_sac_code' => '02044300',
                'gst_rate' => 5,
                'subtotal' => 6375,
                'tax_amount' => 318.76,
                'cgst_amount' => 159.38,
                'sgst_amount' => 159.38,
                'igst_amount' => 0,
                'total' => 6693.76,
            ]),
        ]));

        $summary = (new InvoiceTaxSummaryService())->build($invoice);
        $rows = collect($summary['rows'])->keyBy('hsn_sac_code');

        $this->assertSame('intra_state', $summary['gst_type']);
        $this->assertCount(3, $summary['rows']);
        $this->assertSame(159.38, $rows['02044300']['cgst_amount']);
        $this->assertSame(159.38, $rows['02044300']['sgst_amount']);
        $this->assertSame(0.0, $rows['02044300']['igst_amount']);
        $this->assertSame(6525.0, $summary['totals']['taxable_value']);
        $this->assertSame(172.88, $summary['totals']['cgst_amount']);
        $this->assertSame(172.88, $summary['totals']['sgst_amount']);
        $this->assertSame(0.0, $summary['totals']['igst_amount']);
        $this->assertSame(345.76, $summary['totals']['total_tax']);
    }

    public function test_it_derives_missing_historical_line_tax_from_the_saved_rate(): void
    {
        $invoice = (new Invoice())->forceFill([
            'gst_type' => 'intra_state',
            'tax_total' => 50,
            'cgst_amount' => 25,
            'sgst_amount' => 25,
            'igst_amount' => 0,
            'delivery_fee' => 0,
            'handling_fee' => 0,
        ]);

        $invoice->setRelation('order', null);
        $invoice->setRelation('items', new Collection([
            (new InvoiceItem())->forceFill([
                'description' => 'Historical admin line',
                'hsn_sac_code' => '02044300',
                'gst_rate' => 5,
                'subtotal' => 1000,
                'tax_amount' => 0,
                'cgst_amount' => null,
                'sgst_amount' => null,
                'igst_amount' => null,
                'total' => 1000,
            ]),
        ]));

        $summary = (new InvoiceTaxSummaryService())->build($invoice);
        $row = $summary['rows'][0];

        $this->assertSame(25.0, $row['cgst_amount']);
        $this->assertSame(25.0, $row['sgst_amount']);
        $this->assertSame(50.0, $row['total_tax']);
        $this->assertSame(50.0, $summary['totals']['total_tax']);
    }

    public function test_it_infers_inter_state_mode_and_uses_igst_only(): void
    {
        $invoice = (new Invoice())->forceFill([
            'gst_type' => null,
            'cgst_amount' => 0,
            'sgst_amount' => 0,
            'igst_amount' => 50,
            'delivery_fee' => 0,
            'handling_fee' => 0,
        ]);

        $invoice->setRelation('order', null);
        $invoice->setRelation('items', new Collection([
            (new InvoiceItem())->forceFill([
                'description' => 'Inter-state product',
                'hsn_sac_code' => '02044300',
                'gst_rate' => 5,
                'subtotal' => 1000,
                'tax_amount' => 50,
                'cgst_amount' => 0,
                'sgst_amount' => 0,
                'igst_amount' => 50,
                'total' => 1050,
            ]),
        ]));

        $summary = (new InvoiceTaxSummaryService())->build($invoice);
        $row = $summary['rows'][0];

        $this->assertSame('inter_state', $summary['gst_type']);
        $this->assertSame(0.0, $row['cgst_amount']);
        $this->assertSame(0.0, $row['sgst_amount']);
        $this->assertSame(50.0, $row['igst_amount']);
        $this->assertSame(0.0, $summary['totals']['cgst_amount']);
        $this->assertSame(0.0, $summary['totals']['sgst_amount']);
        $this->assertSame(50.0, $summary['totals']['igst_amount']);
        $this->assertSame(50.0, $summary['totals']['total_tax']);
    }
}

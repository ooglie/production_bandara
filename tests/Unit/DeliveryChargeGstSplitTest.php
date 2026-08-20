<?php

namespace Tests\Unit;

use App\Services\DeliveryChargeService;
use Tests\TestCase;

class DeliveryChargeGstSplitTest extends TestCase
{
    public function test_delivery_and_handling_tax_follow_the_resolved_order_gst_type(): void
    {
        $service = app(DeliveryChargeService::class);
        $quote = [
            'tax_total' => 18.00,
            'delivery_tax_amount' => 12.00,
            'handling_tax_amount' => 6.00,
        ];

        $intraState = $service->splitChargeTaxForGstType($quote, 'intra_state');
        $interState = $service->splitChargeTaxForGstType($quote, 'inter_state');

        $this->assertSame('intra_state', $intraState['gst_type']);
        $this->assertSame(9.0, $intraState['cgst_amount']);
        $this->assertSame(9.0, $intraState['sgst_amount']);
        $this->assertNull($intraState['igst_amount']);

        $this->assertSame('inter_state', $interState['gst_type']);
        $this->assertNull($interState['cgst_amount']);
        $this->assertNull($interState['sgst_amount']);
        $this->assertSame(18.0, $interState['igst_amount']);
    }
}

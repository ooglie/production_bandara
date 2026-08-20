<?php

namespace Tests\Unit;

use App\Services\GstPlaceOfSupplyService;
use InvalidArgumentException;
use Tests\TestCase;

class GstPlaceOfSupplyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'gst.supplier_gstin' => '27ABEFB3240N1ZE',
            'store.invoice.seller.gstin_no' => '27ABEFB3240N1ZE',
        ]);
    }

    public function test_no_gstin_uses_shipping_state(): void
    {
        $service = app(GstPlaceOfSupplyService::class);

        $maharashtra = $service->resolve(null, null, null, 'MH', 'Maharashtra');
        $karnataka = $service->resolve(null, null, null, 'KA', 'Karnataka');

        $this->assertSame('intra_state', $maharashtra['gst_type']);
        $this->assertSame('27', $maharashtra['place_of_supply_gst_state_code']);
        $this->assertSame('shipping_address', $maharashtra['gst_determination_basis']);

        $this->assertSame('inter_state', $karnataka['gst_type']);
        $this->assertSame('29', $karnataka['place_of_supply_gst_state_code']);
        $this->assertSame('IGST', $karnataka['tax_label']);
    }

    public function test_maharashtra_bill_to_gstin_remains_intra_state_when_shipping_outside_maharashtra(): void
    {
        $context = app(GstPlaceOfSupplyService::class)->resolve(
            '27ABCDE1234F1Z5',
            'MH',
            'Maharashtra',
            'KA',
            'Karnataka',
        );

        $this->assertSame('intra_state', $context['gst_type']);
        $this->assertSame('CGST + SGST', $context['tax_label']);
        $this->assertSame('27', $context['place_of_supply_gst_state_code']);
        $this->assertSame('29', $context['ship_to_gst_state_code']);
        $this->assertTrue($context['is_bill_to_ship_to']);
        $this->assertSame('bill_to_gstin', $context['gst_determination_basis']);
    }

    public function test_outside_state_bill_to_gstin_is_inter_state_even_when_shipping_to_maharashtra(): void
    {
        $context = app(GstPlaceOfSupplyService::class)->resolve(
            '29ABCDE1234F1Z5',
            'KA',
            'Karnataka',
            'MH',
            'Maharashtra',
        );

        $this->assertSame('inter_state', $context['gst_type']);
        $this->assertSame('29', $context['place_of_supply_gst_state_code']);
        $this->assertTrue($context['is_bill_to_ship_to']);
    }

    public function test_gstin_state_must_match_billing_address_state(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('GSTIN belongs to Maharashtra');

        app(GstPlaceOfSupplyService::class)->resolve(
            '27ABCDE1234F1Z5',
            'KA',
            'Karnataka',
            'KA',
            'Karnataka',
        );
    }

    public function test_gstin_is_normalized_and_format_checked(): void
    {
        $service = app(GstPlaceOfSupplyService::class);

        $this->assertSame('27ABCDE1234F1Z5', $service->normalizeGstin(' 27abcde1234f1z5 '));
        $this->assertTrue($service->isValidFormat('27ABCDE1234F1Z5'));
        $this->assertFalse($service->isValidFormat('27ABC'));
    }
    public function test_legacy_daman_and_diu_prefixes_match_the_combined_address_option(): void
    {
        $service = app(GstPlaceOfSupplyService::class);

        $this->assertSame(['26', '25'], $service->gstStateCodesForAddress(
            'DH',
            'Dadra and Nagar Haveli and Daman and Diu',
        ));

        $context = $service->resolve(
            '25ABCDE1234F1Z5',
            'DH',
            'Dadra and Nagar Haveli and Daman and Diu',
            'MH',
            'Maharashtra',
        );

        $this->assertSame('25', $context['place_of_supply_gst_state_code']);
        $this->assertSame('inter_state', $context['gst_type']);
    }

}

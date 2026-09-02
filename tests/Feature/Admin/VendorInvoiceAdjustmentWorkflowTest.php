<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorInvoiceItem;
use App\Services\VendorInvoiceAdjustmentService;
use App\Services\VendorInvoiceBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorInvoiceAdjustmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_note_is_draft_first_then_posts_and_can_be_reversed_by_an_opposite_audit_entry(): void
    {
        [$invoice, $item] = $this->makeInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $service = app(VendorInvoiceAdjustmentService::class);
        $balances = app(VendorInvoiceBalanceService::class);

        $draft = $service->createFinancialDraft(
            invoice: $invoice,
            actor: $actor,
            direction: VendorInvoiceAdjustment::DIRECTION_CREDIT,
            header: [
                'supplier_document_number' => 'CN-PRICE-001',
                'supplier_document_date' => now()->toDateString(),
                'reason' => 'Supplier reduced the agreed price.',
            ],
            lines: [[
                'vendor_invoice_item_id' => $item->id,
                'product_id' => $item->product_id,
                'original_unit_cost' => $item->unit_cost,
                'revised_unit_cost' => 90,
                'subtotal_amount' => 100,
                'tax_amount' => 18,
            ]],
        );

        $this->assertTrue($draft->isDraft());
        $this->assertSame(1180.0, $balances->summary($invoice->fresh())['adjusted_total']);
        $this->assertSame(1180.0, (float) $invoice->fresh()->total_amount);

        $posted = $service->post($draft, $actor);
        $this->assertTrue($posted->isPosted());
        $this->assertSame(-118.0, (float) $posted->total_delta);
        $this->assertSame(1062.0, $balances->summary($invoice->fresh())['adjusted_total']);

        $reversal = $service->reversePostedAdjustment(
            adjustment: $posted,
            actor: $actor,
            reason: 'The supplier withdrew the credit note.',
        );

        $this->assertSame(VendorInvoiceAdjustment::TYPE_ADJUSTMENT_REVERSAL, $reversal->type);
        $this->assertSame(118.0, (float) $reversal->total_delta);
        $this->assertSame($posted->id, $reversal->reverses_adjustment_id);
        $this->assertSame(1180.0, $balances->summary($invoice->fresh())['adjusted_total']);
        $this->assertDatabaseHas('vendor_invoice_adjustments', ['id' => $posted->id]);
    }

    public function test_metadata_correction_records_before_and_after_values_without_financial_effect(): void
    {
        [$invoice] = $this->makeInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $service = app(VendorInvoiceAdjustmentService::class);

        $audit = $service->recordMetadataCorrection(
            invoice: $invoice,
            actor: $actor,
            before: ['invoice_number' => 'SUP-001'],
            after: ['invoice_number' => 'SUP-001-A'],
            reason: 'Corrected the supplier reference.',
        );

        $this->assertSame(VendorInvoiceAdjustment::TYPE_METADATA_CORRECTION, $audit->type);
        $this->assertSame(0.0, (float) $audit->total_delta);
        $this->assertSame('SUP-001', data_get($audit->meta, 'before.invoice_number'));
        $this->assertSame('SUP-001-A', data_get($audit->meta, 'after.invoice_number'));
    }


    public function test_supplier_document_number_cannot_be_reused_for_the_same_vendor(): void
    {
        [$invoice, $item] = $this->makeInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $service = app(VendorInvoiceAdjustmentService::class);

        $service->createFinancialDraft(
            invoice: $invoice,
            actor: $actor,
            direction: VendorInvoiceAdjustment::DIRECTION_CREDIT,
            header: [
                'supplier_document_number' => 'CN-DUPLICATE-001',
                'supplier_document_date' => now()->toDateString(),
                'reason' => 'First supplier credit.',
            ],
            lines: [[
                'vendor_invoice_item_id' => $item->id,
                'product_id' => $item->product_id,
                'subtotal_amount' => 10,
                'tax_amount' => 1.80,
            ]],
        );

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $service->createFinancialDraft(
            invoice: $invoice,
            actor: $actor,
            direction: VendorInvoiceAdjustment::DIRECTION_DEBIT,
            header: [
                'supplier_document_number' => 'CN-DUPLICATE-001',
                'supplier_document_date' => now()->toDateString(),
                'reason' => 'Duplicate supplier document.',
            ],
            lines: [[
                'vendor_invoice_item_id' => $item->id,
                'product_id' => $item->product_id,
                'subtotal_amount' => 10,
                'tax_amount' => 1.80,
            ]],
        );
    }

    private function makeInvoice(): array
    {
        $vendor = Vendor::query()->create(['name' => 'Adjustment Test Vendor', 'is_active' => true]);
        $product = Product::query()->create([
            'name' => 'Adjustment Test Product',
            'slug' => 'adjustment-test-product',
            'sku' => 'ADJ-TEST',
            'type' => 'simple',
            'manage_stock' => true,
            'stock_quantity' => 10,
            'base_price' => 100,
            'is_active' => true,
        ]);
        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SUP-001',
            'invoice_date' => now()->toDateString(),
            'subtotal' => 1000,
            'tax_amount' => 180,
            'total_amount' => 1180,
            'status' => 'pending',
        ]);
        $item = VendorInvoiceItem::query()->create([
            'vendor_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'receipt_type' => 'quantity',
            'quantity' => 10,
            'unit_cost' => 100,
            'tax_amount' => 180,
            'total' => 1180,
        ]);

        return [$invoice, $item, $product, $vendor];
    }
}

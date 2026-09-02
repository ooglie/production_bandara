<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryLot;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceAdjustment;
use App\Models\VendorInvoiceItem;
use App\Models\VendorReturn;
use App\Services\VendorInvoiceAdjustmentService;
use App\Services\VendorInvoiceBalanceService;
use App\Services\VendorReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantity_return_is_draft_first_then_posts_stock_and_supplier_credit_atomically(): void
    {
        [$invoice, $item, $product, $lot] = $this->makeQuantityInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $returns = app(VendorReturnService::class);
        $balances = app(VendorInvoiceBalanceService::class);

        $draft = $returns->createDraft($invoice, $actor, [
            'return_date' => now()->toDateString(),
            'reason' => 'Two damaged packs returned to the supplier.',
            'credit_note_received' => true,
            'supplier_credit_note_number' => 'CN-RETURN-001',
            'supplier_credit_note_date' => now()->toDateString(),
            'items' => [
                $item->id => ['quantity' => 2],
            ],
        ]);

        $this->assertTrue($draft->isDraft());
        $this->assertSame(236.0, (float) $draft->expected_total);
        $this->assertSame(10.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(10.0, (float) $lot->fresh()->available_quantity);
        $this->assertSame(1180.0, $balances->summary($invoice->fresh())['adjusted_total']);

        $posted = $returns->post($draft, $actor);

        $this->assertSame(VendorReturn::STATUS_CREDITED, $posted->status);
        $this->assertNotNull($posted->supplier_credit_adjustment_id);
        $this->assertSame(8.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(8.0, (float) $lot->fresh()->available_quantity);
        $this->assertSame(944.0, $balances->summary($invoice->fresh())['adjusted_total']);

        $credit = VendorInvoiceAdjustment::query()->findOrFail($posted->supplier_credit_adjustment_id);
        $this->assertSame(VendorInvoiceAdjustment::TYPE_PURCHASE_RETURN_CREDIT, $credit->type);
        $this->assertSame(-236.0, (float) $credit->total_delta);
        $this->assertTrue((bool) $credit->affects_stock);

        $movement = StockMovement::query()
            ->where('reference_type', 'vendor_return')
            ->where('reference_id', $posted->id)
            ->firstOrFail();

        $this->assertSame('return', $movement->movement_type);
        $this->assertSame(-2.0, (float) $movement->quantity);
    }

    public function test_exact_weighted_piece_return_does_not_touch_other_pieces(): void
    {
        [$invoice, $item, $product, $lot, $pieces] = $this->makeWeightedPieceInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $returns = app(VendorReturnService::class);

        $draft = $returns->createDraft($invoice, $actor, [
            'return_date' => now()->toDateString(),
            'reason' => 'One cheese block was rejected on quality.',
            'credit_note_received' => true,
            'supplier_credit_note_number' => 'CN-PIECE-001',
            'supplier_credit_note_date' => now()->toDateString(),
            'items' => [
                $item->id => ['piece_ids' => [$pieces[1]->id]],
            ],
        ]);

        $this->assertSame(115.5, (float) $draft->expected_total);

        $returns->post($draft, $actor);

        $this->assertSame('available', $pieces[0]->fresh()->status);
        $this->assertSame('returned', $pieces[1]->fresh()->status);
        $this->assertSame('available', $pieces[2]->fresh()->status);
        $this->assertSame(2.2, (float) $product->fresh()->stock_quantity);
        $this->assertSame(2.2, (float) $lot->fresh()->available_weight_kg);
        $this->assertSame(2, (int) $lot->fresh()->available_piece_count);
    }

    public function test_untouched_unpaid_invoice_can_be_fully_reversed_without_deleting_original_invoice_or_items(): void
    {
        [$invoice, $item, $product, $lot] = $this->makeQuantityInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $returns = app(VendorReturnService::class);

        $assessment = $returns->assessFullReversal($invoice);
        $this->assertTrue($assessment['can_reverse']);
        $this->assertSame([], $assessment['blockers']);

        $reversed = $returns->reverseInvoice(
            invoice: $invoice,
            actor: $actor,
            reason: 'The supplier invoice was entered against the wrong document.',
        );

        $this->assertSame('cancelled', $reversed->status);
        $this->assertSame(1180.0, (float) $reversed->total_amount);
        $this->assertSame(0.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(0.0, (float) $lot->fresh()->available_quantity);
        $this->assertSame('returned', $lot->fresh()->lot_status);
        $this->assertDatabaseHas('vendor_invoices', ['id' => $invoice->id, 'total_amount' => 1180]);
        $this->assertDatabaseHas('vendor_invoice_items', ['id' => $item->id]);
        $this->assertDatabaseHas('vendor_invoice_adjustments', [
            'vendor_invoice_id' => $invoice->id,
            'type' => VendorInvoiceAdjustment::TYPE_FULL_REVERSAL,
            'status' => VendorInvoiceAdjustment::STATUS_POSTED,
            'total_delta' => -1180,
        ]);
        $this->assertDatabaseHas('vendor_returns', [
            'vendor_invoice_id' => $invoice->id,
            'status' => VendorReturn::STATUS_CREDITED,
        ]);
    }


    public function test_full_reversal_is_blocked_while_an_adjustment_draft_exists(): void
    {
        [$invoice, $item] = $this->makeQuantityInvoice();
        $actor = User::factory()->create(['customer_type' => 'staff']);

        app(VendorInvoiceAdjustmentService::class)->createFinancialDraft(
            invoice: $invoice,
            actor: $actor,
            direction: VendorInvoiceAdjustment::DIRECTION_CREDIT,
            header: [
                'supplier_document_number' => 'CN-DRAFT-REVERSAL-BLOCK',
                'supplier_document_date' => now()->toDateString(),
                'reason' => 'Draft credit under review.',
            ],
            lines: [[
                'vendor_invoice_item_id' => $item->id,
                'product_id' => $item->product_id,
                'subtotal_amount' => 10,
                'tax_amount' => 1.80,
            ]],
        );

        $assessment = app(VendorReturnService::class)->assessFullReversal($invoice->fresh());

        $this->assertFalse($assessment['can_reverse']);
        $this->assertTrue(collect($assessment['blockers'])->contains(
            fn (string $message) => str_contains(strtolower($message), 'adjustment draft')
        ));
    }

    private function makeQuantityInvoice(): array
    {
        $vendor = Vendor::query()->create([
            'name' => 'Return Test Vendor ' . uniqid(),
            'code' => 'RTV-' . strtoupper(substr(uniqid(), -6)),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Return Test Product ' . uniqid(),
            'slug' => 'return-test-product-' . uniqid(),
            'sku' => 'RTP-' . strtoupper(substr(uniqid(), -6)),
            'type' => 'simple',
            'manage_stock' => true,
            'stock_quantity' => 10,
            'base_price' => 100,
            'is_active' => true,
            'sell_unit' => 'piece',
            'pack_type' => 'quantity',
        ]);

        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SUP-RET-' . strtoupper(substr(uniqid(), -8)),
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

        $lot = InventoryLot::query()->create([
            'lot_code' => 'LOT-RET-' . strtoupper(substr(uniqid(), -8)),
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'vendor_invoice_id' => $invoice->id,
            'vendor_invoice_item_id' => $item->id,
            'lot_stage' => 'pack',
            'inward_mode' => 'qty',
            'is_saleable' => true,
            'can_repack' => false,
            'lot_status' => 'available',
            'received_quantity' => 10,
            'available_quantity' => 10,
            'unit_weight_kg' => 1,
            'total_weight_kg' => 10,
            'available_weight_kg' => 10,
            'unit_cost' => 100,
            'total_cost' => 1000,
        ]);

        $lot->root_inventory_lot_id = $lot->id;
        $lot->save();

        return [$invoice->fresh(), $item, $product, $lot];
    }

    private function makeWeightedPieceInvoice(): array
    {
        $vendor = Vendor::query()->create([
            'name' => 'Weighted Return Vendor ' . uniqid(),
            'code' => 'WRV-' . strtoupper(substr(uniqid(), -6)),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Weighted Return Product ' . uniqid(),
            'slug' => 'weighted-return-product-' . uniqid(),
            'sku' => 'WRP-' . strtoupper(substr(uniqid(), -6)),
            'type' => 'simple',
            'manage_stock' => true,
            'stock_quantity' => 3.3,
            'base_price' => 100,
            'is_active' => true,
            'sell_unit' => 'kg',
            'pack_type' => 'variable_weight',
        ]);

        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SUP-WRET-' . strtoupper(substr(uniqid(), -8)),
            'invoice_date' => now()->toDateString(),
            'subtotal' => 330,
            'tax_amount' => 16.50,
            'total_amount' => 346.50,
            'status' => 'pending',
        ]);

        $item = VendorInvoiceItem::query()->create([
            'vendor_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'receipt_type' => 'pieces_weight',
            'quantity' => 3,
            'total_weight_kg' => 3.3,
            'unit_cost' => 100,
            'tax_amount' => 16.50,
            'total' => 346.50,
        ]);

        $lot = InventoryLot::query()->create([
            'lot_code' => 'LOT-WRET-' . strtoupper(substr(uniqid(), -8)),
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'vendor_invoice_id' => $invoice->id,
            'vendor_invoice_item_id' => $item->id,
            'lot_stage' => 'raw',
            'inward_mode' => 'pieces',
            'is_saleable' => true,
            'can_repack' => true,
            'lot_status' => 'available',
            'received_quantity' => 3.3,
            'available_quantity' => 3.3,
            'total_weight_kg' => 3.3,
            'available_weight_kg' => 3.3,
            'piece_count' => 3,
            'available_piece_count' => 3,
            'unit_cost' => 100,
            'total_cost' => 330,
        ]);

        $lot->root_inventory_lot_id = $lot->id;
        $lot->save();

        $pieces = collect([1.0, 1.1, 1.2])->map(function (float $weight, int $index) use ($lot) {
            return InventoryPiece::query()->create([
                'inventory_lot_id' => $lot->id,
                'piece_no' => $index + 1,
                'label' => 'Piece ' . ($index + 1),
                'weight_kg' => $weight,
                'available_weight_kg' => $weight,
                'status' => 'available',
            ]);
        })->values();

        return [$invoice->fresh(), $item, $product, $lot, $pieces];
    }
}

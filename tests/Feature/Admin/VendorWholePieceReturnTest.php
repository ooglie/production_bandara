<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use App\Models\VendorReturnItem;
use App\Services\VendorReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorWholePieceReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_untracked_weighted_piece_can_only_be_returned_as_a_whole_piece(): void
    {
        [$invoice, $item, $product, $lot] = $this->makeWholePieceInvoice(4.750, 4.750);
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $service = app(VendorReturnService::class);

        $option = $service->options($invoice)[$item->id];

        $this->assertSame('whole_piece', $option['mode']);
        $this->assertTrue($option['is_whole_piece']);
        $this->assertSame(4.75, (float) $option['max_weight_kg']);
        $this->assertSame([], $option['blockers']);

        $draft = $service->createDraft($invoice, $actor, [
            'return_date' => now()->toDateString(),
            'reason' => 'The full salmon was returned to the supplier.',
            'items' => [
                $item->id => [
                    'whole_piece' => true,
                    // A submitted partial weight must never override the exact piece weight.
                    'weight_kg' => 0.500,
                ],
            ],
        ]);

        $returnItem = VendorReturnItem::query()
            ->where('vendor_return_id', $draft->id)
            ->firstOrFail();

        $this->assertSame('whole_piece', $returnItem->return_mode);
        $this->assertSame(1.0, (float) $returnItem->quantity);
        $this->assertSame(4.75, (float) $returnItem->weight_kg);

        $posted = $service->post($draft, $actor);

        $this->assertSame(0.0, (float) $product->fresh()->stock_quantity);
        $this->assertSame(0.0, (float) $lot->fresh()->available_quantity);
        $this->assertSame(0.0, (float) $lot->fresh()->available_weight_kg);
        $this->assertSame(0, (int) $lot->fresh()->available_piece_count);
        $this->assertSame('returned', $lot->fresh()->lot_status);

        $movement = StockMovement::query()
            ->where('reference_type', 'vendor_return')
            ->where('reference_id', $posted->id)
            ->firstOrFail();

        $this->assertSame(-4.75, (float) $movement->quantity);
    }

    public function test_weight_only_submission_is_rejected_for_an_intact_whole_piece(): void
    {
        [$invoice, $item] = $this->makeWholePieceInvoice(4.750, 4.750);
        $actor = User::factory()->create(['customer_type' => 'staff']);

        $this->expectException(ValidationException::class);

        app(VendorReturnService::class)->createDraft($invoice, $actor, [
            'return_date' => now()->toDateString(),
            'reason' => 'Attempted partial return must be blocked.',
            'items' => [
                $item->id => ['weight_kg' => 1.250],
            ],
        ]);
    }

    public function test_partially_consumed_single_piece_is_blocked_instead_of_offering_weight_return(): void
    {
        [$invoice, $item] = $this->makeWholePieceInvoice(4.750, 3.250);
        $actor = User::factory()->create(['customer_type' => 'staff']);
        $service = app(VendorReturnService::class);

        $option = $service->options($invoice)[$item->id];

        $this->assertSame('whole_piece', $option['mode']);
        $this->assertNotEmpty($option['blockers']);
        $this->assertTrue(collect($option['blockers'])->contains(
            fn (string $message) => str_contains(strtolower($message), 'cannot be returned by partial weight')
        ));

        $this->expectException(ValidationException::class);

        $service->createDraft($invoice, $actor, [
            'return_date' => now()->toDateString(),
            'reason' => 'Attempted partial return.',
            'items' => [
                $item->id => ['whole_piece' => true, 'weight_kg' => 2.000],
            ],
        ]);
    }

    private function makeWholePieceInvoice(float $originalWeight, float $availableWeight): array
    {
        $vendor = Vendor::query()->create([
            'name' => 'Whole Piece Vendor ' . uniqid(),
            'code' => 'WPV-' . strtoupper(substr(uniqid(), -6)),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Full Salmon ' . uniqid(),
            'slug' => 'full-salmon-' . uniqid(),
            'sku' => 'FS-' . strtoupper(substr(uniqid(), -7)),
            'type' => 'simple',
            'manage_stock' => true,
            'stock_quantity' => $availableWeight,
            'base_price' => 1200,
            'is_active' => true,
            'sell_unit' => 'kg',
            'pack_type' => 'variable_weight',
        ]);

        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => 'SUP-WHOLE-' . strtoupper(substr(uniqid(), -8)),
            'invoice_date' => now()->toDateString(),
            'subtotal' => round($originalWeight * 1000, 2),
            'tax_amount' => 0,
            'total_amount' => round($originalWeight * 1000, 2),
            'status' => 'pending',
        ]);

        $item = VendorInvoiceItem::query()->create([
            'vendor_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'receipt_type' => 'pieces_weight',
            'quantity' => 1,
            'total_weight_kg' => $originalWeight,
            'unit_cost' => 1000,
            'tax_amount' => 0,
            'total' => round($originalWeight * 1000, 2),
        ]);

        $lot = InventoryLot::query()->create([
            'lot_code' => 'LOT-WHOLE-' . strtoupper(substr(uniqid(), -7)),
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'vendor_invoice_id' => $invoice->id,
            'vendor_invoice_item_id' => $item->id,
            'lot_stage' => 'raw',
            'inward_mode' => 'pieces',
            'is_saleable' => true,
            'can_repack' => true,
            'lot_status' => 'available',
            'received_quantity' => $originalWeight,
            'available_quantity' => $availableWeight,
            'total_weight_kg' => $originalWeight,
            'available_weight_kg' => $availableWeight,
            'piece_count' => 1,
            'available_piece_count' => $availableWeight > 0 ? 1 : 0,
            'unit_cost' => 1000,
            'total_cost' => round($originalWeight * 1000, 2),
        ]);

        $lot->root_inventory_lot_id = $lot->id;
        $lot->save();

        return [$invoice->fresh(), $item, $product, $lot];
    }
}

<?php

namespace Tests\Feature\Services;

use App\Models\InventoryLot;
use App\Models\Product;
use App\Models\ProductionRun;
use App\Models\ProductionRunInput;
use App\Models\ProductionRunOutput;
use App\Models\User;
use App\Services\ProductionRunReversalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionRunReversalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reverses_an_untouched_completed_run_without_deleting_audit_records(): void
    {
        [$run, $sourceProduct, $outputProduct, $sourceLot, $outputLot] = $this->makeCompletedRun();
        $actor = User::factory()->create();

        $service = app(ProductionRunReversalService::class);

        $this->assertTrue($service->assess($run)['can_reverse']);

        $service->reverse($run, $actor, 'The wrong slab output was recorded.');

        $run->refresh();
        $sourceProduct->refresh();
        $outputProduct->refresh();
        $sourceLot->refresh();
        $outputLot->refresh();

        $this->assertSame('reversed', $run->status);
        $this->assertSame($actor->id, $run->reversed_by_id);
        $this->assertSame('The wrong slab output was recorded.', $run->reversal_reason);
        $this->assertNotNull($run->reversed_at);
        $this->assertIsArray($run->reversal_snapshot_json);

        $this->assertSame(5.0, (float) $sourceProduct->stock_quantity);
        $this->assertSame(0.0, (float) $outputProduct->stock_quantity);

        $this->assertSame(5.0, (float) $sourceLot->available_quantity);
        $this->assertSame(10.0, (float) $sourceLot->available_weight_kg);
        $this->assertSame('available', $sourceLot->lot_status);

        $this->assertSame(0.0, (float) $outputLot->available_quantity);
        $this->assertSame(0.0, (float) $outputLot->available_weight_kg);
        $this->assertSame('cancelled', $outputLot->lot_status);
        $this->assertFalse((bool) $outputLot->is_saleable);

        $this->assertDatabaseHas('production_run_inputs', ['production_run_id' => $run->id]);
        $this->assertDatabaseHas('production_run_outputs', ['production_run_id' => $run->id]);
        $this->assertDatabaseHas('inventory_lots', ['id' => $outputLot->id, 'lot_status' => 'cancelled']);
    }

    public function test_it_blocks_reversal_after_output_stock_has_changed(): void
    {
        [$run, , , , $outputLot] = $this->makeCompletedRun();

        $outputLot->available_weight_kg = 3.500;
        $outputLot->save();

        $assessment = app(ProductionRunReversalService::class)->assess($run->fresh());

        $this->assertFalse($assessment['can_reverse']);
        $this->assertTrue(collect($assessment['blockers'])->contains(
            fn (string $message) => str_contains($message, 'weight has changed')
        ));
    }

    private function makeCompletedRun(): array
    {
        $sourceProduct = Product::query()->create([
            'name' => 'Pork Belly Full',
            'slug' => 'pork-belly-full-test',
            'sku' => 'PBF-TEST',
            'type' => 'simple',
            'stock_quantity' => 3,
            'sell_unit' => 'piece',
            'base_price' => 100,
            'is_active' => true,
        ]);

        $outputProduct = Product::query()->create([
            'name' => 'Pork Belly Slab',
            'slug' => 'pork-belly-slab-test',
            'sku' => 'PBS-TEST',
            'type' => 'simple',
            'stock_quantity' => 2,
            'sell_unit' => 'piece',
            'base_price' => 100,
            'is_active' => true,
        ]);

        $sourceLot = InventoryLot::query()->create([
            'lot_code' => 'SRC-TEST-001',
            'product_id' => $sourceProduct->id,
            'lot_stage' => 'raw',
            'inward_mode' => 'qty',
            'is_saleable' => false,
            'can_repack' => true,
            'lot_status' => 'available',
            'received_quantity' => 5,
            'available_quantity' => 3,
            'total_weight_kg' => 10,
            'available_weight_kg' => 6,
        ]);

        $run = ProductionRun::query()->create([
            'run_number' => 'PR-TEST-001',
            'run_date' => now()->toDateString(),
            'run_type' => 'raw_to_slab',
            'status' => 'completed',
            'input_weight_kg' => 4,
            'saleable_output_weight_kg' => 4,
            'trim_weight_kg' => 0,
            'waste_weight_kg' => 0,
            'yield_percent' => 100,
        ]);

        ProductionRunInput::query()->create([
            'production_run_id' => $run->id,
            'inventory_lot_id' => $sourceLot->id,
            'product_id' => $sourceProduct->id,
            'consumed_quantity' => 2,
            'consumed_weight_kg' => 4,
        ]);

        $outputLot = InventoryLot::query()->create([
            'lot_code' => 'OUT-TEST-001',
            'product_id' => $outputProduct->id,
            'production_run_id' => $run->id,
            'parent_inventory_lot_id' => $sourceLot->id,
            'root_inventory_lot_id' => $sourceLot->id,
            'lot_stage' => 'slab',
            'inward_mode' => 'qty',
            'is_saleable' => true,
            'can_repack' => true,
            'lot_status' => 'available',
            'received_quantity' => 2,
            'available_quantity' => 2,
            'total_weight_kg' => 4,
            'available_weight_kg' => 4,
        ]);

        ProductionRunOutput::query()->create([
            'production_run_id' => $run->id,
            'inventory_lot_id' => $outputLot->id,
            'product_id' => $outputProduct->id,
            'output_stage' => 'slab',
            'produced_quantity' => 2,
            'produced_weight_kg' => 4,
            'is_saleable' => true,
            'can_repack' => true,
            'inventory_output' => true,
        ]);

        return [$run->fresh(), $sourceProduct, $outputProduct, $sourceLot, $outputLot];
    }
}

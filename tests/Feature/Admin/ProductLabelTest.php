<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Country;
use App\Models\InventoryLot;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductLabelBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductLabelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_open_prefilled_label_designer(): void
    {
        [$admin, $product] = $this->labelProduct();

        $this->actingAs($admin)
            ->get(route('admin.labels.edit', $product))
            ->assertOk()
            ->assertSee('Pork Mince')
            ->assertSee('Pork')
            ->assertSee('India')
            ->assertSee('500 gms')
            ->assertSee('350', false)
            ->assertSee('21526079001348');
    }

    public function test_admin_can_generate_exact_size_product_label_pdf(): void
    {
        [$admin, $product] = $this->labelProduct();

        $response = $this->actingAs($admin)
            ->post(route('admin.labels.pdf', $product), [
                'category' => 'Pork',
                'country' => 'India',
                'product_name' => 'Boneless Mince',
                'price' => '350.00',
                'unit_label' => '500 gms',
                'company_name' => 'Bandara LLP',
                'fssai' => '21526079001348',
                'website' => 'https://bandarallp.com',
                'best_before' => now()->addYear()->format('Y-m'),
                'copies' => 1,
                'disposition' => 'inline',
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename=boneless-mince-label.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_label_pdf_rejects_invalid_print_values(): void
    {
        [$admin, $product] = $this->labelProduct();

        $this->actingAs($admin)
            ->from(route('admin.labels.edit', $product))
            ->post(route('admin.labels.pdf', $product), [
                'category' => '',
                'country' => 'India',
                'product_name' => 'Boneless Mince',
                'price' => '-1',
                'unit_label' => '500 gms',
                'company_name' => 'Bandara LLP',
                'fssai' => '123',
                'website' => 'not-a-url',
                'best_before' => now()->subYear()->format('Y-m'),
                'copies' => 101,
                'disposition' => 'invalid',
            ])
            ->assertRedirect(route('admin.labels.edit', $product))
            ->assertSessionHasErrors([
                'category',
                'price',
                'fssai',
                'website',
                'best_before',
                'copies',
                'disposition',
            ]);
    }

    public function test_admin_can_open_variable_weight_batch_designer_with_inventory_weights(): void
    {
        [$admin, $product] = $this->variableWeightProduct();
        $this->addBellyPieces($product, [3.5, 4.2, 5.5]);

        $this->actingAs($admin)
            ->get(route('admin.labels.batch.edit', $product))
            ->assertOk()
            ->assertSee('Pork Belly Full')
            ->assertSee('Available inventory weights')
            ->assertSee('3.5 kg')
            ->assertSee('4.2 kg')
            ->assertSee('5.5 kg');
    }

    public function test_variable_weight_batch_calculates_each_price_and_generates_one_pdf(): void
    {
        [$admin, $product] = $this->variableWeightProduct();
        $pieces = $this->addBellyPieces($product, [3.5, 4.2, 5.5]);
        $values = array_merge($this->batchValues(), [
            'manual_weights' => '',
            'inventory_piece_ids' => collect($pieces)->pluck('id')->all(),
        ]);
        $batch = app(ProductLabelBatchService::class);

        $items = $batch->resolveItems($product, $values, []);
        $labels = $batch->buildLabels($product, $values, $items);

        $this->assertSame(['3850.00', '4620.00', '6050.00'], array_column($labels, 'price'));
        $this->assertSame(['3.5 kg', '4.2 kg', '5.5 kg'], array_column($labels, 'unit_label'));

        $response = $this->actingAs($admin)
            ->post(route('admin.labels.batch.pdf', $product), $values);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader(
            'Content-Disposition',
            'inline; filename=pork-belly-full-batch-3-labels.pdf',
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_variable_weight_batch_rejects_invalid_print_values(): void
    {
        [$admin, $product] = $this->variableWeightProduct();

        $this->actingAs($admin)
            ->from(route('admin.labels.batch.edit', $product))
            ->post(route('admin.labels.batch.pdf', $product), array_merge($this->batchValues(), [
                'price_per_kg' => '-1',
                'best_before' => now()->subYear()->format('Y-m'),
                'manual_weights' => '-1',
                'disposition' => 'invalid',
            ]))
            ->assertRedirect(route('admin.labels.batch.edit', $product))
            ->assertSessionHasErrors([
                'price_per_kg',
                'best_before',
                'manual_weights',
                'disposition',
            ]);
    }

    private function labelProduct(): array
    {
        Role::findOrCreate('Admin', 'web');

        $admin = User::factory()->create([
            'customer_type' => 'staff',
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        Country::query()->create(['code' => 'IN', 'name' => 'India']);
        $category = Category::query()->create([
            'name' => 'Pork',
            'slug' => 'pork',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Pork Mince',
            'slug' => 'pork-mince',
            'sku' => 'PORK-MINCE-500',
            'type' => 'simple',
            'base_price' => 300,
            'mrp_price' => 333.33,
            'gst_rate' => 5,
            'b2c_price_includes_gst' => true,
            'sell_unit' => 'pack',
            'pack_type' => 'fixed_weight_pack',
            'product_weight' => 0.5,
            'country_of_origin' => 'IN',
            'is_active' => true,
        ]);
        $product->categories()->attach($category);

        return [$admin, $product];
    }

    private function variableWeightProduct(): array
    {
        Role::findOrCreate('Admin', 'web');

        $admin = User::factory()->create([
            'customer_type' => 'staff',
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        Country::query()->create(['code' => 'IN', 'name' => 'India']);
        $category = Category::query()->create([
            'name' => 'Pork',
            'slug' => 'pork',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Pork Belly Full',
            'slug' => 'pork-belly-full',
            'sku' => 'PORK-BELLY-FULL',
            'type' => 'simple',
            'base_price' => 1100,
            'mrp_price' => 1100,
            'gst_rate' => 0,
            'b2c_price_includes_gst' => true,
            'sell_unit' => 'kg',
            'pack_type' => 'variable_weight',
            'country_of_origin' => 'IN',
            'is_active' => true,
        ]);
        $product->categories()->attach($category);

        return [$admin, $product];
    }

    /** @param array<int, float> $weights */
    private function addBellyPieces(Product $product, array $weights): array
    {
        $lot = InventoryLot::query()->create([
            'lot_code' => 'BELLY-LOT-001',
            'product_id' => $product->id,
            'lot_stage' => 'raw',
            'inward_mode' => 'pieces',
            'is_saleable' => true,
            'can_repack' => false,
            'lot_status' => 'available',
            'batch_code' => 'BELLY-BATCH-001',
            'expiry_date' => now()->addYear()->endOfMonth()->toDateString(),
            'received_quantity' => count($weights),
            'available_quantity' => count($weights),
            'total_weight_kg' => array_sum($weights),
            'available_weight_kg' => array_sum($weights),
            'piece_count' => count($weights),
            'available_piece_count' => count($weights),
        ]);

        $pieces = [];

        foreach ($weights as $index => $weight) {
            $pieces[] = InventoryPiece::query()->create([
                'inventory_lot_id' => $lot->id,
                'piece_no' => $index + 1,
                'label' => 'Belly '.($index + 1),
                'weight_kg' => $weight,
                'available_weight_kg' => $weight,
                'status' => 'available',
            ]);
        }

        return $pieces;
    }

    /** @return array<string, mixed> */
    private function batchValues(): array
    {
        return [
            'category' => 'Pork',
            'country' => 'India',
            'product_name' => 'Pork Belly Full',
            'price_per_kg' => '1100.00',
            'company_name' => 'Bandara LLP',
            'fssai' => '21526079001348',
            'website' => 'https://bandarallp.com',
            'best_before' => now()->addYear()->format('Y-m'),
            'manual_weights' => "3.5\n4.2\n5.5",
            'inventory_piece_ids' => [],
            'inventory_pack_ids' => [],
            'disposition' => 'inline',
        ];
    }
}

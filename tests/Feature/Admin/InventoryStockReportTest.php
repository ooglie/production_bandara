<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryStockReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_default_report_includes_active_managed_catalog_rows_with_zero_stock(): void
    {
        $admin = $this->adminUser();
        $zeroProduct = $this->product('Zero Product', [
            'manage_stock' => true,
            'stock_quantity' => null,
            'sell_unit' => 'kg',
        ]);
        $positiveProduct = $this->product('Positive Product', [
            'manage_stock' => true,
            'stock_quantity' => 4,
        ]);
        $variantProduct = $this->product('Variant Product', [
            'type' => 'variable',
            'manage_stock' => false,
        ]);
        $zeroVariant = ProductVariant::query()->create([
            'product_id' => $variantProduct->id,
            'sku' => 'ZERO-VARIANT',
            'name' => 'Zero Variant',
            'manage_stock' => true,
            'stock_quantity' => null,
            'product_weight' => 0,
            'price' => 100,
            'pricing_unit' => 'pack',
            'is_active' => true,
        ]);

        $this->product('Inactive Product', [
            'manage_stock' => true,
            'stock_quantity' => 3,
            'is_active' => false,
        ]);
        $this->product('Unmanaged Product', [
            'manage_stock' => false,
            'stock_quantity' => 3,
        ]);
        $this->product('Internal Product', [
            'manage_stock' => true,
            'stock_quantity' => 3,
            'inventory_role' => 'internal',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.inventory-stock'))
            ->assertOk();

        $rows = $response->viewData('rows');
        $zeroProductRow = $rows->firstWhere(fn (array $row) => $row['stock_type'] === 'product'
            && (int) $row['product_id'] === $zeroProduct->id);
        $positiveProductRow = $rows->firstWhere(fn (array $row) => $row['stock_type'] === 'product'
            && (int) $row['product_id'] === $positiveProduct->id);
        $zeroVariantRow = $rows->firstWhere(fn (array $row) => $row['stock_type'] === 'variant'
            && (int) $row['variant_id'] === $zeroVariant->id);

        $this->assertSame('all', $response->viewData('filters')['status']);
        $this->assertSame('0', $zeroProductRow['quantity']);
        $this->assertSame('0', $zeroProductRow['weight_kg']);
        $this->assertSame('4', $positiveProductRow['quantity']);
        $this->assertSame('0', $zeroVariantRow['quantity']);
        $this->assertSame('0', $zeroVariantRow['packs']);
        $this->assertCount(2, $rows->where('stock_type', 'product'));
        $this->assertCount(1, $rows->where('stock_type', 'variant'));
        $this->assertMatchesRegularExpression(
            '/Zero Product(?:(?!<\/tr>).)*<td[^>]*>\s*0\s*<\/td>/s',
            $response->getContent(),
        );
    }

    public function test_stock_status_filters_and_csv_export_keep_zero_values(): void
    {
        $admin = $this->adminUser();
        $zeroProduct = $this->product('Zero Export Product', [
            'manage_stock' => true,
            'stock_quantity' => null,
        ]);
        $positiveProduct = $this->product('Positive Export Product', [
            'manage_stock' => true,
            'stock_quantity' => 2,
        ]);

        $availableRows = $this->actingAs($admin)
            ->get(route('admin.reports.inventory-stock', [
                'stock_type' => 'product',
                'status' => 'available',
            ]))
            ->assertOk()
            ->viewData('rows');

        $this->assertSame([$positiveProduct->id], $availableRows->pluck('product_id')->map(fn ($id) => (int) $id)->all());

        $zeroRows = $this->actingAs($admin)
            ->get(route('admin.reports.inventory-stock', [
                'stock_type' => 'product',
                'status' => 'zero',
            ]))
            ->assertOk()
            ->viewData('rows');

        $this->assertSame([$zeroProduct->id], $zeroRows->pluck('product_id')->map(fn ($id) => (int) $id)->all());

        $csv = $this->actingAs($admin)
            ->get(route('admin.reports.inventory-stock.export', [
                'stock_type' => 'product',
                'status' => 'all',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Zero Export Product', $csv);
        $this->assertStringContainsString('"Product stock","Zero Export Product"', $csv);
        $this->assertStringContainsString(',active,0,', $csv);
    }

    public function test_all_products_report_includes_every_product_and_uses_latest_vendor_price_or_zero(): void
    {
        $admin = $this->adminUser();
        $pricedProduct = $this->product('No Stock With Price', [
            'stock_quantity' => 0,
        ]);
        $unpricedProduct = $this->product('No Stock Without Price', [
            'stock_quantity' => null,
        ]);
        $stockedProduct = $this->product('Product With Stock', [
            'stock_quantity' => 3,
        ]);
        $variantProduct = $this->product('Prawn', [
            'type' => 'variable',
            'manage_stock' => false,
            'stock_quantity' => 9,
        ]);
        $jumboVariant = ProductVariant::query()->create([
            'product_id' => $variantProduct->id,
            'sku' => 'PRAWN-JUMBO',
            'name' => 'Jumbo',
            'manage_stock' => true,
            'stock_quantity' => 0,
            'product_weight' => 0,
            'price' => 100,
            'pricing_unit' => 'pack',
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $variantProduct->id,
            'sku' => 'PRAWN-XL',
            'name' => 'XL',
            'manage_stock' => true,
            'stock_quantity' => 4,
            'product_weight' => 0,
            'price' => 100,
            'pricing_unit' => 'pack',
            'is_active' => true,
        ]);
        $this->product('Inactive Product', [
            'is_active' => false,
        ]);
        $this->product('Unmanaged Product', [
            'manage_stock' => false,
        ]);
        $this->product('Internal Product', [
            'inventory_role' => 'internal',
        ]);
        $deletedProduct = $this->product('Deleted Product');
        $deletedProduct->delete();

        $vendor = Vendor::query()->create([
            'name' => 'Report Vendor',
            'code' => 'REPORT-VENDOR',
        ]);
        $this->vendorPrice($vendor, $pricedProduct, 'OLD-PRICE', 18, 'paid');
        $this->vendorPrice($vendor, $pricedProduct, 'LATEST-PRICE', 24.5, 'pending');
        $this->vendorPrice($vendor, $pricedProduct, 'CANCELLED-PRICE', 99, 'cancelled');
        $this->vendorPrice($vendor, $variantProduct, 'PRAWN-BASE-PRICE', 20, 'paid');
        $this->vendorPrice($vendor, $variantProduct, 'PRAWN-JUMBO-PRICE', 35, 'paid', $jumboVariant);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.all-products'))
            ->assertOk()
            ->assertSee('All Products')
            ->assertSee('No Stock With Price')
            ->assertSee('₹24.50')
            ->assertSee('No Stock Without Price')
            ->assertSee('₹0.00')
            ->assertSee('Product With Stock')
            ->assertSee('Inactive Product')
            ->assertSee('Unmanaged Product')
            ->assertSee('Internal Product')
            ->assertSee('Jumbo')
            ->assertSee('PRAWN-JUMBO')
            ->assertSee('XL')
            ->assertSee('PRAWN-XL')
            ->assertDontSee('Deleted Product');

        $rows = $response->viewData('rows')->getCollection();
        $this->assertSame(
            [
                'Inactive Product|',
                'Internal Product|',
                'No Stock With Price|',
                'No Stock Without Price|',
                'Prawn|Jumbo',
                'Prawn|XL',
                'Product With Stock|',
                'Unmanaged Product|',
            ],
            $rows->map(fn ($row) => $row->product_name.'|'.($row->variant_name ?? ''))->all(),
        );
        $this->assertSame(24.5, (float) $rows->firstWhere('product_name', 'No Stock With Price')->vendor_price);
        $this->assertSame(0.0, (float) $rows->firstWhere('product_name', 'No Stock Without Price')->vendor_price);
        $this->assertSame(35.0, (float) $rows->firstWhere('sku', 'PRAWN-JUMBO')->vendor_price);
        $this->assertSame(20.0, (float) $rows->firstWhere('sku', 'PRAWN-XL')->vendor_price);

        $csv = $this->actingAs($admin)
            ->get(route('admin.reports.all-products.export'))
            ->assertOk()
            ->streamedContent();
        $csvRows = collect(preg_split('/\r\n|\r|\n/', trim($csv)))
            ->map(fn (string $line) => str_getcsv($line));

        $this->assertSame(['Product Name', 'Variant', 'SKU', 'Vendor Price'], $csvRows->first());
        $this->assertSame(
            ['No Stock With Price', '', 'NO-STOCK-WITH-PRICE', '24.5'],
            $csvRows->first(fn (array $row) => $row[0] === 'No Stock With Price'),
        );
        $this->assertSame(
            ['No Stock Without Price', '', 'NO-STOCK-WITHOUT-PRICE', '0'],
            $csvRows->first(fn (array $row) => $row[0] === 'No Stock Without Price'),
        );
        $this->assertSame(
            ['Product With Stock', '', $stockedProduct->sku, '0'],
            $csvRows->first(fn (array $row) => $row[0] === 'Product With Stock'),
        );
        $this->assertSame(
            ['Prawn', 'Jumbo', 'PRAWN-JUMBO', '35'],
            $csvRows->first(fn (array $row) => $row[2] === 'PRAWN-JUMBO'),
        );
        $this->assertSame(
            ['Prawn', 'XL', 'PRAWN-XL', '20'],
            $csvRows->first(fn (array $row) => $row[2] === 'PRAWN-XL'),
        );
    }

    private function adminUser(): User
    {
        Role::findOrCreate('Admin', 'web');

        $admin = User::factory()->create([
            'customer_type' => 'staff',
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        return $admin;
    }

    private function product(string $name, array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => strtoupper(str($name)->slug('-')->toString()),
            'type' => 'simple',
            'inventory_role' => 'saleable',
            'manage_stock' => true,
            'stock_quantity' => 0,
            'base_price' => 100,
            'sell_unit' => 'pack',
            'is_active' => true,
        ], $attributes));
    }

    private function vendorPrice(
        Vendor $vendor,
        Product $product,
        string $invoiceNumber,
        float $unitCost,
        string $status,
        ?ProductVariant $variant = null,
    ): void {
        $invoice = VendorInvoice::query()->create([
            'vendor_id' => $vendor->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->toDateString(),
            'subtotal' => $unitCost,
            'tax_amount' => 0,
            'total_amount' => $unitCost,
            'status' => $status,
        ]);

        VendorInvoiceItem::query()->create([
            'vendor_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => 1,
            'unit_cost' => $unitCost,
            'tax_amount' => 0,
            'total' => $unitCost,
        ]);
    }
}

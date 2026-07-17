<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_layout_contains_mobile_tablet_and_desktop_search_fields(): void
    {
        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('id="mobile-storefront-search"', false)
            ->assertSee('id="tablet-storefront-search"', false)
            ->assertSee('id="desktop-storefront-search"', false);
    }

    public function test_shop_search_matches_variant_and_category_names(): void
    {
        $dimsum = $this->product('Chicken Siew Mai');
        ProductVariant::query()->create([
            'product_id' => $dimsum->id,
            'sku' => 'SIUMAI-20PC-'.str()->random(6),
            'name' => 'Twenty piece family pack',
            'price' => 180,
            'manage_stock' => false,
            'is_active' => true,
        ]);

        $pork = $this->product('Pork Belly Full');
        $category = Category::query()->create([
            'name' => 'Premium Meat',
            'slug' => 'premium-meat-'.str()->random(6),
            'is_active' => true,
            'position' => 1,
        ]);
        $pork->categories()->attach($category->id);

        $this->get(route('shop.index', ['q' => 'family pack']))
            ->assertOk()
            ->assertSee($dimsum->name)
            ->assertDontSee($pork->name);

        $this->get(route('shop.index', ['q' => 'Premium Meat']))
            ->assertOk()
            ->assertSee($pork->name)
            ->assertDontSee($dimsum->name);
    }

    private function product(string $name): Product
    {
        return Product::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.str()->random(6))->toString(),
            'type' => 'simple',
            'base_price' => 100,
            'mrp_price' => 110,
            'sell_unit' => 'pack',
            'pack_type' => 'quantity',
            'pricing_unit' => 'pack',
            'is_active' => true,
            'manage_stock' => false,
        ]);
    }
}

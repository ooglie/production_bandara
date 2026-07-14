<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class B2BStorefrontVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_b2b_customer_cannot_see_or_open_product_without_b2b_price(): void
    {
        $customer = $this->b2bCustomer();

        $retailOnly = $this->product('Retail only product', null);
        $b2bPriced = $this->product('B2B priced product', 90);

        $this->actingAs($customer)
            ->get(route('shop.index'))
            ->assertOk()
            ->assertDontSee($retailOnly->name)
            ->assertSee($b2bPriced->name);

        $this->actingAs($customer)
            ->get(route('product.show', ['product' => $retailOnly->slug]))
            ->assertNotFound();
    }

    public function test_b2c_customer_still_sees_retail_product(): void
    {
        $customer = User::factory()->create(['customer_type' => 'b2c']);
        $retailOnly = $this->product('Retail visible product', null);

        $this->actingAs($customer)
            ->get(route('shop.index'))
            ->assertOk()
            ->assertSee($retailOnly->name);
    }

    public function test_b2b_collection_hides_retail_only_products(): void
    {
        $customer = $this->b2bCustomer();
        $retailOnly = $this->product('Collection retail product', null);
        $b2bPriced = $this->product('Collection B2B product', 90);

        $collection = ProductCollection::query()->create([
            'name' => 'B2B collection check',
            'slug' => 'b2b-collection-check',
            'is_active' => true,
        ]);
        $collection->products()->attach([
            $retailOnly->id => ['sort_order' => 1],
            $b2bPriced->id => ['sort_order' => 2],
        ]);

        $this->actingAs($customer)
            ->get(route('collections.show', ['collection' => $collection->slug]))
            ->assertOk()
            ->assertDontSee($retailOnly->name)
            ->assertSee($b2bPriced->name);
    }

    public function test_existing_retail_only_cart_item_is_removed_for_b2b_customer(): void
    {
        $customer = $this->b2bCustomer();
        $retailOnly = $this->product('Old retail cart product', null);
        $cart = Cart::query()->create(['user_id' => $customer->id]);
        $item = CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $retailOnly->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($customer);

        $updated = app(CartService::class)->syncPrices($cart);

        $this->assertSame(1, $updated);
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_b2b_rewards_routes_redirect_without_rendering_reward_page(): void
    {
        $customer = $this->b2bCustomer();

        $this->actingAs($customer)
            ->get(route('account.rewards'))
            ->assertRedirect(route('dashboard.customer'));

        $this->actingAs($customer)
            ->get(route('account.rewards.terms'))
            ->assertRedirect(route('dashboard.customer'));
    }

    private function b2bCustomer(): User
    {
        $role = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::factory()->create(['customer_type' => 'b2b']);
        $customer->assignRole($role);

        return $customer;
    }

    private function product(string $name, ?float $b2bPrice): Product
    {
        return Product::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.str()->random(6))->toString(),
            'type' => 'simple',
            'base_price' => 100,
            'mrp_price' => 110,
            'standard_b2b_price' => $b2bPrice,
            'sell_unit' => 'pack',
            'pack_type' => 'quantity',
            'pricing_unit' => 'pack',
            'is_active' => true,
            'manage_stock' => false,
        ]);
    }
}

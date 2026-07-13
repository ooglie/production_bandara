<?php

namespace Tests\Unit;

use App\Http\Requests\Admin\ProductRequest;
use Tests\TestCase;

class ProductParentPricingTest extends TestCase
{
    public function test_parent_prices_are_optional_for_active_variable_products(): void
    {
        $rules = $this->rulesFor([
            'type' => 'variable',
            'is_active' => '1',
            'inventory_role' => 'saleable',
        ]);

        $this->assertContains('nullable', $rules['mrp_price']);
        $this->assertContains('nullable', $rules['base_price']);
        $this->assertNotContains('required', $rules['mrp_price']);
        $this->assertNotContains('required', $rules['base_price']);
    }

    public function test_mrp_is_optional_but_sell_price_is_required_for_active_physical_choice_products(): void
    {
        $rules = $this->rulesFor([
            'type' => 'simple',
            'pack_type' => 'variable_weight',
            'sell_unit' => 'kg',
            'is_active' => '1',
            'inventory_role' => 'both',
        ]);

        $this->assertContains('nullable', $rules['mrp_price']);
        $this->assertNotContains('required', $rules['mrp_price']);
        $this->assertContains('required', $rules['base_price']);
        $this->assertContains('gt:0', $rules['base_price']);
    }

    public function test_parent_prices_are_required_for_active_fixed_price_simple_products(): void
    {
        $rules = $this->rulesFor([
            'type' => 'simple',
            'pack_type' => 'quantity',
            'sell_unit' => 'pack',
            'is_active' => '1',
            'inventory_role' => 'saleable',
        ]);

        $this->assertContains('required', $rules['mrp_price']);
        $this->assertContains('required', $rules['base_price']);
        $this->assertContains('gt:0', $rules['mrp_price']);
        $this->assertContains('gt:0', $rules['base_price']);
    }

    private function rulesFor(array $payload): array
    {
        /** @var ProductRequest $request */
        $request = ProductRequest::create('/admin/products', 'POST', $payload);
        $request->setContainer($this->app);

        return $request->rules();
    }
}

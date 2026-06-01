<?php

namespace App\Services;

use App\Models\B2BCustomerProduct;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\ProductVariant;
use App\Models\User;

class B2BTermsService
{
    /**
     * Unified storefront rule:
     * - B2B users can browse the same active catalog as B2C users.
     * - Buying is allowed when a B2B price can be resolved for the product,
     *   variant, or sellable unit.
     * - Existing b2b_customer_products rows now act as MOQ/terms overrides,
     *   not mandatory catalog visibility gates.
     */
    public function canBuy(?User $user, Product $product, ?ProductSellUnit $sellUnit = null, ?ProductVariant $variant = null): bool
    {
        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            return true;
        }

        return app(PricingService::class)->hasB2BPrice($user, $product, $sellUnit ?: $variant?->sellUnit, $variant);
    }

    public function hasAnyPortfolioAccess(?User $user, Product $product): bool
    {
        return $this->canBuy($user, $product);
    }

    public function minOrderQty(?User $user, Product $product, ?ProductSellUnit $sellUnit = null, ?ProductVariant $variant = null): float
    {
        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            return 1.0;
        }

        $sellUnit = $sellUnit ?: $variant?->sellUnit;
        $query = $this->activeAssignmentQuery($user, $product);

        if ($sellUnit) {
            $row = (clone $query)->where('product_sell_unit_id', $sellUnit->id)->first();
            if ($row && (float) ($row->min_order_quantity ?? 0) > 0) {
                return (float) $row->min_order_quantity;
            }
        }

        $row = (clone $query)->whereNull('product_sell_unit_id')->first();
        if ($row && (float) ($row->min_order_quantity ?? 0) > 0) {
            return (float) $row->min_order_quantity;
        }

        foreach ([$sellUnit, $variant, $product] as $model) {
            $min = (float) ($model?->standard_b2b_min_order_quantity ?? 0);
            if ($min > 0) {
                return $min;
            }
        }

        return 1.0;
    }

    protected function activeAssignmentQuery(User $user, Product $product)
    {
        return B2BCustomerProduct::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('is_active', true);
    }
}

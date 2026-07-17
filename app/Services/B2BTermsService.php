<?php

namespace App\Services;

use App\Models\B2BCustomerProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class B2BTermsService
{
    /**
     * B2B buying is allowed only when an explicit B2B/customer price can be
     * resolved for the selected product option.
     */
    public function canBuy(?User $user, Product $product, ?ProductVariant $variant = null): bool
    {
        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            return true;
        }

        return app(PricingService::class)->hasB2BPrice($user, $product, $variant);
    }

    public function hasAnyPortfolioAccess(?User $user, Product $product): bool
    {
        return app(PricingService::class)->productIsAvailableToUser($user, $product);
    }

    public function minOrderQty(?User $user, Product $product, ?ProductVariant $variant = null): float
    {
        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            return 1.0;
        }

        $query = $this->activeAssignmentQuery($user, $product);

        if ($variant) {
            $row = (clone $query)->where('product_variant_id', $variant->id)->first();
            if ($row && (float) ($row->min_order_quantity ?? 0) > 0) {
                return (float) $row->min_order_quantity;
            }
        }

        $row = (clone $query)->whereNull('product_variant_id')->first();
        if ($row && (float) ($row->min_order_quantity ?? 0) > 0) {
            return (float) $row->min_order_quantity;
        }

        foreach ([$variant, $product] as $model) {
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

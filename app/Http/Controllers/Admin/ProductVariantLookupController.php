<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantLookupController extends Controller
{
    public function byProduct(Request $request, Product $product)
    {
        // Optional: enforce permissions if you want (safe if you already gate routes)
        // abort_unless($request->user()?->can('manage products'), 403);

        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get()
            ->map(function ($v) {
                $label = $v->sku ? $v->sku : ('Variant #' . $v->id);

                // If your table has a "name" column, include it nicely:
                if (!empty($v->name)) {
                    $label .= ' — ' . $v->name;
                }

                return [
                    'id'    => $v->id,
                    'label' => $label,
                ];
            })
            ->values();

        return response()->json([
            'ok'       => true,
            'product'  => ['id' => $product->id, 'name' => $product->name],
            'variants' => $variants,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantOptionsController extends Controller
{
    public function index(Request $request, Product $product)
    {
        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_active', 1)
            // if you use SoftDeletes in model, this is already filtered; leaving it safe anyway:
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'sku', 'name'])
            ->map(function ($v) {
                $label = trim(($v->name ?? '') . ' ' . ($v->sku ? "({$v->sku})" : ''));
                return [
                    'id' => (int) $v->id,
                    'label' => $label !== '' ? $label : ('Variant #' . $v->id),
                ];
            })
            ->values();

        return response()->json(['variants' => $variants]);
    }
}

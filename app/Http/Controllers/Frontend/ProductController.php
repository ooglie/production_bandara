<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\InventoryPiece;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use App\Services\PricingService;

class ProductController extends Controller
{
    public function show(Request $request, Product $product)
    {
        if (isset($product->is_active) && ! $product->is_active) {
            abort(404);
        }

        $product->load([
            'images' => function ($q) {
                $q->orderBy('position')->orderBy('id');
            },
            'variants.attributeValues.attribute',
            'activeRecipes',
        ]);

        $variants = $product->variants ?? collect();
        $pieceSelector = $this->buildPieceSelector($product, $request);

        return view('products.show', compact('product', 'variants', 'pieceSelector'));
    }

    public function variantOptions(Request $request, Product $product): JsonResponse
    {
        if (isset($product->is_active) && ! $product->is_active) {
            abort(404);
        }

        $product->loadMissing([
            'variants.attributeValues.attribute',
        ]);

        $pricing = app(PricingService::class);

        $variants = ($product->variants ?? collect())
            ->filter(fn ($variant) => $this->variantIsSelectable($variant))
            ->map(function ($variant) use ($product, $request, $pricing) {
                $displayPrice = $pricing->priceFor($request->user(), $product, $variant);
                $name = $this->variantLabel($variant);

                return [
                    'id' => $variant->id,
                    'name' => $name,
                    'label' => $name . ' — ₹' . number_format($displayPrice, 2),
                    'price' => $displayPrice,
                    'price_label' => '₹' . number_format($displayPrice, 2),
                    'stock_label' => $this->variantStockLabel($variant),
                ];
            })
            ->values();

        return response()->json([
            'variants' => $variants,
        ]);
    }

    protected function variantIsSelectable($variant): bool
    {
        $isActive = $variant->getAttribute('is_active');

        if ($isActive !== null && ! (bool) $isActive) {
            return false;
        }

        if ((bool) ($variant->manage_stock ?? false)) {
            return (float) ($variant->stock_quantity ?? 0) > 0;
        }

        return true;
    }

    /**
     * Variant prices should display the same way as regular products:
     * - if variant has no own price, fall back to product effective price
     * - if product B2C mode includes GST, display the raw ex-GST variant price with GST added, matching regular product behavior
     */
    protected function normalizeVariantDisplayPrice(Product $product, mixed $rawPrice = null): float
    {
        if ($rawPrice === null || $rawPrice === '') {
            return round((float) ($product->effective_price ?? 0), 2);
        }

        $price = (float) $rawPrice;
        $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);

        if (($product->b2c_price_includes_gst ?? true) && $gstRate > 0) {
            $price = $price * (1 + ($gstRate / 100));
        }

        return round($price, 2);
    }

    protected function variantStockLabel($variant): string
    {
        $packType = (string) ($variant->pack_type ?? '');

        if ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
            $pieces = rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.');
            return $pieces . ' pcs per pack';
        }

        if ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
            $weight = rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.');
            return $weight . ' kg per pack';
        }

        return 'Add 1 pack';
    }

    protected function variantLabel($variant): string
    {
        $name = trim((string) ($variant->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $packType = (string) ($variant->pack_type ?? '');
        if ($packType === 'fixed_piece_pack' && (float) ($variant->pieces_per_pack ?? 0) > 0) {
            return rtrim(rtrim(number_format((float) $variant->pieces_per_pack, 3), '0'), '.') . ' pcs pack';
        }

        if ($packType === 'fixed_weight_pack' && (float) ($variant->product_weight ?? 0) > 0) {
            return rtrim(rtrim(number_format((float) $variant->product_weight, 3), '0'), '.') . ' kg pack';
        }

        $parts = [];

        foreach (($variant->attributeValues ?? collect()) as $value) {
            $attributeName = $value->attribute->name ?? 'Option';
            $valueName = $value->value ?? $value->name ?? '';

            if ($valueName !== '') {
                $parts[] = $attributeName . ': ' . $valueName;
            }
        }

        if (!empty($parts)) {
            return implode(' · ', $parts);
        }

        return $variant->sku ?? ('Variant ' . $variant->id);
    }

    protected function buildPieceSelector(Product $product, Request $request): array
    {
        if (!Schema::hasTable('inventory_pieces') || !Schema::hasTable('inventory_lots')) {
            return [
                'enabled' => false,
                'bands' => [],
                'price_min' => 0,
                'price_max' => 0,
            ];
        }

        $pieces = InventoryPiece::query()
            ->join('inventory_lots', 'inventory_lots.id', '=', 'inventory_pieces.inventory_lot_id')
            ->where('inventory_lots.product_id', $product->id)
            ->where('inventory_lots.is_saleable', true)
            ->where('inventory_lots.lot_status', 'available')
            ->whereIn('inventory_lots.inward_mode', ['pieces', 'pieces_weight'])
            ->where(function ($q) {
                $q->whereNull('inventory_lots.available_piece_count')
                    ->orWhere('inventory_lots.available_piece_count', '>', 0);
            })
            ->where('inventory_pieces.status', 'available')
            ->select([
                'inventory_pieces.weight_kg',
            ])
            ->orderBy('inventory_pieces.weight_kg')
            ->get();

        if ($pieces->isEmpty()) {
            return [
                'enabled' => false,
                'bands' => [],
                'price_min' => 0,
                'price_max' => 0,
            ];
        }

        $displayBasePrice = round((float) app(PricingService::class)->priceFor($request->user(), $product), 2);
        $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));

        $bands = $pieces
            ->groupBy(function ($piece) {
                return $this->bandKeyFromWeight((float) $piece->weight_kg);
            })
            ->map(function ($bandPieces, $bandKey) use ($displayBasePrice, $sellUnit) {
                $choices = collect($bandPieces)
                    ->groupBy(fn ($piece) => number_format((float) $piece->weight_kg, 3, '.', ''))
                    ->map(function ($sameWeightPieces, $weightKey) use ($displayBasePrice, $sellUnit) {
                        $weightKg = (float) $weightKey;

                        $price = $sellUnit === 'kg'
                            ? round($displayBasePrice * $weightKg, 2)
                            : round($displayBasePrice, 2);

                        return [
                            'key' => $weightKey,
                            'weight_kg' => $weightKg,
                            'weight_label' => $this->formatWeightLabel($weightKg),
                            'count' => $sameWeightPieces->count(),
                            'price' => $price,
                        ];
                    })
                    ->sortBy('weight_kg')
                    ->values();

                [$from, $to] = $this->parseBandKey($bandKey);

                return [
                    'key' => $bandKey,
                    'label' => $from . '-' . $to . ' g',
                    'count' => (int) $choices->sum('count'),
                    'price_min' => (float) $choices->min('price'),
                    'price_max' => (float) $choices->max('price'),
                    'choices' => $choices->all(),
                ];
            })
            ->sortBy(function ($band) {
                return (int) explode('-', $band['key'])[0];
            })
            ->values();

        return [
            'enabled' => $bands->isNotEmpty(),
            'bands' => $bands->all(),
            'price_min' => (float) ($bands->min('price_min') ?? 0),
            'price_max' => (float) ($bands->max('price_max') ?? 0),
        ];
    }

    protected function bandKeyFromWeight(float $weightKg): string
    {
        $grams = (int) round($weightKg * 1000);
        $from = (int) floor($grams / 100) * 100;
        $to = $from + 100;

        return $from . '-' . $to;
    }

    protected function parseBandKey(string $key): array
    {
        [$from, $to] = array_pad(explode('-', $key), 2, 0);

        return [(int) $from, (int) $to];
    }

    protected function formatWeightLabel(float $kg): string
    {
        if ($kg < 1) {
            return round($kg * 1000) . ' g';
        }

        return rtrim(rtrim(number_format($kg, 3, '.', ''), '0'), '.') . ' kg';
    }
}
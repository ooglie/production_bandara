<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $categoryIds = $this->normalizeCategoryIds($request->input('category', []));

        $categories = Category::query()
            ->when(Schema::hasColumn('categories', 'is_active'), function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('name')
            ->get();

        $productsQuery = Product::query()
            ->with(['images'])
            ->withCount('variants')
            ->when(Schema::hasColumn('products', 'is_active'), function ($q) {
                $q->where('is_active', true);
            });

        app(PricingService::class)->applyProductAvailabilityFilter($productsQuery, $request->user());

        $search = trim((string) $request->input('q', ''));

        if ($search !== '') {
            $hasProductVariants = Schema::hasTable('product_variants');
            $hasCategoryPivot = Schema::hasTable('categories') && Schema::hasTable('category_product');

            $productsQuery->where(function ($q) use ($search, $hasProductVariants, $hasCategoryPivot) {
                $like = '%' . $search . '%';

                $q->where('products.name', 'like', $like);

                if (Schema::hasColumn('products', 'sku')) {
                    $q->orWhere('products.sku', 'like', $like);
                }

                if (Schema::hasColumn('products', 'short_description')) {
                    $q->orWhere('products.short_description', 'like', $like);
                }

                if (Schema::hasColumn('products', 'description')) {
                    $q->orWhere('products.description', 'like', $like);
                }

                if (Schema::hasColumn('products', 'barcode')) {
                    $q->orWhere('products.barcode', 'like', $like);
                }

                if ($hasProductVariants) {
                    $q->orWhereHas('variants', function ($variantQuery) use ($like) {
                        $variantQuery->where(function ($variantSearch) use ($like) {
                            if (Schema::hasColumn('product_variants', 'name')) {
                                $variantSearch->where('product_variants.name', 'like', $like);
                            }

                            if (Schema::hasColumn('product_variants', 'sku')) {
                                $method = Schema::hasColumn('product_variants', 'name') ? 'orWhere' : 'where';
                                $variantSearch->{$method}('product_variants.sku', 'like', $like);
                            }

                            if (Schema::hasColumn('product_variants', 'barcode')) {
                                $hasPrevious = Schema::hasColumn('product_variants', 'name')
                                    || Schema::hasColumn('product_variants', 'sku');
                                $method = $hasPrevious ? 'orWhere' : 'where';
                                $variantSearch->{$method}('product_variants.barcode', 'like', $like);
                            }
                        });

                        if (Schema::hasColumn('product_variants', 'is_active')) {
                            $variantQuery->where('product_variants.is_active', true);
                        }
                    });
                }

                if ($hasCategoryPivot) {
                    $q->orWhereHas('categories', function ($categoryQuery) use ($like) {
                        $categoryQuery->where('categories.name', 'like', $like);

                        if (Schema::hasColumn('categories', 'is_active')) {
                            $categoryQuery->where('categories.is_active', true);
                        }
                    });
                }
            });
        }

        if ($categoryIds->isNotEmpty()) {
            $productModel = new Product();

            if (method_exists($productModel, 'categories')) {
                $productsQuery->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds->all());
                });
            } elseif (Schema::hasColumn('products', 'category_id')) {
                $productsQuery->whereIn('category_id', $categoryIds->all());
            }
        }

        $sort = (string) $request->input('sort', '');

        if ($sort === 'price_asc' && Schema::hasColumn('products', 'base_price')) {
            $productsQuery->orderBy('base_price')->orderByDesc('id');
        } elseif ($sort === 'price_desc' && Schema::hasColumn('products', 'base_price')) {
            $productsQuery->orderByDesc('base_price')->orderByDesc('id');
        } else {
            $productsQuery->latest();
        }

        $products = $productsQuery
            ->paginate(16)
            ->withQueryString();

        $this->attachPieceSelectorMeta($products->getCollection());

        return view('shop.index', compact(
            'products',
            'categories'
        ));
    }

    public function show(Request $request, Product $product)
    {
        // Only show active products
        if (! $product->is_active) {
            abort(404);
        }

        $product->load([
            'images' => function ($q) {
                $q->orderBy('position')->orderBy('id');
            },
            'variants.attributeValues.attribute',
        ]);

        $pricing = app(PricingService::class);
        if (! $pricing->productIsAvailableToUser($request->user(), $product)) {
            abort(404);
        }

        if (($request->user()?->customer_type ?? 'b2c') === 'b2b') {
            $product->setRelation(
                'variants',
                $product->variants
                    ->filter(fn ($variant) => $pricing->variantIsAvailableToUser($request->user(), $product, $variant))
                    ->values()
            );
        }

        $variants = $product->variants ?? collect();

        return view('products.show', compact('product', 'variants'));
    }

    protected function normalizeCategoryIds(mixed $rawCategories): \Illuminate\Support\Collection
    {
        if (!is_array($rawCategories)) {
            $rawCategories = filled($rawCategories)
                ? explode(',', (string) $rawCategories)
                : [];
        }

        return collect($rawCategories)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Build piece-selector band data for shop cards.
     * Bands are grouped into 100g slabs like 500-600 g, 600-700 g, etc.
     */
    protected function attachPieceSelectorMeta(Collection $products): void
    {
        foreach ($products as $product) {
            $product->piece_selector = [
                'enabled' => false,
                'bands' => [],
            ];
        }

        if ($products->isEmpty()) {
            return;
        }

        if (!Schema::hasTable('inventory_pieces') || !Schema::hasTable('inventory_lots')) {
            return;
        }

        $productIds = $products
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $piecesByProduct = InventoryPiece::query()
            ->join('inventory_lots', 'inventory_lots.id', '=', 'inventory_pieces.inventory_lot_id')
            ->whereIn('inventory_lots.product_id', $productIds)
            ->where('inventory_lots.is_saleable', true)
            ->where('inventory_lots.lot_status', 'available')
            ->whereIn('inventory_lots.inward_mode', ['pieces', 'pieces_weight'])
            ->where(function ($q) {
                $q->whereNull('inventory_lots.available_piece_count')
                    ->orWhere('inventory_lots.available_piece_count', '>', 0);
            })
            ->where('inventory_pieces.status', 'available')
            ->select([
                'inventory_lots.product_id',
                'inventory_pieces.weight_kg',
            ])
            ->orderBy('inventory_lots.product_id')
            ->orderBy('inventory_pieces.weight_kg')
            ->get()
            ->filter(fn ($piece) => (float) ($piece->weight_kg ?? 0) > 0)
            ->groupBy('product_id');

        foreach ($products as $product) {
            $pieces = $piecesByProduct->get($product->id);

            if (!$pieces || $pieces->isEmpty()) {
                continue;
            }

            $bands = $pieces
                ->groupBy(function ($piece) {
                    return $this->bandKeyFromWeight((float) $piece->weight_kg);
                })
                ->map(function ($bandPieces, $bandKey) use ($product) {
                    [$fromGrams, $toGrams] = $this->parseBandKey($bandKey);

                    $effectivePrice = round((float) app(\App\Services\PricingService::class)->priceFor(request()->user(), $product), 2);
                    $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));

                    $choices = collect($bandPieces)
                        ->groupBy(fn ($piece) => number_format((float) $piece->weight_kg, 3, '.', ''))
                        ->map(function ($sameWeightPieces, $weightKey) use ($effectivePrice, $sellUnit) {
                            $weightKg = (float) $weightKey;
                            $price = $sellUnit === 'kg'
                                ? round($effectivePrice * $weightKg, 2)
                                : round($effectivePrice, 2);

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

                    return [
                        'key' => $bandKey,
                        'label' => $fromGrams . '-' . $toGrams . ' g',
                        'count' => (int) $choices->sum('count'),
                        'price_min' => (float) ($choices->min('price') ?? 0),
                        'price_max' => (float) ($choices->max('price') ?? 0),
                        'choices' => $choices->all(),
                    ];
                })
                ->sortBy('key')
                ->values()
                ->all();

            $product->piece_selector = [
                'enabled' => count($bands) > 0,
                'bands' => $bands,
            ];
        }
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

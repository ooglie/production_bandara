<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryPiece;
use App\Models\Product;
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

        $search = trim((string) $request->input('q', ''));

        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';

                $q->where('name', 'like', $like);

                if (Schema::hasColumn('products', 'sku')) {
                    $q->orWhere('sku', 'like', $like);
                }

                if (Schema::hasColumn('products', 'short_description')) {
                    $q->orWhere('short_description', 'like', $like);
                }

                if (Schema::hasColumn('products', 'description')) {
                    $q->orWhere('description', 'like', $like);
                }

                if (Schema::hasColumn('products', 'barcode')) {
                    $q->orWhere('barcode', 'like', $like);
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

    public function show(Product $product)
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
            ->where('inventory_lots.inward_mode', 'pieces')
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

                    $effectivePrice = (float) ($product->effective_price ?? 0);
                    $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));

                    if ($sellUnit === 'kg') {
                        $priceMin = round($effectivePrice * ($fromGrams / 1000), 2);
                        $priceMax = round($effectivePrice * ($toGrams / 1000), 2);
                    } else {
                        $priceMin = round($effectivePrice, 2);
                        $priceMax = round($effectivePrice, 2);
                    }

                    return [
                        'key' => $bandKey,
                        'label' => $fromGrams . '-' . $toGrams . ' g',
                        'count' => $bandPieces->count(),
                        'price_min' => $priceMin,
                        'price_max' => $priceMax,
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
}
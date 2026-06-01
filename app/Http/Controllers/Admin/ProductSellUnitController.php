<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductSellUnitController extends Controller
{
    public function index(Product $product): View
    {
        $product->load([
            'sellUnits' => fn ($query) => $query
                ->withCount('variants')
                ->orderBy('sort_order')
                ->orderBy('name'),
            'variants' => fn ($query) => $query
                ->orderBy('name')
                ->orderBy('sku'),
        ]);

        return view('admin.products.sell-units.index', compact('product'));
    }

    public function create(Product $product): View
    {
        $sellUnit = new ProductSellUnit([
            'product_id' => $product->id,
            'unit_type' => 'pack',
            'pricing_unit' => 'pack',
            'is_retail_visible' => true,
            'is_b2b_visible' => true,
            'is_active' => true,
        ]);

        $variants = $product->variants()
            ->orderBy('name')
            ->orderBy('sku')
            ->get();

        return view('admin.products.sell-units.create', compact('product', 'sellUnit', 'variants'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);

        DB::transaction(function () use ($data, $product) {
            $sellUnit = $product->sellUnits()->create($data['attributes']);
            $this->syncVariantLinks($sellUnit, $data['variant_ids']);
        });

        return redirect()
            ->route('admin.products.sell-units.index', $product)
            ->with('status', 'Sellable unit created. B2C behaviour is unchanged until this unit is used in channel rules.');
    }

    public function edit(ProductSellUnit $sellUnit): View
    {
        $sellUnit->load('product');
        $product = $sellUnit->product;

        $variants = $product->variants()
            ->orderBy('name')
            ->orderBy('sku')
            ->get();

        $selectedVariantIds = $sellUnit->variants()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('admin.products.sell-units.edit', compact('product', 'sellUnit', 'variants', 'selectedVariantIds'));
    }

    public function update(Request $request, ProductSellUnit $sellUnit): RedirectResponse
    {
        $sellUnit->load('product');
        $product = $sellUnit->product;
        $data = $this->validatedData($request, $product, $sellUnit);

        DB::transaction(function () use ($sellUnit, $data) {
            $sellUnit->update($data['attributes']);
            $this->syncVariantLinks($sellUnit, $data['variant_ids']);
        });

        return redirect()
            ->route('admin.products.sell-units.index', $product)
            ->with('status', 'Sellable unit updated.');
    }

    public function destroy(ProductSellUnit $sellUnit): RedirectResponse
    {
        $sellUnit->load('product');
        $product = $sellUnit->product;

        DB::transaction(function () use ($sellUnit) {
            ProductVariant::query()
                ->where('product_sell_unit_id', $sellUnit->id)
                ->update(['product_sell_unit_id' => null]);

            $sellUnit->delete();
        });

        return redirect()
            ->route('admin.products.sell-units.index', $product)
            ->with('status', 'Sellable unit removed. Linked B2C variants were left intact and only unlinked from this unit.');
    }

    protected function validatedData(Request $request, Product $product, ?ProductSellUnit $sellUnit = null): array
    {
        $uniqueSku = Rule::unique('product_sell_units', 'sku')
            ->whereNull('deleted_at')
            ->ignore($sellUnit?->id);

        $uniqueBarcode = Rule::unique('product_sell_units', 'barcode')
            ->whereNull('deleted_at')
            ->ignore($sellUnit?->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:120', $uniqueSku],
            'barcode' => ['nullable', 'string', 'max:120', $uniqueBarcode],
            'unit_type' => ['required', Rule::in(ProductSellUnit::UNIT_TYPES)],
            'pricing_unit' => ['required', Rule::in(ProductSellUnit::PRICING_UNITS)],
            'pieces_per_unit' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'weight_per_unit_kg' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'standard_b2b_price' => ['nullable', 'numeric', 'min:0'],
            'standard_b2b_min_order_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_retail_visible' => ['nullable', 'boolean'],
            'is_b2b_visible' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'variant_ids' => ['nullable', 'array'],
            'variant_ids.*' => ['integer', 'exists:product_variants,id'],
        ]);

        $variantIds = collect($validated['variant_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($variantIds->isNotEmpty()) {
            $validCount = ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereIn('id', $variantIds)
                ->count();

            if ($validCount !== $variantIds->count()) {
                throw ValidationException::withMessages([
                    'variant_ids' => 'One or more selected variants do not belong to this product.',
                ]);
            }
        }

        return [
            'attributes' => [
                'name' => trim((string) $validated['name']),
                'sku' => $this->nullableTrim($validated['sku'] ?? null),
                'barcode' => $this->nullableTrim($validated['barcode'] ?? null),
                'unit_type' => $validated['unit_type'],
                'pricing_unit' => $validated['pricing_unit'],
                'pieces_per_unit' => $validated['pieces_per_unit'] ?? null,
                'weight_per_unit_kg' => $validated['weight_per_unit_kg'] ?? null,
                'standard_b2b_price' => $this->normalizeStoredB2BPrice($validated['standard_b2b_price'] ?? null, $product),
                'standard_b2b_min_order_quantity' => $validated['standard_b2b_min_order_quantity'] ?? null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_retail_visible' => $request->boolean('is_retail_visible'),
                'is_b2b_visible' => $request->boolean('is_b2b_visible'),
                'is_active' => $request->boolean('is_active'),
                'notes' => $this->nullableTrim($validated['notes'] ?? null),
            ],
            'variant_ids' => $variantIds->all(),
        ];
    }

    protected function syncVariantLinks(ProductSellUnit $sellUnit, array $variantIds): void
    {
        ProductVariant::query()
            ->where('product_id', $sellUnit->product_id)
            ->where('product_sell_unit_id', $sellUnit->id)
            ->whereNotIn('id', $variantIds ?: [0])
            ->update(['product_sell_unit_id' => null]);

        if ($variantIds !== []) {
            ProductVariant::query()
                ->where('product_id', $sellUnit->product_id)
                ->whereIn('id', $variantIds)
                ->update(['product_sell_unit_id' => $sellUnit->id]);
        }
    }


    protected function normalizeStoredB2BPrice(mixed $value, Product $product): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $price = (float) $value;
        $includesGst = (bool) ($product->b2b_price_includes_gst ?? false);
        $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
        $factor = 1 + ($gstRate / 100);

        if ($includesGst && $factor > 0) {
            return round($price / $factor, 2);
        }

        return round($price, 2);
    }

    protected function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

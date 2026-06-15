<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            'sale_type' => 'fixed_piece_pack',
            'pricing_unit' => 'pack',
            'b2c_price_includes_gst' => true,
            'is_retail_visible' => true,
            'is_b2b_visible' => true,
            'is_active' => true,
        ]);

        $variants = $product->variants()
            ->whereNull('product_sell_unit_id')
            ->orderBy('name')
            ->orderBy('sku')
            ->get();

        return view('admin.products.sell-units.create', compact('product', 'sellUnit', 'variants'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);

        DB::transaction(function () use ($data, $product) {
            $sellUnit = $product->sellUnits()->create($this->filterSellUnitAttributesForSchema($data['attributes']));
            $this->syncVariantLinks($sellUnit, $data['variant_ids']);
            $this->syncSimpleProductDefaultsFromSellUnit($product, $sellUnit);
        });

        return redirect()
            ->route('admin.products.sell-units.index', $product)
            ->with('status', 'Sellable format created. No storefront variant was created.');
    }

    public function edit(ProductSellUnit $sellUnit): View
    {
        $sellUnit->load('product');
        $product = $sellUnit->product;

        $variants = $product->variants()
            ->where(function ($query) use ($sellUnit) {
                $query->whereNull('product_sell_unit_id')
                    ->orWhere('product_sell_unit_id', $sellUnit->id);
            })
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

        DB::transaction(function () use ($sellUnit, $data, $product) {
            $sellUnit->update($this->filterSellUnitAttributesForSchema($data['attributes']));
            $this->syncVariantLinks($sellUnit, $data['variant_ids']);
            $this->syncSimpleProductDefaultsFromSellUnit($product, $sellUnit->fresh());
        });

        return redirect()
            ->route('admin.products.sell-units.index', $product)
            ->with('status', 'Sellable format updated. Storefront product display was not changed.');
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
            ->with('status', 'Sellable format removed. Any manually linked variants were left intact and only unlinked.');
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
            'sale_type' => ['required', Rule::in(ProductSellUnit::SALE_TYPES)],
            'unit_type' => ['nullable', Rule::in(ProductSellUnit::UNIT_TYPES)],
            'pricing_unit' => ['nullable', Rule::in(ProductSellUnit::PRICING_UNITS)],
            'pieces_per_unit' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'weight_per_unit_kg' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'mrp_price' => ['nullable', 'numeric', 'min:0'],
            'b2c_price_includes_gst' => ['nullable', 'boolean'],
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

        $saleType = (string) ($validated['sale_type'] ?? 'fixed_piece_pack');
        $pieces = $this->toDecimal($validated['pieces_per_unit'] ?? null, 3);
        $weight = $this->toDecimal($validated['weight_per_unit_kg'] ?? null, 3);

        if ($saleType === 'fixed_piece_pack' && (! $pieces || $pieces <= 0)) {
            throw ValidationException::withMessages([
                'pieces_per_unit' => 'Enter pieces per pack for this sellable format.',
            ]);
        }

        if (in_array($saleType, ['fixed_weight_pack', 'variable_weight'], true) && (! $weight || $weight <= 0)) {
            throw ValidationException::withMessages([
                'weight_per_unit_kg' => $saleType === 'fixed_weight_pack'
                    ? 'Enter fixed pack weight in kg for this sellable format.'
                    : 'Enter the default billing/step weight in kg for this sellable format.',
            ]);
        }

        [$unitType, $pricingUnit] = $this->defaultUnitsForSaleType(
            $saleType,
            $validated['unit_type'] ?? null,
            $validated['pricing_unit'] ?? null
        );

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
                    'variant_ids' => 'One or more selected order options do not belong to this product.',
                ]);
            }
        }

        $b2cIncludesGst = $request->boolean('b2c_price_includes_gst', true);
        $factor = $this->gstFactor($product);

        return [
            'attributes' => [
                'name' => trim((string) $validated['name']),
                'sku' => $this->nullableTrim($validated['sku'] ?? null),
                'barcode' => $this->nullableTrim($validated['barcode'] ?? null),
                'sale_type' => $saleType,
                'unit_type' => $unitType,
                'pricing_unit' => $pricingUnit,
                'pieces_per_unit' => $saleType === 'fixed_piece_pack' ? $pieces : null,
                'weight_per_unit_kg' => in_array($saleType, ['fixed_weight_pack', 'variable_weight'], true) ? $weight : null,
                'base_price' => $this->normalizeStoredPrice($validated['base_price'] ?? null, $b2cIncludesGst, $factor),
                'mrp_price' => $this->normalizeStoredPrice($validated['mrp_price'] ?? null, $b2cIncludesGst, $factor),
                'b2c_price_includes_gst' => $b2cIncludesGst,
                'standard_b2b_price' => $this->normalizeStoredB2BPrice($validated['standard_b2b_price'] ?? null, $product),
                'standard_b2b_min_order_quantity' => $this->toDecimal($validated['standard_b2b_min_order_quantity'] ?? null, 3),
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

    /**
     * Keep sellable-format unit metadata predictable. The frontend remains product-based,
     * so these values are used by admin stock/repack screens only and should not create
     * or imply storefront variants.
     */
    protected function defaultUnitsForSaleType(string $saleType, ?string $requestedUnitType = null, ?string $requestedPricingUnit = null): array
    {
        $defaults = match ($saleType) {
            'variable_weight' => ['kg', 'kg'],
            'fixed_weight_pack', 'fixed_piece_pack' => ['pack', 'pack'],
            default => ['pack', 'pack'],
        };

        $unitType = in_array((string) $requestedUnitType, ProductSellUnit::UNIT_TYPES, true)
            ? (string) $requestedUnitType
            : $defaults[0];

        $pricingUnit = in_array((string) $requestedPricingUnit, ProductSellUnit::PRICING_UNITS, true)
            ? (string) $requestedPricingUnit
            : $defaults[1];

        // The simplified UI should not accidentally leave fixed packs as kg or
        // variable-weight formats as pack when browser state/autofill is stale.
        if ($saleType === 'variable_weight') {
            return ['kg', 'kg'];
        }

        if (in_array($saleType, ['fixed_weight_pack', 'fixed_piece_pack'], true)) {
            return [in_array($unitType, ['pack', 'box'], true) ? $unitType : 'pack', 'pack'];
        }

        return [$unitType, $pricingUnit];
    }

    /**
     * Sellable formats are an admin/inventory concept only. They must not force
     * the product into `variable` mode or create product variants, because the
     * existing storefront already displays simple products correctly.
     *
     * To make a one-format/simple product usable without duplicate entry, copy
     * only blank product defaults from the sellable format. Existing product
     * price/weight settings are never overwritten.
     */
    protected function syncSimpleProductDefaultsFromSellUnit(Product $product, ProductSellUnit $sellUnit): void
    {
        $fresh = Product::query()->lockForUpdate()->find($product->id);
        if (! $fresh) {
            return;
        }

        $updates = [];

        if ((string) ($fresh->type ?? 'simple') === 'variable') {
            $hasVisibleVariants = ProductVariant::query()
                ->where('product_id', $fresh->id)
                ->where('is_active', true)
                ->exists();

            if ($hasVisibleVariants) {
                return;
            }

            $updates['type'] = 'simple';
        }

        $copiedRetailPrice = false;

        $basePrice = $this->toDecimal($sellUnit->base_price ?? null, 2);
        if ($basePrice !== null && $basePrice > 0 && (float) ($fresh->base_price ?? 0) <= 0) {
            $updates['base_price'] = $basePrice;
            $copiedRetailPrice = true;
        }

        $mrpPrice = $this->toDecimal($sellUnit->mrp_price ?? null, 2);
        if ($mrpPrice !== null && $mrpPrice > 0 && (float) ($fresh->mrp_price ?? 0) <= 0) {
            $updates['mrp_price'] = $mrpPrice;
            $copiedRetailPrice = true;
        }

        if ($copiedRetailPrice && Schema::hasColumn('products', 'b2c_price_includes_gst')) {
            $updates['b2c_price_includes_gst'] = (bool) ($sellUnit->b2c_price_includes_gst ?? true);
        }

        $standardB2BPrice = $this->toDecimal($sellUnit->standard_b2b_price ?? null, 2);
        if ($standardB2BPrice !== null && $standardB2BPrice > 0 && (float) ($fresh->standard_b2b_price ?? 0) <= 0) {
            $updates['standard_b2b_price'] = $standardB2BPrice;
        }

        $standardB2BMoq = $this->toDecimal($sellUnit->standard_b2b_min_order_quantity ?? null, 3);
        if ($standardB2BMoq !== null && $standardB2BMoq > 0 && (float) ($fresh->standard_b2b_min_order_quantity ?? 0) <= 0) {
            $updates['standard_b2b_min_order_quantity'] = $standardB2BMoq;
        }

        $weight = $this->toDecimal($sellUnit->weight_per_unit_kg ?? null, 3);
        if ($weight !== null && $weight > 0 && (float) ($fresh->product_weight ?? 0) <= 0) {
            $updates['product_weight'] = $weight;
        }

        if (Schema::hasColumn('products', 'sell_unit')) {
            $suggestedSellUnit = (string) ($sellUnit->sale_type ?? '') === 'variable_weight' ? 'kg' : 'pack';
            $currentSellUnit = (string) ($fresh->sell_unit ?? '');

            if ($suggestedSellUnit !== '' && in_array($currentSellUnit, ['', 'piece'], true)) {
                $updates['sell_unit'] = $suggestedSellUnit;
            }
        }

        if ($updates !== []) {
            $fresh->forceFill($updates)->save();
        }
    }

    protected function normalizeStoredB2BPrice(mixed $value, Product $product): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $price = (float) $value;
        $includesGst = (bool) ($product->b2b_price_includes_gst ?? false);

        return $this->normalizeStoredPrice($price, $includesGst, $this->gstFactor($product));
    }

    protected function normalizeStoredPrice(mixed $value, bool $includesGst, float $factor): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $price = (float) $value;
        if ($includesGst && $factor > 0) {
            $price = $price / $factor;
        }

        return round(max($price, 0), 2);
    }

    protected function toDecimal(mixed $value, int $scale = 3): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, $scale);
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function gstFactor(Product $product): float
    {
        $rate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);

        return 1 + ($rate / 100);
    }

    protected function filterSellUnitAttributesForSchema(array $attributes): array
    {
        if (! Schema::hasTable('product_sell_units')) {
            return $attributes;
        }

        return collect($attributes)
            ->filter(fn ($value, $column) => Schema::hasColumn('product_sell_units', (string) $column))
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductVariantController extends Controller
{
    public function index(Product $product)
    {
        $variants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.variants.index', compact('product', 'variants'));
    }

    public function create(Product $product)
    {
        [$attributeValuesByAttribute, $existingVariantAttributes] = $this->variantAttributeData($product);

        return view('admin.products.variants.create', compact(
            'product',
            'attributeValuesByAttribute',
            'existingVariantAttributes'
        ));
    }

    public function store(Request $request, Product $product)
    {
        $data = $this->validatedData($request, $product);

        $variant = DB::transaction(function () use ($data, $product) {
            $variant = ProductVariant::create($this->buildPayload($data, $product));
            $this->syncVariantAttributes($variant, $product, $data['variant_attributes'] ?? []);
            $this->syncParentProductForPackVariants($product);

            return $variant;
        });

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with('status', 'Variant created successfully.');
    }

    public function edit(ProductVariant $variant)
    {
        $product = $variant->product;

        if (! $product) {
            throw new NotFoundHttpException();
        }

        [$attributeValuesByAttribute, $existingVariantAttributes] = $this->variantAttributeData($product, $variant);

        return view('admin.products.variants.edit', compact(
            'product',
            'variant',
            'attributeValuesByAttribute',
            'existingVariantAttributes'
        ));
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $product = $variant->product;

        if (! $product) {
            throw new NotFoundHttpException();
        }

        $data = $this->validatedData($request, $product, $variant);

        DB::transaction(function () use ($variant, $data, $product) {
            $variant->update($this->buildPayload($data, $product));
            $this->syncVariantAttributes($variant, $product, $data['variant_attributes'] ?? []);
            $this->syncParentProductForPackVariants($product);
        });

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with('status', 'Variant updated successfully.');
    }

    public function destroy(ProductVariant $variant)
    {
        $product = $variant->product;

        if (! $product) {
            throw new NotFoundHttpException();
        }

        DB::transaction(function () use ($variant, $product) {
            if (ProductVariant::hasVariantAttributePivotTable()) {
                DB::table('product_variant_attribute_values')
                    ->where('product_variant_id', $variant->id)
                    ->delete();
            }

            $variant->delete();
            $this->syncParentProductForPackVariants($product);
        });

        return redirect()
            ->route('admin.products.variants.index', $product)
            ->with('status', 'Variant removed successfully.');
    }

    protected function validatedData(Request $request, Product $product, ?ProductVariant $variant = null): array
    {
        $packType = (string) $request->input('pack_type', $variant->pack_type ?? 'quantity');
        $isFixedPiecePack = $packType === 'fixed_piece_pack';

        $productWeightRules = $isFixedPiecePack
            ? ['nullable', 'numeric', 'min:0']
            : ['required', 'numeric', 'gt:0'];

        $piecesPerPackRules = $isFixedPiecePack
            ? ['required', 'numeric', 'gt:0']
            : ['nullable', 'numeric', 'min:0'];

        return $request->validate([
            'barcode' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('product_variants', 'barcode')
                    ->ignore($variant?->id)
                    ->whereNull('deleted_at'),
            ],

            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')
                    ->ignore($variant?->id)
                    ->whereNull('deleted_at'),
            ],

            'name' => ['nullable', 'string', 'max:255'],
            'pack_type' => ['required', Rule::in(['quantity', 'fixed_weight_pack', 'fixed_piece_pack'])],

            'mrp_price' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'min_order_quantity' => ['nullable', 'numeric', 'min:0'],
            'standard_b2b_price' => ['nullable', 'numeric', 'min:0'],
            'standard_b2b_min_order_quantity' => ['nullable', 'numeric', 'min:0.001'],

            'product_weight' => $productWeightRules,
            'pieces_per_pack' => $piecesPerPackRules,
            'pricing_unit' => ['nullable', Rule::in(['pack', 'kg'])],

            'manage_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],

            'variant_attributes' => ['nullable', 'array'],
            'variant_attributes.*' => ['nullable', 'integer'],
        ]);
    }

    protected function buildPayload(array $data, Product $product): array
    {
        $packType = (string) ($data['pack_type'] ?? 'quantity');
        $pricingUnit = $this->nullableString($data['pricing_unit'] ?? null) ?: 'pack';
        $productWeight = $this->nullableNumber($data['product_weight'] ?? null);

        if ($packType === 'fixed_piece_pack' && $productWeight === null) {
            // Existing DBs have product_variants.product_weight as NOT NULL.
            // Piece-pack variants such as Dimsum 10 pcs do not need a kg weight.
            $productWeight = 0.0;
        }

        $payload = [
            'product_id' => $product->id,
            'barcode' => $this->nullableString($data['barcode'] ?? null),
            'sku' => trim((string) $data['sku']),
            'name' => $this->nullableString($data['name'] ?? null),

            'manage_stock' => (bool) ($data['manage_stock'] ?? false),
            'stock_quantity' => $this->nullableNumber($data['stock_quantity'] ?? null),
            'low_stock_threshold' => $this->nullableNumber($data['low_stock_threshold'] ?? null),
            'min_order_quantity' => $this->nullableNumber($data['min_order_quantity'] ?? null),
            'standard_b2b_price' => $this->normalizeStoredPrice($this->nullableNumber($data['standard_b2b_price'] ?? null), (bool) ($product->b2b_price_includes_gst ?? false), $product),
            'standard_b2b_min_order_quantity' => $this->nullableNumber($data['standard_b2b_min_order_quantity'] ?? null),

            'product_weight' => $productWeight ?? 0.0,
            'price' => $this->normalizeStoredPrice(round((float) $data['price'], 2), (bool) ($product->b2c_price_includes_gst ?? true), $product) ?? 0.0,
            'pricing_unit' => $pricingUnit,

            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if ($this->tableHasColumn('product_variants', 'pack_type')) {
            $payload['pack_type'] = $packType;
        }

        if ($this->tableHasColumn('product_variants', 'pieces_per_pack')) {
            $payload['pieces_per_pack'] = $packType === 'fixed_piece_pack'
                ? $this->nullableNumber($data['pieces_per_pack'] ?? null)
                : null;
        }

        if ($this->tableHasColumn('product_variants', 'mrp_price')) {
            $payload['mrp_price'] = $this->normalizeStoredPrice($this->nullableNumber($data['mrp_price'] ?? null), (bool) ($product->b2c_price_includes_gst ?? true), $product);
        }

        return $payload;
    }


    protected function syncParentProductForPackVariants(Product $product): void
    {
        $activeVariants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->get();

        if ($activeVariants->isEmpty()) {
            if ((string) ($product->type ?? 'simple') === 'variable') {
                $product->type = 'simple';
                $product->save();
            }

            return;
        }

        $product->type = 'variable';
        $product->manage_stock = true;
        $product->stock_quantity = round((float) $activeVariants->sum(fn (ProductVariant $variant) => (float) ($variant->stock_quantity ?? 0)), 3);
        $product->save();
    }

    protected function syncVariantAttributes(ProductVariant $variant, Product $product, array $submittedAttributes): void
    {
        if (! ProductVariant::hasVariantAttributePivotTable()) {
            return;
        }

        $selectedValueIds = $this->validatedVariantAttributeValues($product, $submittedAttributes);

        DB::table('product_variant_attribute_values')
            ->where('product_variant_id', $variant->id)
            ->delete();

        if (empty($selectedValueIds)) {
            return;
        }

        $rows = [];
        $usesTimestamps =
            Schema::hasColumn('product_variant_attribute_values', 'created_at') &&
            Schema::hasColumn('product_variant_attribute_values', 'updated_at');

        foreach ($selectedValueIds as $productAttributeValueId) {
            $row = [
                'product_variant_id' => $variant->id,
                'product_attribute_value_id' => $productAttributeValueId,
            ];

            if ($usesTimestamps) {
                $row['created_at'] = now();
                $row['updated_at'] = now();
            }

            $rows[] = $row;
        }

        DB::table('product_variant_attribute_values')->insert($rows);
    }

    /**
     * Returns:
     * - $attributeValuesByAttribute: grouped values for the form
     * - $existingVariantAttributes: [attribute_id => product_attribute_values.id]
     */
    protected function variantAttributeData(Product $product, ?ProductVariant $variant = null): array
    {
        $attributeValuesByAttribute = $this->productAttributeValues($product)
            ->groupBy(fn ($row) => (int) $row->attribute_id)
            ->filter(fn ($group, $attributeId) => (int) $attributeId > 0);

        $existingVariantAttributes = [];

        if ($variant && ProductVariant::hasVariantAttributePivotTable()) {
            $existingRows = DB::table('product_variant_attribute_values as pvav')
                ->join('product_attribute_values as pav', 'pav.id', '=', 'pvav.product_attribute_value_id')
                ->where('pvav.product_variant_id', $variant->id)
                ->select([
                    'pav.attribute_id',
                    'pvav.product_attribute_value_id',
                ])
                ->get();

            foreach ($existingRows as $row) {
                $existingVariantAttributes[(int) $row->attribute_id] = (int) $row->product_attribute_value_id;
            }
        }

        return [$attributeValuesByAttribute, $existingVariantAttributes];
    }

    /**
     * Builds rows shaped for your existing Blade:
     * - id = product_attribute_values.id
     * - name = attribute_values.name
     * - attribute = object with id/name/display_name
     * - position if available
     */
    protected function productAttributeValues(Product $product): Collection
    {
        $attributeHasDisplayName = $this->tableHasColumn('attributes', 'display_name');
        $attributeValueHasPosition = $this->tableHasColumn('attribute_values', 'position');

        $select = [
            'pav.id',
            'pav.product_id',
            'pav.attribute_id',
            'pav.attribute_value_id',
            'a.name as attribute_name',
            'av.name as name',
        ];

        if ($attributeHasDisplayName) {
            $select[] = 'a.display_name';
        }

        if ($attributeValueHasPosition) {
            $select[] = 'av.position';
        }

        $rows = DB::table('product_attribute_values as pav')
            ->join('attributes as a', 'a.id', '=', 'pav.attribute_id')
            ->join('attribute_values as av', 'av.id', '=', 'pav.attribute_value_id')
            ->where('pav.product_id', $product->id)
            ->select($select)
            ->orderBy('pav.attribute_id')
            ->orderBy($attributeValueHasPosition ? 'av.position' : 'av.name')
            ->orderBy('av.name')
            ->get();

        return $rows->map(function ($row) use ($attributeHasDisplayName, $attributeValueHasPosition) {
            return (object) [
                'id' => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'attribute_id' => (int) $row->attribute_id,
                'attribute_value_id' => (int) $row->attribute_value_id,
                'name' => $row->name,
                'position' => $attributeValueHasPosition ? (int) ($row->position ?? 0) : 0,
                'attribute' => (object) [
                    'id' => (int) $row->attribute_id,
                    'name' => $row->attribute_name,
                    'display_name' => $attributeHasDisplayName ? ($row->display_name ?? null) : null,
                ],
            ];
        });
    }

    /**
     * Validates posted selections.
     * Posted option value is product_attribute_values.id, not attribute_values.id.
     */
    protected function validatedVariantAttributeValues(Product $product, ?array $submitted): array
    {
        if (! is_array($submitted) || empty($submitted)) {
            return [];
        }

        $pairs = [];
        foreach ($submitted as $attributeId => $productAttributeValueId) {
            if ($productAttributeValueId === null || $productAttributeValueId === '') {
                continue;
            }

            $pairs[(int) $attributeId] = (int) $productAttributeValueId;
        }

        if (empty($pairs)) {
            return [];
        }

        $rows = DB::table('product_attribute_values')
            ->where('product_id', $product->id)
            ->whereIn('id', array_values($pairs))
            ->get()
            ->keyBy('id');

        $validated = [];

        foreach ($pairs as $postedAttributeId => $productAttributeValueId) {
            $row = $rows->get($productAttributeValueId);

            if (! $row) {
                continue;
            }

            if ((int) $row->attribute_id !== $postedAttributeId) {
                continue;
            }

            $validated[] = $productAttributeValueId;
        }

        return array_values(array_unique($validated));
    }

    protected function tableHasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    protected function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }


    protected function normalizeStoredPrice(?float $value, bool $includesGst, Product $product): ?float
    {
        if ($value === null) {
            return null;
        }

        $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
        $factor = 1 + ($gstRate / 100);

        if ($includesGst && $factor > 0) {
            return round($value / $factor, 2);
        }

        return round($value, 2);
    }

    protected function nullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
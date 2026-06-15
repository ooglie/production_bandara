<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Country;
use App\Models\HsnCode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(\Illuminate\Http\Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $barcode = trim((string) $request->input('barcode', ''));

        // Barcode / SKU direct open
        if ($barcode !== '') {
            $matchedProduct = Product::query()
                ->where('barcode', $barcode)
                ->orWhere('sku', $barcode)
                ->first();

            if ($matchedProduct) {
                return redirect()->route('admin.products.edit', $matchedProduct);
            }

            return redirect()
                ->route('admin.products.index', collect($request->except(['barcode', 'page']))
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->all())
                ->with('error', 'No product found for the scanned barcode / SKU.');
        }

        $query = Product::query()
            ->with(['vendor', 'categories'])
            ->latest();

        // Search by name / SKU
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Active / inactive filter
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Type filter
        if ($request->filled('type')) {
            $type = (string) $request->input('type');

            if (in_array($type, ['simple', 'variable'], true)) {
                $query->where('type', $type);
            }
        }

        // Flag filter
        if ($request->filled('flag')) {
            $flag = (string) $request->input('flag');

            if ($flag === 'featured') {
                $query->where('is_featured', true);
            } elseif ($flag === 'new') {
                $query->where('is_new', true);
            } elseif ($flag === 'special') {
                $query->where('is_special', true);
            }
        }

        $products = $query
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $product = new Product();

        return view('admin.products.create', $this->formData($product));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product();

        $this->persistProduct($product, $request);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', $request->isDraftSave()
                ? 'Product draft saved. It remains inactive until price and weight are completed.'
                : 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->persistProduct($product, $request);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', $request->isDraftSave()
                ? 'Product draft updated. It remains inactive until price and weight are completed.'
                : 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product) {
            if (method_exists($product, 'categories')) {
                $product->categories()->detach();
            }

            if (method_exists($product, 'attributeValues')) {
                $product->attributeValues()->detach();
            }

            $product->delete();
        });

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product deleted successfully.');
    }

    protected function formData(Product $product): array
    {
        $product->loadMissing(['categories', 'attributeValues']);

        return [
            'product' => $product,

            'vendors' => Vendor::query()
                ->orderBy('code')
                ->get(),

            'categories' => Category::query()
                ->with('parent')
                ->orderBy('name')
                ->get(),

            'selectedCategoryIds' => $product->categories
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),

            'attributes' => Attribute::query()
                ->with([
                    'values' => fn ($q) => $q->orderBy('position')->orderBy('name'),
                ])
                ->orderBy('name')
                ->get(),

            'selectedAttributeValueIds' => $product->attributeValues
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),

            'hsnCodes' => HsnCode::query()
                ->orderBy('code')
                ->get(),

            'countries' => Country::query()
                ->orderBy('name')
                ->get(),

            // kept because your Blade expects it
            'supplierVendors' => Vendor::query()
                ->orderBy('code')
                ->get(),
        ];
    }

    protected function persistProduct(Product $product, ProductRequest $request): void
    {
        DB::transaction(function () use ($product, $request) {
            $product->forceFill($this->buildPayload($product, $request));
            $product->save();

            $this->syncRelations($product, $request);
        });
    }

    protected function buildPayload(Product $product, ProductRequest $request): array
    {
        $validated = $request->validated();
        $isDraft = $request->isDraftSave();

        $name = trim((string) $validated['name']);
        $slug = $this->resolveSlug($validated['slug'] ?? null, $name, $product);

        $gstRate = $this->toDecimal($validated['gst_rate'] ?? null);
        $hsnRate = null;
        if (! empty($validated['hsn_code_id'])) {
            $hsnRate = $this->toDecimal(HsnCode::query()->whereKey($validated['hsn_code_id'])->value('gst_rate'));
        }

        if (($gstRate === null || $gstRate <= 0) && $hsnRate !== null && $hsnRate > 0) {
            $gstRate = $hsnRate;
        }

        $gstRate = $gstRate ?? 0.0;
        $b2cPriceIncludesGst = $request->boolean('b2c_price_includes_gst', true);
        $b2bPriceIncludesGst = $request->boolean('b2b_price_includes_gst', false);
        $factor = 1 + ($gstRate / 100);

        $enteredSell = $this->toDecimal($validated['base_price'] ?? null);
        $enteredMrp = $this->toDecimal($validated['mrp_price'] ?? null);
        $enteredSpecial = $this->toDecimal($validated['special_price'] ?? null);
        $enteredStandardB2B = $this->toDecimal($validated['standard_b2b_price'] ?? null);
        $specialAudience = $validated['special_audience'] ?? 'b2c';
        $inventoryRole = $validated['inventory_role'] ?? 'saleable';
        $specialPriceIncludesGst = $specialAudience === 'b2b' ? $b2bPriceIncludesGst : $b2cPriceIncludesGst;

        $payload = [
            'name' => $name,
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'storage_guidance' => $this->nullableTrim($validated['storage_guidance'] ?? null) ?? Product::defaultStorageGuidanceText(),
            'delivery_support' => $this->nullableTrim($validated['delivery_support'] ?? null) ?? Product::defaultDeliverySupportText(),
            'slug' => $slug,
            'sku' => $validated['sku'],
            'type' => $validated['type'],
            'inventory_role' => $inventoryRole,

            'vendor_id' => $validated['vendor_id'] ?? null,
            'barcode' => $this->nullableTrim($validated['barcode'] ?? null),

            // Stored in DB as EXCL GST. B2C input defaults to GST-inclusive;
            // B2B input defaults to GST-exclusive.
            'base_price' => $this->normalizeStoredPrice($enteredSell, $b2cPriceIncludesGst, $factor) ?? 0.0,
            'mrp_price' => $this->normalizeStoredPrice($enteredMrp, $b2cPriceIncludesGst, $factor) ?? 0.0,
            'special_price' => $this->normalizeStoredPrice($enteredSpecial, $specialPriceIncludesGst, $factor),
            'standard_b2b_price' => $this->normalizeStoredPrice($enteredStandardB2B, $b2bPriceIncludesGst, $factor),
            'standard_b2b_min_order_quantity' => $this->toDecimal($validated['standard_b2b_min_order_quantity'] ?? null),

            'b2c_price_includes_gst' => $b2cPriceIncludesGst,
            'b2b_price_includes_gst' => $b2bPriceIncludesGst,
            'hsn_code_id' => $validated['hsn_code_id'],
            'gst_rate' => round($gstRate, 2),

            'sell_unit' => $validated['sell_unit'] ?? 'piece',
            'pack_type' => $validated['pack_type'] ?? $this->inferPackTypeFromProductInput($validated),
            'product_weight' => $this->toDecimal($validated['product_weight'] ?? null),
            'pieces_per_pack' => $this->toDecimal($validated['pieces_per_pack'] ?? null),

            // inventory / order control
            'stock_quantity' => $this->toDecimal($validated['stock_quantity'] ?? null) ?? 0.0,
            'low_stock_threshold' => $this->toDecimal($validated['low_stock_threshold'] ?? null) ?? 0.0,
            'min_order_quantity' => $this->toDecimal($validated['min_order_quantity'] ?? null) ?? 0.0,

            'country_of_origin' => $validated['country_of_origin'] ?? null,

            // advanced inventory behavior
            'lot_stage_default' => $validated['lot_stage_default'] ?? null,
            'inventory_is_saleable' => $request->boolean('inventory_is_saleable'),
            'inventory_can_repack' => $request->boolean('inventory_can_repack'),

            // storefront toggles. Draft products are always inactive and are not surfaced
            // through featured/new/special storefront collections.
            'manage_stock' => $request->boolean('manage_stock'),
            'dynamic_pricing_enabled' => $isDraft ? false : $request->boolean('dynamic_pricing_enabled'),
            'is_featured' => $isDraft ? false : $request->boolean('is_featured'),
            'is_new' => $isDraft ? false : $request->boolean('is_new'),
            'is_special' => $isDraft ? false : $request->boolean('is_special'),
            'special_audience' => $specialAudience,
            'is_active' => $inventoryRole === 'internal' ? false : ($isDraft ? false : $request->boolean('is_active')),

            // special pricing window
            'special_starts_at' => $validated['special_starts_at'] ?? null,
            'special_ends_at' => $validated['special_ends_at'] ?? null,
        ];

        return $this->filterProductPayloadForSchema($payload);
    }

    protected function inferPackTypeFromProductInput(array $validated): string
    {
        $sellUnit = (string) ($validated['sell_unit'] ?? 'piece');
        $piecesPerPack = $this->toDecimal($validated['pieces_per_pack'] ?? null);
        $weight = $this->toDecimal($validated['product_weight'] ?? null);

        if ($piecesPerPack !== null && $piecesPerPack > 0) {
            return 'fixed_piece_pack';
        }

        if ($sellUnit === 'kg') {
            return 'variable_weight';
        }

        if ($sellUnit === 'pack' && $weight !== null && $weight > 0) {
            return 'fixed_weight_pack';
        }

        return $sellUnit === 'kg' ? 'bulk' : 'quantity';
    }

    protected function filterProductPayloadForSchema(array $payload): array
    {
        if (! Schema::hasTable('products')) {
            return $payload;
        }

        return array_filter(
            $payload,
            fn ($value, string $column): bool => Schema::hasColumn('products', $column),
            ARRAY_FILTER_USE_BOTH
        );
    }


    public function barcodeLookup(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $code = trim((string) $request->query('barcode', ''));

        if ($code === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please scan or enter a barcode / SKU.',
            ], 422);
        }

        $variant = ProductVariant::query()
            ->with('product')
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->first();

        if ($variant && $variant->product) {
            return response()->json([
                'ok' => true,
                'product' => $this->barcodeProductPayload($variant->product),
                'variant' => [
                    'id' => (int) $variant->id,
                    'name' => (string) ($variant->name ?: $variant->sku ?: 'Variant #' . $variant->id),
                    'sku' => (string) ($variant->sku ?? ''),
                    'barcode' => (string) ($variant->barcode ?? ''),
                    'is_active' => (bool) $variant->is_active,
                ],
            ]);
        }

        $product = Product::query()
            ->where(function ($query) use ($code) {
                $query->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'ok' => false,
                'message' => 'No product or variant found for this barcode / SKU.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'product' => $this->barcodeProductPayload($product),
            'variant' => null,
        ]);
    }

    protected function barcodeProductPayload(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'barcode' => (string) ($product->barcode ?? ''),
            'is_active' => (bool) $product->is_active,
            'hsn_code_id' => $product->hsn_code_id ? (int) $product->hsn_code_id : null,
        ];
    }

    protected function syncRelations(Product $product, ProductRequest $request): void
    {
        $validated = $request->validated();

        $categoryIds = collect($validated['category_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $attributeValueIds = collect($validated['attribute_value_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (method_exists($product, 'categories')) {
            $product->categories()->sync($categoryIds);
        }

        if (method_exists($product, 'attributeValues')) {
            $product->attributeValues()->sync($attributeValueIds);
        }
    }

    protected function resolveSlug(?string $rawSlug, string $name, Product $product): string
    {
        $slug = filled($rawSlug)
            ? Str::slug($rawSlug)
            : Str::slug($name);

        if ($slug === '') {
            $slug = 'product';
        }

        $base = $slug;
        $i = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($product->exists, fn ($q) => $q->whereKeyNot($product->getKey()))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    protected function normalizeStoredPrice(?float $value, bool $priceIncludesGst, float $factor): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($priceIncludesGst && $factor > 0) {
            return round($value / $factor, 2);
        }

        return round($value, 2);
    }

    protected function toDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 4);
    }

    protected function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BCustomerProduct;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class B2BCustomerProductController extends Controller
{
    public function index(User $user)
    {
        $this->assertB2B($user);

        $rows = B2BCustomerProduct::with(['product', 'productVariant'])
            ->where('user_id', $user->id)
            ->orderByDesc('is_active')
            ->orderBy('product_id')
            ->orderByRaw('product_variant_id IS NULL DESC')
            ->orderBy('product_variant_id')
            ->paginate(20);

        $priceOverrides = $this->priceOverridesFor($user->id, $rows->getCollection());

        return view('admin.customers.b2b-products.index', [
            'user' => $user,
            'rows' => $rows,
            'priceOverrides' => $priceOverrides,
        ]);
    }

    public function create(User $user)
    {
        $this->assertB2B($user);

        $products = $this->productsWithVariants();

        return view('admin.customers.b2b-products.create', [
            'user' => $user,
            'products' => $products,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $this->assertB2B($user);

        $data = $request->validate([
            'assignment_target'  => ['required', 'string', 'max:40'],
            'min_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'price'              => ['nullable', 'numeric', 'min:0'],
            'is_active'          => ['sometimes', 'boolean'],
        ]);

        [$product, $variant] = $this->resolveAssignmentTarget($data['assignment_target']);

        $minOrderQty = (float) ($data['min_order_quantity'] ?? 1);
        $isActive = $request->boolean('is_active', true);

        $row = B2BCustomerProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->when($variant, fn ($q) => $q->where('product_variant_id', $variant->id), fn ($q) => $q->whereNull('product_variant_id'))
            ->first();

        if (! $row) {
            $row = new B2BCustomerProduct();
            $row->user_id = $user->id;
            $row->product_id = $product->id;
            $row->product_variant_id = $variant?->id;
            $row->created_by_id = $request->user()->id;
        }

        $row->min_order_quantity = $minOrderQty;
        $row->is_active = $isActive;
        $row->updated_by_id = $request->user()->id;
        $row->save();

        $this->upsertPriceIfProvided($request, $user, $product, $variant);

        return redirect()
            ->route('admin.customers.b2b-products.index', $user)
            ->with('status', $variant ? 'Variant option added to B2B catalog.' : 'Product added to B2B catalog.');
    }

    public function edit(User $user, B2BCustomerProduct $row)
    {
        $this->assertB2B($user);
        abort_unless($row->user_id === $user->id, 404);

        $row->load(['product', 'productVariant']);
        $priceOverride = $this->priceOverrideFor($user->id, (int) $row->product_id, $row->product_variant_id ? (int) $row->product_variant_id : null);

        return view('admin.customers.b2b-products.edit', [
            'user' => $user,
            'row'  => $row,
            'priceOverride' => $priceOverride,
        ]);
    }

    public function update(Request $request, User $user, B2BCustomerProduct $row)
    {
        $this->assertB2B($user);
        abort_unless($row->user_id === $user->id, 404);

        $data = $request->validate([
            'min_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'price'              => ['nullable', 'numeric', 'min:0'],
            'is_active'          => ['sometimes', 'boolean'],
        ]);

        $row->loadMissing(['product', 'productVariant']);
        $row->min_order_quantity = (float) ($data['min_order_quantity'] ?? 1);
        $row->is_active = $request->boolean('is_active', true);
        $row->updated_by_id = $request->user()->id;
        $row->save();

        if ($row->product) {
            $this->upsertPriceIfProvided($request, $user, $row->product, $row->productVariant);
        }

        return redirect()
            ->route('admin.customers.b2b-products.index', $user)
            ->with('status', 'B2B catalog updated.');
    }

    public function destroy(Request $request, User $user, B2BCustomerProduct $row)
    {
        $this->assertB2B($user);
        abort_unless($row->user_id === $user->id, 404);

        $row->delete();

        return redirect()
            ->route('admin.customers.b2b-products.index', $user)
            ->with('status', 'Product option removed from B2B catalog.');
    }

    protected function assertB2B(User $user): void
    {
        if (($user->customer_type ?? 'b2c') !== 'b2b') {
            abort(403, 'Customer is not marked as B2B.');
        }
    }

    protected function productsWithVariants()
    {
        return Product::query()
            ->with(['variants' => function ($q) {
                $q->where(function ($query) {
                    $query->where('is_active', true)->orWhereNull('is_active');
                })->orderBy('name')->orderBy('sku')->orderBy('id');
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
    }

    protected function resolveAssignmentTarget(string $target): array
    {
        [$type, $id] = array_pad(explode(':', $target, 2), 2, null);
        $id = (int) $id;

        if ($type === 'product' && $id > 0) {
            $product = Product::query()->where('is_active', true)->findOrFail($id);
            return [$product, null];
        }

        if ($type === 'variant' && $id > 0) {
            $variant = ProductVariant::query()
                ->with('product')
                ->where(function ($query) {
                    $query->where('is_active', true)->orWhereNull('is_active');
                })
                ->findOrFail($id);

            if (! $variant->product || ! (bool) ($variant->product->is_active ?? false)) {
                abort(422, 'Selected variant does not belong to an active product.');
            }

            return [$variant->product, $variant];
        }

        abort(422, 'Please select a valid product or variant.');
    }

    protected function upsertPriceIfProvided(Request $request, User $user, Product $product, ?ProductVariant $variant): void
    {
        if (! $request->has('price') || trim((string) $request->input('price')) === '') {
            return;
        }

        $price = $this->normalizeStoredB2BPrice($request->input('price'), $product);

        $row = CustomerProductPrice::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->when($variant, fn ($q) => $q->where('product_variant_id', $variant->id), fn ($q) => $q->whereNull('product_variant_id'))
            ->whereNull('valid_from')
            ->whereNull('valid_to')
            ->first();

        if (! $row) {
            $row = new CustomerProductPrice();
            $row->user_id = $user->id;
            $row->product_id = $product->id;
            $row->product_variant_id = $variant?->id;
            $row->currency = 'INR';
            $row->created_by_id = $request->user()?->id;
        }

        $row->price = $price;
        $row->is_active = true;
        $row->updated_by_id = $request->user()?->id;
        $row->save();
    }

    protected function priceOverrideFor(int $userId, int $productId, ?int $variantId): ?CustomerProductPrice
    {
        return CustomerProductPrice::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->when($variantId, fn ($q) => $q->where('product_variant_id', $variantId), fn ($q) => $q->whereNull('product_variant_id'))
            ->whereNull('valid_from')
            ->whereNull('valid_to')
            ->latest('id')
            ->first();
    }

    protected function priceOverridesFor(int $userId, Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $productIds = $rows->pluck('product_id')->filter()->unique()->values();

        return CustomerProductPrice::query()
            ->where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->whereNull('valid_from')
            ->whereNull('valid_to')
            ->get()
            ->keyBy(fn ($price) => $price->product_id . '|' . ((int) ($price->product_variant_id ?? 0)));
    }

    protected function normalizeStoredB2BPrice(mixed $value, Product $product): float
    {
        $price = (float) $value;
        $includesGst = (bool) ($product->b2b_price_includes_gst ?? false);
        $gstRate = (float) ($product->effective_gst_rate ?? $product->gst_rate ?? 0);
        $factor = 1 + ($gstRate / 100);

        if ($includesGst && $factor > 0) {
            return round($price / $factor, 2);
        }

        return round($price, 2);
    }
}

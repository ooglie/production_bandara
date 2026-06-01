<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BCustomerProduct;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class B2BCustomerProductController extends Controller
{
    public function index(User $user)
    {
        $this->assertB2B($user);

        $rows = B2BCustomerProduct::with(['product', 'sellUnit'])
            ->where('user_id', $user->id)
            ->orderByDesc('is_active')
            ->orderBy('product_id')
            ->orderByRaw('product_sell_unit_id IS NULL DESC')
            ->orderBy('product_sell_unit_id')
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

        $products = $this->productsWithSellUnits();

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

        [$product, $sellUnit] = $this->resolveAssignmentTarget($data['assignment_target']);

        $minOrderQty = (float) ($data['min_order_quantity'] ?? 1);
        $isActive = $request->boolean('is_active', true);

        $row = B2BCustomerProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->when($sellUnit, fn ($q) => $q->where('product_sell_unit_id', $sellUnit->id), fn ($q) => $q->whereNull('product_sell_unit_id'))
            ->first();

        if (! $row) {
            $row = new B2BCustomerProduct();
            $row->user_id = $user->id;
            $row->product_id = $product->id;
            $row->product_sell_unit_id = $sellUnit?->id;
            $row->created_by_id = $request->user()->id;
        }

        $row->min_order_quantity = $minOrderQty;
        $row->is_active = $isActive;
        $row->updated_by_id = $request->user()->id;
        $row->save();

        $this->upsertPriceIfProvided($request, $user, $product, $sellUnit);

        return redirect()
            ->route('admin.customers.b2b-products.index', $user)
            ->with('status', $sellUnit ? 'Sellable unit added to B2B catalog.' : 'Product added to B2B catalog.');
    }

    public function edit(User $user, B2BCustomerProduct $row)
    {
        $this->assertB2B($user);
        abort_unless($row->user_id === $user->id, 404);

        $row->load(['product', 'sellUnit']);
        $priceOverride = $this->priceOverrideFor($user->id, (int) $row->product_id, $row->product_sell_unit_id ? (int) $row->product_sell_unit_id : null);

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

        $row->loadMissing(['product', 'sellUnit']);
        $row->min_order_quantity = (float) ($data['min_order_quantity'] ?? 1);
        $row->is_active = $request->boolean('is_active', true);
        $row->updated_by_id = $request->user()->id;
        $row->save();

        if ($row->product) {
            $this->upsertPriceIfProvided($request, $user, $row->product, $row->sellUnit);
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

    protected function productsWithSellUnits()
    {
        return Product::query()
            ->with(['sellUnits' => function ($q) {
                $q->where('is_active', true)
                    ->where('is_b2b_visible', true)
                    ->orderBy('sort_order')
                    ->orderBy('name');
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

        if ($type === 'unit' && $id > 0) {
            $sellUnit = ProductSellUnit::query()
                ->with('product')
                ->where('is_active', true)
                ->where('is_b2b_visible', true)
                ->findOrFail($id);

            if (! $sellUnit->product || ! (bool) ($sellUnit->product->is_active ?? false)) {
                abort(422, 'Selected sellable unit does not belong to an active product.');
            }

            return [$sellUnit->product, $sellUnit];
        }

        abort(422, 'Please select a valid product or sellable unit.');
    }

    protected function upsertPriceIfProvided(Request $request, User $user, Product $product, ?ProductSellUnit $sellUnit): void
    {
        if (! $request->has('price') || trim((string) $request->input('price')) === '') {
            return;
        }

        $price = (float) $request->input('price');

        $row = CustomerProductPrice::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->when($sellUnit, fn ($q) => $q->where('product_sell_unit_id', $sellUnit->id), fn ($q) => $q->whereNull('product_sell_unit_id'))
            ->whereNull('valid_from')
            ->whereNull('valid_to')
            ->first();

        if (! $row) {
            $row = new CustomerProductPrice();
            $row->user_id = $user->id;
            $row->product_id = $product->id;
            $row->product_variant_id = null;
            $row->product_sell_unit_id = $sellUnit?->id;
            $row->currency = 'INR';
            $row->created_by_id = $request->user()?->id;
        }

        $row->price = $price;
        $row->is_active = true;
        $row->updated_by_id = $request->user()?->id;
        $row->save();
    }

    protected function priceOverrideFor(int $userId, int $productId, ?int $sellUnitId): ?CustomerProductPrice
    {
        return CustomerProductPrice::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->when($sellUnitId, fn ($q) => $q->where('product_sell_unit_id', $sellUnitId), fn ($q) => $q->whereNull('product_sell_unit_id'))
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
            ->whereNull('product_variant_id')
            ->whereNull('valid_from')
            ->whereNull('valid_to')
            ->get()
            ->keyBy(fn ($price) => $price->product_id . '|' . ((int) ($price->product_sell_unit_id ?? 0)));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductSellUnit;
use App\Models\User;
use Illuminate\Http\Request;

class B2BCustomerPriceController extends Controller
{
    protected function ensureB2B(User $user): void
    {
        if (($user->customer_type ?? 'b2c') !== 'b2b') {
            abort(404);
        }
    }

    public function index(Request $request, User $user)
    {
        $this->ensureB2B($user);

        $productId = $request->integer('product_id') ?: null;

        $prices = CustomerProductPrice::query()
            ->with(['product', 'productVariant', 'sellUnit'])
            ->where('user_id', $user->id)
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->orderByDesc('valid_from')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $products = Product::query()
            ->with(['sellUnits' => fn ($q) => $q->where('is_active', true)->where('is_b2b_visible', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('admin.b2b.prices.index', compact('user', 'prices', 'products', 'productId'));
    }

    public function create(User $user)
    {
        $this->ensureB2B($user);

        $products = Product::query()
            ->with(['sellUnits' => fn ($q) => $q->where('is_active', true)->where('is_b2b_visible', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('admin.b2b.prices.create', compact('user', 'products'));
    }

    public function store(Request $request, User $user)
    {
        $this->ensureB2B($user);

        $data = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'product_sell_unit_id' => ['nullable', 'integer', 'exists:product_sell_units,id'],
            'price'              => ['required', 'numeric', 'min:0'],
            'currency'           => ['nullable', 'string', 'max:3'],
            'valid_from'         => ['nullable', 'date'],
            'valid_to'           => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $variantId = !empty($data['product_variant_id']) ? (int) $data['product_variant_id'] : null;
        $sellUnitId = !empty($data['product_sell_unit_id']) ? (int) $data['product_sell_unit_id'] : null;


        if ($sellUnitId && $variantId) {
            return back()
                ->withErrors(['product_sell_unit_id' => 'Choose either a sellable unit price or a variant price, not both.'])
                ->withInput();
        }

        // Ensure variant/sell unit belongs to product
        if ($sellUnitId) {
            $sellUnit = ProductSellUnit::query()->findOrFail($sellUnitId);
            if ((int) $sellUnit->product_id !== (int) $data['product_id']) {
                return back()
                    ->withErrors(['product_sell_unit_id' => 'Selected sellable unit does not belong to the selected product.'])
                    ->withInput();
            }
        }

        if ($variantId) {
            $variant = ProductVariant::query()->findOrFail($variantId);
            if ((int) $variant->product_id !== (int) $data['product_id']) {
                return back()
                    ->withErrors(['product_variant_id' => 'Selected variant does not belong to the selected product.'])
                    ->withInput();
            }
        }

        $productForPrice = Product::query()->findOrFail((int) $data['product_id']);
        $storedPrice = $this->normalizeStoredB2BPrice($data['price'], $productForPrice);

        // Prevent accidental duplicates:
        // If same user/product/variant + same validity window exists, update it.
        $existing = CustomerProductPrice::query()
            ->where('user_id', $user->id)
            ->where('product_id', (int) $data['product_id'])
            ->when($sellUnitId, fn($q) => $q->where('product_sell_unit_id', $sellUnitId), fn($q) => $q->whereNull('product_sell_unit_id'))
            ->when($variantId, fn($q) => $q->where('product_variant_id', $variantId), fn($q) => $q->whereNull('product_variant_id'))
            ->where(function ($q) use ($data) {
                if (!empty($data['valid_from'])) {
                    $q->whereDate('valid_from', '=', $data['valid_from']);
                } else {
                    $q->whereNull('valid_from');
                }
            })
            ->where(function ($q) use ($data) {
                if (!empty($data['valid_to'])) {
                    $q->whereDate('valid_to', '=', $data['valid_to']);
                } else {
                    $q->whereNull('valid_to');
                }
            })
            ->first();

        if ($existing) {
            $existing->price = $storedPrice;
            $existing->currency = $data['currency'] ?? ($existing->currency ?? 'INR');
            $existing->is_active = $request->boolean('is_active', true);
            $existing->updated_by_id = $request->user()?->id;
            $existing->save();

            return redirect()
                ->route('admin.b2b.prices.index', $user)
                ->with('status', 'Price override updated.');
        }

        $row = new CustomerProductPrice();
        $row->user_id = $user->id;
        $row->product_id = (int) $data['product_id'];
        $row->product_variant_id = $variantId;
        $row->product_sell_unit_id = $sellUnitId;
        $row->price = $storedPrice;
        $row->currency = $data['currency'] ?? 'INR';
        $row->valid_from = $data['valid_from'] ?? null;
        $row->valid_to = $data['valid_to'] ?? null;
        $row->is_active = $request->boolean('is_active', true);
        $row->created_by_id = $request->user()?->id;
        $row->updated_by_id = $request->user()?->id;
        $row->save();

        return redirect()
            ->route('admin.b2b.prices.index', $user)
            ->with('status', 'Price override created.');
    }

    public function edit(User $user, CustomerProductPrice $price)
    {
        $this->ensureB2B($user);

        if ((int) $price->user_id !== (int) $user->id) {
            abort(404);
        }

        $products = Product::query()
            ->with(['sellUnits' => fn ($q) => $q->where('is_active', true)->where('is_b2b_visible', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return view('admin.b2b.prices.edit', compact('user', 'price', 'products'));
    }

    public function update(Request $request, User $user, CustomerProductPrice $price)
    {
        $this->ensureB2B($user);

        if ((int) $price->user_id !== (int) $user->id) {
            abort(404);
        }

        $data = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'product_sell_unit_id' => ['nullable', 'integer', 'exists:product_sell_units,id'],
            'price'              => ['required', 'numeric', 'min:0'],
            'currency'           => ['nullable', 'string', 'max:3'],
            'valid_from'         => ['nullable', 'date'],
            'valid_to'           => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active'          => ['nullable', 'boolean'],
        ]);

        $variantId = !empty($data['product_variant_id']) ? (int) $data['product_variant_id'] : null;
        $sellUnitId = !empty($data['product_sell_unit_id']) ? (int) $data['product_sell_unit_id'] : null;

        if ($sellUnitId && $variantId) {
            return back()
                ->withErrors(['product_sell_unit_id' => 'Choose either a sellable unit price or a variant price, not both.'])
                ->withInput();
        }

        if ($sellUnitId) {
            $sellUnit = ProductSellUnit::query()->findOrFail($sellUnitId);
            if ((int) $sellUnit->product_id !== (int) $data['product_id']) {
                return back()
                    ->withErrors(['product_sell_unit_id' => 'Selected sellable unit does not belong to the selected product.'])
                    ->withInput();
            }
        }

        if ($variantId) {
            $variant = ProductVariant::query()->findOrFail($variantId);
            if ((int) $variant->product_id !== (int) $data['product_id']) {
                return back()
                    ->withErrors(['product_variant_id' => 'Selected variant does not belong to the selected product.'])
                    ->withInput();
            }
        }

        $productForPrice = Product::query()->findOrFail((int) $data['product_id']);
        $storedPrice = $this->normalizeStoredB2BPrice($data['price'], $productForPrice);

        $price->product_id = (int) $data['product_id'];
        $price->product_variant_id = $variantId;
        $price->product_sell_unit_id = $sellUnitId;
        $price->price = $storedPrice;
        $price->currency = $data['currency'] ?? ($price->currency ?? 'INR');
        $price->valid_from = $data['valid_from'] ?? null;
        $price->valid_to = $data['valid_to'] ?? null;
        $price->is_active = $request->boolean('is_active', true);
        $price->updated_by_id = $request->user()?->id;
        $price->save();

        return redirect()
            ->route('admin.b2b.prices.index', $user)
            ->with('status', 'Price override saved.');
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

    public function destroy(User $user, CustomerProductPrice $price)
    {
        $this->ensureB2B($user);

        if ((int) $price->user_id !== (int) $user->id) {
            abort(404);
        }

        $price->delete();

        return back()->with('status', 'Price override deleted.');
    }
}

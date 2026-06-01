<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BCustomerProduct;
use App\Models\B2BProductRequest;
use App\Models\CustomerProductPrice;
use App\Models\ProductSellUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class B2BProductRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->input('status', 'pending');
        $allowedStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'all'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $query = B2BProductRequest::query()
            ->with(['user', 'product', 'productSellUnit', 'productVariant', 'resolvedBy'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(25)->withQueryString();

        return view('admin.b2b.product-requests.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function approve(Request $request, B2BProductRequest $productRequest)
    {
        $data = $request->validate([
            'min_order_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_scope' => ['nullable', Rule::in(['product', 'sell_unit', 'variant'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $productRequest->user;
        $product = $productRequest->product;

        if (! $user || ! $product) {
            return back()->withErrors(['request' => 'Customer or product no longer exists.']);
        }

        if (($user->customer_type ?? 'b2c') !== 'b2b') {
            return back()->withErrors(['request' => 'This request is not linked to a B2B customer.']);
        }

        $sellUnitId = $this->validatedSellUnitId($productRequest);

        $assignment = B2BCustomerProduct::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_sell_unit_id' => $sellUnitId,
        ]);

        $assignment->min_order_quantity = (float) ($data['min_order_quantity'] ?? $assignment->min_order_quantity ?? 1);
        $assignment->is_active = true;

        if (! $assignment->exists) {
            $assignment->created_by_id = $request->user()?->id;
        }

        $assignment->updated_by_id = $request->user()?->id;
        $assignment->save();

        if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
            $scope = $data['price_scope'] ?? ($sellUnitId ? 'sell_unit' : 'product');
            $variantId = null;
            $priceSellUnitId = null;

            if ($scope === 'variant' && $productRequest->product_variant_id) {
                $variantId = (int) $productRequest->product_variant_id;
            } elseif ($scope === 'sell_unit' && $sellUnitId) {
                $priceSellUnitId = $sellUnitId;
            }

            $price = CustomerProductPrice::query()->firstOrNew([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_sell_unit_id' => $priceSellUnitId,
                'product_variant_id' => $variantId,
            ]);

            $price->price = (float) $data['price'];
            $price->currency = $price->currency ?: 'INR';
            $price->is_active = true;

            if (! $price->exists) {
                $price->created_by_id = $request->user()?->id;
            }

            $price->updated_by_id = $request->user()?->id;
            $price->save();
        }

        $productRequest->status = 'approved';
        $productRequest->admin_note = $data['admin_note'] ?? $productRequest->admin_note;
        $productRequest->resolved_by_id = $request->user()?->id;
        $productRequest->resolved_at = now();
        $productRequest->save();

        return back()->with('status', 'Request approved and product/unit terms are available for the customer account.');
    }

    public function reject(Request $request, B2BProductRequest $productRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $productRequest->status = 'rejected';
        $productRequest->admin_note = $data['admin_note'] ?? $productRequest->admin_note;
        $productRequest->resolved_by_id = $request->user()?->id;
        $productRequest->resolved_at = now();
        $productRequest->save();

        return back()->with('status', 'Request rejected.');
    }

    protected function validatedSellUnitId(B2BProductRequest $productRequest): ?int
    {
        if (! $productRequest->product_sell_unit_id) {
            return null;
        }

        $sellUnit = ProductSellUnit::query()
            ->where('id', $productRequest->product_sell_unit_id)
            ->where('product_id', $productRequest->product_id)
            ->where('is_active', true)
            ->where('is_b2b_visible', true)
            ->first();

        return $sellUnit ? (int) $sellUnit->id : null;
    }
}

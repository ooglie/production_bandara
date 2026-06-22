<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2BCustomerProduct;
use App\Models\B2BProductRequest;
use App\Models\CustomerProductPrice;
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
            ->with(['user', 'product', 'productVariant', 'resolvedBy'])
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
            'price_scope' => ['nullable', Rule::in(['product', 'variant'])],
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

        $variantId = $this->validatedVariantId($productRequest);

        $assignment = B2BCustomerProduct::query()->firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
        ]);

        $assignment->min_order_quantity = (float) ($data['min_order_quantity'] ?? $assignment->min_order_quantity ?? 1);
        $assignment->is_active = true;

        if (! $assignment->exists) {
            $assignment->created_by_id = $request->user()?->id;
        }

        $assignment->updated_by_id = $request->user()?->id;
        $assignment->save();

        if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
            $scope = $data['price_scope'] ?? ($variantId ? 'variant' : 'product');
            $priceVariantId = ($scope === 'variant' && $variantId) ? $variantId : null;

            $price = CustomerProductPrice::query()->firstOrNew([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_variant_id' => $priceVariantId,
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

        return back()->with('status', 'Request approved and product/variant terms are available for the customer account.');
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

    protected function validatedVariantId(B2BProductRequest $productRequest): ?int
    {
        if (! $productRequest->product_variant_id) {
            return null;
        }

        $variant = $productRequest->productVariant;

        return $variant && (int) $variant->product_id === (int) $productRequest->product_id
            ? (int) $variant->id
            : null;
    }
}

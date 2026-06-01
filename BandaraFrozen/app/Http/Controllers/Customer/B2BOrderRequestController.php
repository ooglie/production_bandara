<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\B2BOrderRequest;
use App\Models\B2BOrderRequestItem;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Services\B2BTermsService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class B2BOrderRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->b2bUser($request);

        $status = (string) $request->input('status', 'open');
        $allowed = ['open', 'pending_allocation', 'reviewing', 'partially_allocated', 'allocated', 'finalized', 'cancelled', 'rejected', 'all'];

        if (! in_array($status, $allowed, true)) {
            $status = 'open';
        }

        $query = B2BOrderRequest::query()
            ->with(['finalizedOrder', 'finalizedInvoice', 'items.product.images', 'items.sellUnit', 'items.allocations'])
            ->where('user_id', $user->id)
            ->latest();

        if ($status === 'open') {
            $query->open();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('b2b.requests.index', [
            'user' => $user,
            'status' => $status,
            'requests' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, Product $product, B2BTermsService $terms, PricingService $pricing)
    {
        $user = $this->b2bUser($request);

        if (! (bool) ($product->is_active ?? false)) {
            abort(404);
        }

        $data = $request->validate([
            'product_sell_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('product_sell_units', 'id')->where(fn ($q) => $q->where('product_id', $product->id)),
            ],
            'request_mode' => ['required', Rule::in([B2BOrderRequestItem::MODE_PIECES, B2BOrderRequestItem::MODE_WEIGHT])],
            'requested_piece_count' => ['nullable', 'integer', 'min:1', 'max:9999', 'required_if:request_mode,pieces'],
            'requested_weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:99999', 'required_if:request_mode,weight'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sellUnit = null;
        if (! empty($data['product_sell_unit_id'])) {
            $sellUnit = ProductSellUnit::query()
                ->where('product_id', $product->id)
                ->where('id', (int) $data['product_sell_unit_id'])
                ->where('is_active', true)
                ->where('is_b2b_visible', true)
                ->first();

            if (! $sellUnit) {
                return back()->withErrors(['product_sell_unit_id' => 'This B2B buying option is not currently available.'])->withInput();
            }
        }

        if (! $terms->canBuy($user, $product, $sellUnit)) {
            return back()->withErrors(['product' => 'This product option is not assigned to your B2B portfolio.'])->withInput();
        }

        if (! $this->supportsRequestFlow($product, $sellUnit)) {
            return back()->withErrors(['product' => 'This product option can be ordered directly. Please use Add to B2B cart.'])->withInput();
        }

        $mode = (string) $data['request_mode'];
        $requestedPieces = $mode === B2BOrderRequestItem::MODE_PIECES ? (int) $data['requested_piece_count'] : null;
        $requestedWeight = $mode === B2BOrderRequestItem::MODE_WEIGHT ? round((float) $data['requested_weight_kg'], 3) : null;
        $tolerance = $requestedWeight !== null ? round(max($requestedWeight * 0.05, 0.05), 3) : null;
        $estimatedMin = $requestedWeight !== null ? max(round($requestedWeight - $tolerance, 3), 0) : null;
        $estimatedMax = $requestedWeight !== null ? round($requestedWeight + $tolerance, 3) : null;
        $unitPrice = $sellUnit
            ? (float) $pricing->priceForSellUnit($user, $product, $sellUnit)
            : (float) $pricing->priceFor($user, $product);

        $orderRequest = DB::transaction(function () use ($user, $product, $sellUnit, $mode, $requestedPieces, $requestedWeight, $tolerance, $estimatedMin, $estimatedMax, $unitPrice, $data) {
            $orderRequest = B2BOrderRequest::query()->create([
                'user_id' => $user->id,
                'status' => B2BOrderRequest::STATUS_PENDING_ALLOCATION,
                'customer_note' => $data['customer_note'] ?? null,
                'submitted_at' => now(),
            ]);

            $orderRequest->request_number = 'BOR-' . now()->format('Ymd') . '-' . str_pad((string) $orderRequest->id, 5, '0', STR_PAD_LEFT);
            $orderRequest->save();

            B2BOrderRequestItem::query()->create([
                'b2b_order_request_id' => $orderRequest->id,
                'product_id' => $product->id,
                'product_sell_unit_id' => $sellUnit?->id,
                'request_mode' => $mode,
                'requested_piece_count' => $requestedPieces,
                'requested_weight_kg' => $requestedWeight,
                'weight_tolerance_kg' => $tolerance,
                'quoted_unit_price' => $unitPrice > 0 ? $unitPrice : null,
                'pricing_unit' => $sellUnit?->pricing_unit ?: 'kg',
                'estimated_min_weight_kg' => $estimatedMin,
                'estimated_max_weight_kg' => $estimatedMax,
                'status' => B2BOrderRequest::STATUS_PENDING_ALLOCATION,
                'customer_note' => $data['customer_note'] ?? null,
            ]);

            return $orderRequest;
        });

        return redirect()
            ->route('b2b.requests.index')
            ->with('status', 'B2B order request ' . $orderRequest->request_number . ' submitted. Our team will allocate actual pieces/weight before invoicing.');
    }

    protected function supportsRequestFlow(Product $product, ?ProductSellUnit $sellUnit): bool
    {
        if ($sellUnit && in_array($sellUnit->unit_type, ['request_piece', 'request_weight'], true)) {
            return true;
        }

        if (! $sellUnit && $product->variants()->exists()) {
            return true;
        }

        return false;
    }

    protected function b2bUser(Request $request)
    {
        $user = $request->user();

        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            abort(403, 'This area is only available for B2B customers.');
        }

        return $user;
    }
}

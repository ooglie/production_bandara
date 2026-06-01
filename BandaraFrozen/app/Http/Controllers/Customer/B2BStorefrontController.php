<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\B2BCustomerProduct;
use App\Models\B2BProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\B2BPayLaterService;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class B2BStorefrontController extends Controller
{
    public function dashboard(Request $request, B2BPayLaterService $payLaterService)
    {
        $user = $this->b2bUser($request);

        $portfolioCount = B2BCustomerProduct::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->distinct('product_id')
            ->count('product_id');

        $pendingRequestCount = Schema::hasTable('b2b_product_requests')
            ? B2BProductRequest::query()->where('user_id', $user->id)->where('status', 'pending')->count()
            : 0;

        $pendingOrderRequestCount = Schema::hasTable('invoices')
            ? \App\Models\Invoice::query()
                ->where('requires_weight_finalization', true)
                ->whereHas('order', fn ($q) => $q->where('user_id', $user->id))
                ->count()
            : 0;

        $recentPortfolioProducts = Product::query()
            ->with(['images', 'sellUnits.variants'])
            ->withCount('variants')
            ->where('is_active', true)
            ->whereHas('b2bAssignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
            ->latest('products.updated_at')
            ->limit(6)
            ->get();

        return view('b2b.dashboard', [
            'user' => $user,
            'portfolioCount' => $portfolioCount,
            'pendingRequestCount' => $pendingRequestCount,
            'pendingOrderRequestCount' => $pendingOrderRequestCount,
            'recentPortfolioProducts' => $recentPortfolioProducts,
            'payLaterSummary' => $payLaterService->summaryFor($user),
        ]);
    }

    public function portfolio(Request $request, PricingService $pricing)
    {
        $user = $this->b2bUser($request);

        $products = Product::query()
            ->with(['images', 'sellUnits.variants'])
            ->withCount('variants')
            ->where('is_active', true)
            ->whereHas('b2bAssignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
            ->orderBy('name')
            ->paginate(16)
            ->withQueryString();

        $assignments = $this->assignmentsFor($user->id, $products->getCollection()->pluck('id'));

        return view('b2b.portfolio', [
            'user' => $user,
            'products' => $products,
            'assignments' => $assignments,
            'pricing' => $pricing,
        ]);
    }

    public function catalog(Request $request, PricingService $pricing)
    {
        $user = $this->b2bUser($request);

        $categories = Category::query()
            ->when(Schema::hasColumn('categories', 'is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        $productsQuery = Product::query()
            ->with(['images', 'sellUnits.variants'])
            ->withCount('variants')
            ->where('is_active', true);

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $productsQuery->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like);
            });
        }

        $categoryId = (int) $request->input('category_id', 0);
        if ($categoryId > 0) {
            $productsQuery->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }

        $products = $productsQuery
            ->orderBy('name')
            ->paginate(16)
            ->withQueryString();

        $productIds = $products->getCollection()->pluck('id');

        return view('b2b.catalog.index', [
            'user' => $user,
            'products' => $products,
            'categories' => $categories,
            'assignments' => $this->assignmentsFor($user->id, $productIds),
            'requestsByProduct' => $this->latestRequestsFor($user->id, $productIds),
            'pricing' => $pricing,
        ]);
    }

    public function show(Request $request, Product $product, PricingService $pricing)
    {
        $user = $this->b2bUser($request);

        if (! (bool) ($product->is_active ?? false)) {
            abort(404);
        }

        $product->loadMissing([
            'images' => fn ($q) => $q->orderBy('position')->orderBy('id'),
            'activeRecipes',
        ]);
        $product->loadCount('variants');

        $assignments = B2BCustomerProduct::query()
            ->with(['sellUnit.variants'])
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->get();

        $assignment = $assignments->firstWhere('product_sell_unit_id', null) ?: $assignments->first();

        $latestRequest = Schema::hasTable('b2b_product_requests')
            ? B2BProductRequest::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->latest()
                ->first()
            : null;

        return view('b2b.catalog.show', [
            'user' => $user,
            'product' => $product,
            'assignment' => $assignment,
            'assignments' => $assignments,
            'latestRequest' => $latestRequest,
            'pricing' => $pricing,
        ]);
    }

    public function storeRequest(Request $request, Product $product)
    {
        $user = $this->b2bUser($request);

        if (! (bool) ($product->is_active ?? false)) {
            abort(404);
        }

        $assignments = B2BCustomerProduct::query()
            ->with(['sellUnit.variants'])
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->get();

        $assignment = $assignments->firstWhere('product_sell_unit_id', null) ?: $assignments->first();

        if ($assignment) {
            return back()->with('status', 'This product is already in your B2B portfolio.');
        }

        $data = $request->validate([
            'requested_quantity' => ['nullable', 'numeric', 'min:0.01'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $pendingRequest = B2BProductRequest::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return back()->with('status', 'Your request for this product is already pending review.');
        }

        B2BProductRequest::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'requested_quantity' => $data['requested_quantity'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('b2b.catalog.show', ['product' => $product->slug])
            ->with('status', 'Request submitted. Our team will review pricing, MOQ, and availability.');
    }

    protected function b2bUser(Request $request)
    {
        $user = $request->user();

        if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
            abort(403, 'This area is only available for B2B customers.');
        }

        return $user;
    }

    protected function assignmentsFor(int $userId, Collection $productIds): Collection
    {
        $ids = $productIds->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return B2BCustomerProduct::query()
            ->with(['sellUnit.variants'])
            ->where('user_id', $userId)
            ->whereIn('product_id', $ids->all())
            ->where('is_active', true)
            ->get()
            ->groupBy('product_id');
    }

    protected function latestRequestsFor(int $userId, Collection $productIds): Collection
    {
        if (! Schema::hasTable('b2b_product_requests')) {
            return collect();
        }

        $ids = $productIds->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return B2BProductRequest::query()
            ->where('user_id', $userId)
            ->whereIn('product_id', $ids->all())
            ->latest()
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductSellUnit;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request, CartService $cartService)
    {
        $cart = $cartService->currentCart(false);

        $items = collect();
        $subtotal = 0.0;

        $pricingUpdatedCount = 0;
        $coupon = null;
        $discount = 0.0;
        $totalAfterDiscount = 0.0;
        $couponNotice = null;

        if ($cart) {
            $pricingUpdatedCount = $cartService->syncPrices($cart);
            $pricingUpdatedCount += $this->reapplySelectedPiecePricing($cart);

            $items = CartItem::with(['product', 'productVariant'])
                ->where('cart_id', $cart->id)
                ->orderBy('id')
                ->get();

            $this->attachSelectedPieceMetaToItems($items);

            $subtotal = (float) $items->sum(fn ($item) => (float) ($item->total ?? 0));

            $isB2B = (($request->user()?->customer_type ?? 'b2c') === 'b2b');

            if ($isB2B && $cart->coupon_id) {
                $cart->coupon_id = null;
                $cart->save();
            }

            if (! $isB2B && $cart->coupon_id) {
                $couponNotice = $cartService->ensureCouponStillValid($cart, $subtotal, $request->user());
                $coupon = $cart->coupon_id ? Coupon::find($cart->coupon_id) : null;
                $discount = $cartService->calculateCouponDiscount($coupon, $subtotal);
            }

            $totalAfterDiscount = max($subtotal - $discount, 0);
        }

        return view('customer.cart.index', [
            'cart'                => $cart,
            'items'               => $items,
            'subtotal'            => $subtotal,
            'pricingUpdatedCount' => $pricingUpdatedCount,
            'coupon'              => $coupon,
            'discount'            => $discount,
            'totalAfterDiscount'  => $totalAfterDiscount,
            'couponNotice'        => $couponNotice,
        ]);
    }

    public function add(Request $request, CartService $cartService)
    {
        $data = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'product_sell_unit_id' => ['nullable', 'integer', 'exists:product_sell_units,id'],
            'quantity'           => ['nullable', 'numeric', 'min:0.01'],
            'inventory_piece_id' => ['nullable', 'integer', 'exists:inventory_pieces,id'],
            'piece_weight_kg'    => ['nullable', 'numeric', 'min:0.001'],
        ]);

        $productId = (int) $data['product_id'];
        $variantId = !empty($data['product_variant_id']) ? (int) $data['product_variant_id'] : null;
        $sellUnitId = !empty($data['product_sell_unit_id']) ? (int) $data['product_sell_unit_id'] : null;

        try {
            /** @var Product|null $product */
            $product = Product::find($productId);
            if (! $product) {
                throw ValidationException::withMessages([
                    'product_id' => 'Product not found.',
                ]);
            }

            $user = $request->user();
            $isB2B = $user && (($user->customer_type ?? 'b2c') === 'b2b');

            $requestedSellUnit = null;
            if ($isB2B && $sellUnitId) {
                $requestedSellUnit = ProductSellUnit::query()
                    ->where('id', $sellUnitId)
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->where('is_b2b_visible', true)
                    ->first();

                if (! $requestedSellUnit) {
                    throw ValidationException::withMessages([
                        'product_sell_unit_id' => 'Selected B2B buying option is not available for this product.',
                    ]);
                }

                if (! $variantId) {
                    $linkedVariant = $this->singleLinkedVariantForSellUnit($requestedSellUnit);
                    if ($linkedVariant) {
                        $variantId = (int) $linkedVariant->id;
                    } elseif ($this->productHasVariants($product)) {
                        throw ValidationException::withMessages([
                            'product_sell_unit_id' => 'This B2B buying option is not linked to a single orderable variant yet. Please contact the team.',
                        ]);
                    }
                }
            }

            if ($isB2B && (! empty($data['inventory_piece_id']) || ! empty($data['piece_weight_kg']))) {
                throw ValidationException::withMessages([
                    'product_id' => 'Please choose an orderable B2B pack/unit option for this product.',
                ]);
            }

            if ($isB2B && $this->productHasVariants($product) && ! $variantId) {
                throw ValidationException::withMessages([
                    'product_id' => 'Please choose a B2B option before adding this product to cart.',
                ]);
            }

            $variant = null;
            if ($variantId) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->firstOrFail();
            }

            if ($isB2B && ! app(\App\Services\B2BTermsService::class)->canBuy($user, $product, $requestedSellUnit ?: $variant?->sellUnit, $variant)) {
                throw ValidationException::withMessages([
                    'product_id' => 'B2B price is not configured for this product option. Please contact the team.',
                ]);
            }

            $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));
            $isKg = ($sellUnit === 'kg');

            $pieceSelectable = ! $isB2B && $this->productRequiresPieceSelection($productId);

            // Legacy exact piece flow
            if (!empty($data['inventory_piece_id'])) {
                $selectedPiece = $this->findSelectablePiece($productId, (int) $data['inventory_piece_id'], $variantId);

                if (! $selectedPiece) {
                    throw ValidationException::withMessages([
                        'inventory_piece_id' => 'The selected slab is no longer available.',
                    ]);
                }

                $cart = $cartService->currentCart();
                if (! $cart) {
                    throw ValidationException::withMessages([
                        'cart' => 'Unable to create cart.',
                    ]);
                }

                if ($this->cartAlreadyHasPiece($cart->id, (int) $selectedPiece->id)) {
                    return back()->with('status', 'This slab is already in your cart.');
                }

                $this->createSelectedPieceCartItem($cart->id, $product, $variantId, $selectedPiece);

                $cart->touch();
                $this->reapplySelectedPiecePricing($cart);

                return back()->with('status', 'Selected slab added to cart.');
            }

            // Grouped weight flow for selectable pieces
            if (! $isB2B && $pieceSelectable) {
                $weightKg = isset($data['piece_weight_kg']) ? round((float) $data['piece_weight_kg'], 3) : 0.0;
                $qtyRequested = max(1, (int) round((float) ($data['quantity'] ?? 1)));

                if ($weightKg <= 0) {
                    throw ValidationException::withMessages([
                        'piece_weight_kg' => 'Please choose a slab size before adding to cart.',
                    ]);
                }

                $cart = $cartService->currentCart();
                if (! $cart) {
                    throw ValidationException::withMessages([
                        'cart' => 'Unable to create cart.',
                    ]);
                }

                $excludePieceIds = $this->currentCartPieceIds($cart->id);

                $selectedPieces = $this->findSelectablePiecesByWeight(
                    productId: $productId,
                    weightKg: $weightKg,
                    limit: $qtyRequested,
                    excludePieceIds: $excludePieceIds,
                    variantId: $variantId
                );

                if ($selectedPieces->count() < $qtyRequested) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Requested quantity is not available for the selected slab size.',
                    ]);
                }

                foreach ($selectedPieces as $selectedPiece) {
                    $this->createSelectedPieceCartItem($cart->id, $product, $variantId, $selectedPiece);
                }

                $cart->touch();
                $this->reapplySelectedPiecePricing($cart);

                return back()->with('status', $selectedPieces->count() . ' slab(s) added to cart.');
            }

            // Standard product / variant flow
            $qty = (float) ($data['quantity'] ?? 1);
            $qty = $this->normalizeQty($qty, $isKg);

            $itemWeight = $this->calculateStandardItemWeight($product, $variant, $qty);

            $cartService->addToCart($productId, $variantId, $qty, $itemWeight ?? 0.0);

            $cart = $cartService->currentCart(false);
            if ($cart) {
                $cartService->syncPrices($cart);
                $this->refreshStandardCartItemWeight($cartService, $productId, $variantId);
                $this->reapplySelectedPiecePricing($cart);
            }

            $limited = $this->clampItemToStockIfNeeded($cartService, $productId, $variantId);

            $cart = $cartService->currentCart(false);
            if ($cart) {
                $cartService->syncPrices($cart);
                $this->refreshStandardCartItemWeight($cartService, $productId, $variantId);
                $this->reapplySelectedPiecePricing($cart);
            }

            return back()->with('status', $limited
                ? 'Limited stock of this product available'
                : 'Product added to cart.'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'cart' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'cart' => 'Unable to add to cart. Please try again.',
            ]);
        }
    }

    public function store(Request $request, CartService $cartService)
    {
        return $this->add($request, $cartService);
    }

    public function update(Request $request, string $key, CartService $cartService)
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'piece_group_action' => ['nullable', 'in:inc,dec,remove_all'],
        ]);

        $cart = $cartService->currentCart(false);
        if (! $cart) {
            abort(404);
        }

        $item = CartItem::with(['product', 'productVariant'])
            ->where('id', $key)
            ->where('cart_id', $cart->id)
            ->firstOrFail();

        if ($this->isSelectedPieceItem($item)) {
            $action = (string) ($data['piece_group_action'] ?? '');

            if ($action === 'inc') {
                $weightKg = $this->selectedPieceWeightForItem($item);
                if ($weightKg <= 0) {
                    return back()->with('status', 'Unable to identify selected slab size.');
                }

                $excludePieceIds = $this->currentCartPieceIds($cart->id);
                $extraPiece = $this->findSelectablePiecesByWeight(
                    productId: (int) $item->product_id,
                    weightKg: $weightKg,
                    limit: 1,
                    excludePieceIds: $excludePieceIds
                )->first();

                if (! $extraPiece) {
                    return back()->with('status', 'No more slabs of this size are available.');
                }

                $this->createSelectedPieceCartItem($cart->id, $item->product, $item->product_variant_id, $extraPiece);
                $cart->touch();
                $this->reapplySelectedPiecePricing($cart);

                return back()->with('status', 'Added one more slab of the same size.');
            }

            if ($action === 'dec') {
                $this->removeSessionPieceMeta($item->id);
                $item->delete();

                $remaining = (int) CartItem::where('cart_id', $cart->id)->count();

                if ($remaining === 0) {
                    $cart->delete();
                    session()->forget('cart_id');
                    session()->forget('cart_piece_meta');

                    return redirect()
                        ->route($this->cartIndexRouteName($request))
                        ->with('status', 'Removed one slab. Your cart is now empty.');
                }

                $cart->touch();
                $this->reapplySelectedPiecePricing($cart);

                return back()->with('status', 'Removed one slab.');
            }

            if ($action === 'remove_all') {
                $groupItems = $this->selectedPieceGroupItems($cart->id, $item);

                foreach ($groupItems as $groupItem) {
                    $this->removeSessionPieceMeta($groupItem->id);
                    $groupItem->delete();
                }

                $remaining = (int) CartItem::where('cart_id', $cart->id)->count();

                if ($remaining === 0) {
                    $cart->delete();
                    session()->forget('cart_id');
                    session()->forget('cart_piece_meta');

                    return redirect()
                        ->route($this->cartIndexRouteName($request))
                        ->with('status', 'Selected slab group removed. Your cart is now empty.');
                }

                $cart->touch();
                $this->reapplySelectedPiecePricing($cart);

                return back()->with('status', 'Selected slab group removed.');
            }

            return back()->with('status', 'Selected slab quantity cannot be edited directly.');
        }

        $cartService->syncPrices($cart);
        $this->reapplySelectedPiecePricing($cart);

        $item->refresh();
        $item->loadMissing(['product', 'productVariant']);

        $qty = (float) $data['quantity'];
        $price = (float) ($item->unit_price ?? 0);

        $sellUnit = strtolower((string) ($item->product?->sell_unit ?? 'piece'));
        $isKg = $sellUnit === 'kg';

        $qty = $this->normalizeQty($qty, $isKg);

        $user = $request->user();
        $moqNotice = null;

        if ($user && (($user->customer_type ?? 'b2c') === 'b2b') && $item->product) {
            $min = (float) app(\App\Services\B2BTermsService::class)->minOrderQty($user, $item->product, $item->productVariant?->sellUnit, $item->productVariant);

            if ($min <= 0) {
                $min = $isKg ? 0.01 : 1;
            }

            $min = $this->normalizeQty($min, $isKg);

            if ($qty < $min) {
                $qty = $min;
                $minLabel = $isKg
                    ? rtrim(rtrim(number_format($min, 2), '0'), '.')
                    : (string) (int) $min;

                $moqNotice = "MOQ applied: minimum {$minLabel} for this product.";
            }
        }

        $limitedNotice = null;
        [$maxQty, $hasMax] = $this->stockMaxForItem($item, $isKg);

        if ($hasMax && $maxQty !== null && $maxQty >= 0 && $qty > ($maxQty + 1e-9)) {
            $qty = $maxQty;
            $limitedNotice = 'Limited stock of this product available';
        }

        $item->quantity = $qty;

        $lineWeight = $this->calculateStandardItemWeight($item->product, $item->productVariant, $qty);
        $lineTotal = $this->calculateStandardLineTotal($item->product, $item->productVariant, $qty, $price, $lineWeight);

        $item->item_weight = $lineWeight;
        $item->total = $lineTotal;
        $item->save();

        $cart->touch();

        $this->reapplySelectedPiecePricing($cart);

        $subtotal = $cartService->subtotal($cart);
        $couponNotice = $cartService->ensureCouponStillValid($cart, $subtotal, $user);

        $msg = 'Cart updated.';
        if ($limitedNotice) $msg .= ' ' . $limitedNotice . '.';
        if ($moqNotice) $msg .= ' ' . $moqNotice;
        if ($couponNotice) $msg .= ' ' . $couponNotice;

        return back()->with('status', $msg);
    }

    public function destroy(Request $request, string $key, CartService $cartService)
    {
        $cart = $cartService->currentCart(false);
        if (! $cart) {
            abort(404);
        }

        $item = CartItem::where('id', $key)
            ->where('cart_id', $cart->id)
            ->firstOrFail();

        $this->removeSessionPieceMeta($item->id);

        $item->delete();

        $remaining = (int) CartItem::where('cart_id', $cart->id)->count();

        if ($remaining === 0) {
            $cart->delete();
            session()->forget('cart_id');
            session()->forget('cart_piece_meta');

            return redirect()
                ->route($this->cartIndexRouteName($request))
                ->with('status', 'Item removed. Your cart is now empty.');
        }

        $cart->touch();

        $subtotal = $cartService->subtotal($cart);
        $couponNotice = $cartService->ensureCouponStillValid($cart, $subtotal, $request->user());

        $msg = 'Item removed from cart.';
        if ($couponNotice) $msg .= ' ' . $couponNotice;

        return redirect()
            ->route($this->cartIndexRouteName($request))
            ->with('status', $msg);
    }


    private function cartIndexRouteName(Request $request): string
    {
        return 'cart.index';
    }


    private function singleLinkedVariantForSellUnit(ProductSellUnit $sellUnit): ?ProductVariant
    {
        $variants = ProductVariant::query()
            ->where('product_sell_unit_id', $sellUnit->id)
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('id')
            ->limit(2)
            ->get();

        return $variants->count() === 1 ? $variants->first() : null;
    }

    private function productHasVariants(Product $product): bool
    {
        if (($product->type ?? 'simple') !== 'simple') {
            return true;
        }

        return ProductVariant::query()
            ->where('product_id', $product->id)
            ->exists();
    }

    private function resolveB2BAutoVariant(Product $product): ?ProductVariant
    {
        return ProductVariant::query()
            ->where('product_id', $product->id)
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereNull('is_active');
            })
            ->where(function ($query) {
                $query->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0);
            })
            ->orderByRaw('CASE WHEN stock_quantity IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->first();
    }

    public function applyCoupon(Request $request, CartService $cartService)
    {
        $data = $request->validate([
            'coupon_code' => ['required', 'string', 'max:191'],
        ]);

        $cart = $cartService->currentCart(false);

        if (! $cart) {
            return back()->withErrors([
                'coupon_code' => 'Your cart is empty.',
            ]);
        }

        $cartService->syncPrices($cart);
        $this->reapplySelectedPiecePricing($cart);

        $subtotal = $cartService->subtotal($cart);

        if ($subtotal <= 0) {
            return back()->withErrors([
                'coupon_code' => 'Your cart is empty.',
            ]);
        }

        $result = $cartService->applyCouponCode($cart, $data['coupon_code'], $subtotal, $request->user());

        if (! $result['ok']) {
            return back()
                ->withErrors(['coupon_code' => $result['message']])
                ->withInput();
        }

        return back()->with('status', 'Coupon applied.');
    }

    public function removeCoupon(Request $request, CartService $cartService)
    {
        $cart = $cartService->currentCart(false);

        if ($cart) {
            $cartService->removeCoupon($cart);
            $this->reapplySelectedPiecePricing($cart);
        }

        return back()->with('status', 'Coupon removed.');
    }

    private function normalizeQty(float $qty, bool $isKg): float
    {
        if ($isKg) {
            $qty = max($qty, 0.01);
            return round($qty, 2);
        }

        $qty = (int) round($qty);
        if ($qty < 1) $qty = 1;

        return (float) $qty;
    }

    private function stockMaxForItem(CartItem $item, bool $isKg): array
    {
        $product = $item->product;
        $variant = $item->productVariant;

        $manageStock = false;
        $available = null;

        if ($variant && (bool) ($variant->manage_stock ?? false)) {
            $manageStock = true;
            $available = (float) ($variant->stock_quantity ?? 0);
        } elseif ($product && (bool) ($product->manage_stock ?? false)) {
            $manageStock = true;
            $available = (float) ($product->stock_quantity ?? 0);
        } elseif ($variant && $variant->stock_quantity !== null && (float) $variant->stock_quantity > 0) {
            $manageStock = true;
            $available = (float) $variant->stock_quantity;
        } elseif ($product && $product->stock_quantity !== null && (float) $product->stock_quantity > 0) {
            $manageStock = true;
            $available = (float) $product->stock_quantity;
        }

        if (! $manageStock) {
            return [null, false];
        }

        $available = max((float) $available, 0);

        $maxQty = $isKg
            ? round($available, 2)
            : (float) max((int) floor($available), 0);

        return [$maxQty, true];
    }

    private function clampItemToStockIfNeeded(CartService $cartService, int $productId, ?int $variantId): bool
    {
        $cart = $cartService->currentCart(false);
        if (! $cart) return false;

        $items = CartItem::with(['product', 'productVariant'])
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->when($variantId === null, fn ($q) => $q->whereNull('product_variant_id'))
            ->when($variantId !== null, fn ($q) => $q->where('product_variant_id', $variantId))
            ->orderBy('id')
            ->get();

        $item = $items->first(function ($row) {
            return ! $this->isSelectedPieceItem($row);
        });

        if (! $item) return false;

        $sellUnit = strtolower((string) ($item->product?->sell_unit ?? 'piece'));
        $isKg = $sellUnit === 'kg';

        [$maxQty, $hasMax] = $this->stockMaxForItem($item, $isKg);

        if ($hasMax && $maxQty !== null && $maxQty >= 0) {
            $current = (float) $item->quantity;

            if ($current > ($maxQty + 1e-9)) {
                $item->quantity = $maxQty;

                $lineWeight = $this->calculateStandardItemWeight($item->product, $item->productVariant, $maxQty);
                $lineTotal = $this->calculateStandardLineTotal(
                    $item->product,
                    $item->productVariant,
                    $maxQty,
                    (float) $item->unit_price,
                    $lineWeight
                );

                $item->item_weight = $lineWeight;
                $item->total = $lineTotal;
                $item->save();

                return true;
            }
        }

        return false;
    }

    private function productRequiresPieceSelection(int $productId): bool
    {
        return InventoryPiece::query()
            ->join('inventory_lots', 'inventory_lots.id', '=', 'inventory_pieces.inventory_lot_id')
            ->where('inventory_lots.product_id', $productId)
            ->where('inventory_lots.is_saleable', true)
            ->where('inventory_lots.lot_status', 'available')
            ->where('inventory_lots.inward_mode', 'pieces')
            ->where(function ($q) {
                $q->whereNull('inventory_lots.available_piece_count')
                  ->orWhere('inventory_lots.available_piece_count', '>', 0);
            })
            ->where('inventory_pieces.status', 'available')
            ->exists();
    }

    private function findSelectablePiece(int $productId, int $pieceId, ?int $variantId = null): ?InventoryPiece
    {
        return InventoryPiece::query()
            ->with('inventoryLot')
            ->where('id', $pieceId)
            ->where('status', 'available')
            ->whereHas('inventoryLot', function ($q) use ($productId, $variantId) {
                $q->where('product_id', $productId)
                  ->when($variantId, fn ($sub) => $sub->where('product_variant_id', $variantId))
                  ->where('is_saleable', true)
                  ->where('lot_status', 'available')
                  ->where('inward_mode', 'pieces');
            })
            ->first();
    }

    private function findSelectablePiecesByWeight(int $productId, float $weightKg, int $limit, array $excludePieceIds = [], ?int $variantId = null)
    {
        return InventoryPiece::query()
            ->with('inventoryLot')
            ->where('status', 'available')
            ->when(!empty($excludePieceIds), fn ($q) => $q->whereNotIn('id', $excludePieceIds))
            ->whereBetween('weight_kg', [$weightKg - 0.0005, $weightKg + 0.0005])
            ->whereHas('inventoryLot', function ($q) use ($productId, $variantId) {
                $q->where('product_id', $productId)
                  ->when($variantId, fn ($sub) => $sub->where('product_variant_id', $variantId))
                  ->where('is_saleable', true)
                  ->where('lot_status', 'available')
                  ->where('inward_mode', 'pieces');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function findSelectablePiecesForVariant(int $productId, int $variantId, int $limit, array $excludePieceIds = [])
    {
        return InventoryPiece::query()
            ->with('inventoryLot')
            ->where('status', 'available')
            ->when(!empty($excludePieceIds), fn ($q) => $q->whereNotIn('id', $excludePieceIds))
            ->whereHas('inventoryLot', function ($q) use ($productId, $variantId) {
                $q->where('product_id', $productId)
                  ->where('product_variant_id', $variantId)
                  ->where('is_saleable', true)
                  ->where('lot_status', 'available')
                  ->where('inward_mode', 'pieces');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    private function currentCartPieceIds(int $cartId): array
    {
        $items = CartItem::where('cart_id', $cartId)->get();

        $ids = [];
        foreach ($items as $item) {
            $pieceId = $this->pieceIdForItem($item);
            if ($pieceId) {
                $ids[] = $pieceId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function cartAlreadyHasPiece(int $cartId, int $pieceId): bool
    {
        return in_array($pieceId, $this->currentCartPieceIds($cartId), true);
    }

    private function requestUser(): ?\App\Models\User
    {
        return request()->user();
    }

    private function createSelectedPieceCartItem(int $cartId, Product $product, ?int $variantId, InventoryPiece $selectedPiece, ?float $overrideUnitPrice = null): void
    {
        $selectedPiece->loadMissing('inventoryLot');

        $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));
        $isKg = ($sellUnit === 'kg');

        $weightKg = round((float) $selectedPiece->weight_kg, 3);
        $resolvedVariantId = $this->resolveSelectedPieceVariantId($product, $variantId, $selectedPiece);
        $resolvedVariant = $resolvedVariantId ? ProductVariant::query()->find($resolvedVariantId) : null;
        $unitPrice = $overrideUnitPrice !== null
            ? (float) $overrideUnitPrice
            : (float) app(\App\Services\PricingService::class)->cartUnitPriceFor($this->requestUser(), $product, $resolvedVariant, $resolvedVariant?->sellUnit);

        $item = new CartItem();
        $item->cart_id = $cartId;
        $item->product_id = $product->id;
        $item->product_variant_id = $resolvedVariantId;

        $item->quantity = 1;
        $item->item_weight = $weightKg;
        $item->unit_price = $unitPrice;
        $item->total = $isKg
            ? round($unitPrice * $weightKg, 2)
            : round($unitPrice, 2);

        if ($this->hasCartItemColumn('selected_piece_id')) {
            $item->selected_piece_id = (int) $selectedPiece->id;
        }

        if ($this->hasCartItemColumn('selected_lot_id')) {
            $item->selected_lot_id = (int) $selectedPiece->inventory_lot_id;
        }

        $item->save();

        $this->storeSessionPieceMeta($item->id, [
            'piece_id' => (int) $selectedPiece->id,
            'lot_id' => (int) $selectedPiece->inventory_lot_id,
            'variant_id' => $resolvedVariantId,
            'weight_kg' => $weightKg,
            'weight_label' => $this->formatWeightLabel($weightKg),
            'lot_code' => (string) ($selectedPiece->inventoryLot->lot_code ?? ('LOT-' . $selectedPiece->inventory_lot_id)),
        ]);
    }

    private function resolveSelectedPieceVariantId(Product $product, ?int $variantId, InventoryPiece $selectedPiece): ?int
    {
        $lotVariantId = (int) ($selectedPiece->inventoryLot?->product_variant_id ?? 0);

        if ($lotVariantId > 0) {
            $belongsToProduct = ProductVariant::query()
                ->where('id', $lotVariantId)
                ->where('product_id', $product->id)
                ->exists();

            if ($belongsToProduct) {
                return $lotVariantId;
            }
        }

        return $variantId ?: null;
    }

    private function reapplySelectedPiecePricing($cart): int
    {
        if (! $cart) return 0;

        $items = CartItem::with(['product', 'productVariant'])
            ->where('cart_id', $cart->id)
            ->get();

        if ($items->isEmpty()) return 0;

        $pieceIds = [];

        foreach ($items as $item) {
            $pieceId = $this->pieceIdForItem($item);
            if ($pieceId) {
                $pieceIds[] = $pieceId;
            }
        }

        $piecesById = InventoryPiece::query()
            ->with('inventoryLot')
            ->whereIn('id', array_unique($pieceIds))
            ->get()
            ->keyBy('id');

        $updated = 0;

        foreach ($items as $item) {
            $pieceId = $this->pieceIdForItem($item);
            if (! $pieceId) continue;

            $sessionMeta = $this->getSessionPieceMetaForItemId($item->id);
            $piece = $piecesById->get($pieceId);

            $weightKg = $piece
                ? round((float) $piece->weight_kg, 3)
                : round((float) ($sessionMeta['weight_kg'] ?? 0), 3);

            if ($weightKg <= 0) {
                continue;
            }

            $sellUnit = strtolower((string) ($item->product?->sell_unit ?? 'piece'));
            $isKg = $sellUnit === 'kg';

            $unitPrice = $item->product
                ? (float) app(\App\Services\PricingService::class)->cartUnitPriceFor($this->requestUser(), $item->product, $item->productVariant, $item->productVariant?->sellUnit)
                : (float) ($item->unit_price ?? 0);
            $newTotal = $isKg
                ? round($unitPrice * $weightKg, 2)
                : round($unitPrice, 2);

            $newLotId = $piece?->inventory_lot_id ?: (int) ($sessionMeta['lot_id'] ?? 0);

            $dirty = false;

            if ((float) $item->quantity !== 1.0) {
                $item->quantity = 1;
                $dirty = true;
            }

            if ((float) ($item->item_weight ?? 0) !== $weightKg) {
                $item->item_weight = $weightKg;
                $dirty = true;
            }

            if ((float) ($item->unit_price ?? 0) !== $unitPrice) {
                $item->unit_price = $unitPrice;
                $dirty = true;
            }

            if ((float) ($item->total ?? 0) !== $newTotal) {
                $item->total = $newTotal;
                $dirty = true;
            }

            if ($this->hasCartItemColumn('selected_piece_id') && (int) ($item->selected_piece_id ?? 0) !== $pieceId) {
                $item->selected_piece_id = $pieceId;
                $dirty = true;
            }

            if ($this->hasCartItemColumn('selected_lot_id') && $newLotId > 0 && (int) ($item->selected_lot_id ?? 0) !== $newLotId) {
                $item->selected_lot_id = $newLotId;
                $dirty = true;
            }

            $pieceVariantId = (int) ($piece?->inventoryLot?->product_variant_id ?? ($sessionMeta['variant_id'] ?? 0));
            if ($pieceVariantId > 0 && empty($item->product_variant_id)) {
                $item->product_variant_id = $pieceVariantId;
                $dirty = true;
            }

            if ($dirty) {
                $item->save();
                $updated++;
            }

            $this->storeSessionPieceMeta($item->id, [
                'piece_id' => $pieceId,
                'lot_id' => $newLotId,
                'variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : (int) ($piece?->inventoryLot?->product_variant_id ?? 0),
                'weight_kg' => $weightKg,
                'weight_label' => $this->formatWeightLabel($weightKg),
                'lot_code' => $piece?->inventoryLot?->lot_code
                    ?: ($sessionMeta['lot_code'] ?? ($newLotId ? 'LOT-' . $newLotId : null)),
            ]);
        }

        if ($updated > 0) {
            $cart->touch();
        }

        return $updated;
    }

    private function attachSelectedPieceMetaToItems($items): void
    {
        $pieceIds = [];
        foreach ($items as $item) {
            $pieceId = $this->pieceIdForItem($item);
            if ($pieceId) {
                $pieceIds[] = $pieceId;
            }
        }

        $piecesById = InventoryPiece::query()
            ->with('inventoryLot')
            ->whereIn('id', array_unique($pieceIds))
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $pieceId = $this->pieceIdForItem($item);
            $meta = $this->getSessionPieceMetaForItemId($item->id);
            $piece = $pieceId ? $piecesById->get($pieceId) : null;

            $weightKg = $piece
                ? round((float) $piece->weight_kg, 3)
                : round((float) ($meta['weight_kg'] ?? 0), 3);

            if ($pieceId && $weightKg > 0) {
                $item->selected_piece_meta = [
                    'piece_id' => $pieceId,
                    'lot_id' => $piece?->inventory_lot_id ?: (int) ($meta['lot_id'] ?? 0),
                    'variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : (int) ($piece?->inventoryLot?->product_variant_id ?? ($meta['variant_id'] ?? 0)),
                    'weight_kg' => $weightKg,
                    'weight_label' => $meta['weight_label'] ?? $this->formatWeightLabel($weightKg),
                    'lot_code' => $piece?->inventoryLot?->lot_code
                        ?: ($meta['lot_code'] ?? null),
                ];
                $item->is_piece_selected = true;
            } else {
                $item->selected_piece_meta = null;
                $item->is_piece_selected = false;
            }
        }
    }

    private function isSelectedPieceItem(CartItem $item): bool
    {
        return $this->pieceIdForItem($item) !== null;
    }

    private function pieceIdForItem(CartItem $item): ?int
    {
        if ($this->hasCartItemColumn('selected_piece_id') && ! empty($item->selected_piece_id)) {
            return (int) $item->selected_piece_id;
        }

        $meta = $this->getSessionPieceMetaForItemId($item->id);
        if (! empty($meta['piece_id'])) {
            return (int) $meta['piece_id'];
        }

        return null;
    }

    private function selectedPieceWeightForItem(CartItem $item): float
    {
        $meta = $item->selected_piece_meta ?? $this->getSessionPieceMetaForItemId($item->id);
        return round((float) ($meta['weight_kg'] ?? 0), 3);
    }

    private function selectedPieceGroupItems(int $cartId, CartItem $anchor)
    {
        $targetWeight = $this->selectedPieceWeightForItem($anchor);

        return CartItem::with(['product', 'productVariant'])
            ->where('cart_id', $cartId)
            ->where('product_id', $anchor->product_id)
            ->when($anchor->product_variant_id === null, fn ($q) => $q->whereNull('product_variant_id'))
            ->when($anchor->product_variant_id !== null, fn ($q) => $q->where('product_variant_id', $anchor->product_variant_id))
            ->get()
            ->filter(function ($item) use ($targetWeight) {
                return $this->isSelectedPieceItem($item)
                    && abs($this->selectedPieceWeightForItem($item) - $targetWeight) < 0.0005;
            })
            ->values();
    }

    private function getSessionPieceMetaForItemId(int $itemId): array
    {
        $all = session('cart_piece_meta', []);
        return is_array($all[$itemId] ?? null) ? $all[$itemId] : [];
    }

    private function storeSessionPieceMeta(int $itemId, array $meta): void
    {
        $all = session('cart_piece_meta', []);
        if (! is_array($all)) {
            $all = [];
        }

        $all[$itemId] = $meta;
        session(['cart_piece_meta' => $all]);
    }

    private function removeSessionPieceMeta(int $itemId): void
    {
        $all = session('cart_piece_meta', []);
        if (! is_array($all)) {
            return;
        }

        unset($all[$itemId]);
        session(['cart_piece_meta' => $all]);
    }

    private function hasCartItemColumn(string $column): bool
    {
        static $cache = [];

        if (! array_key_exists($column, $cache)) {
            $cache[$column] = Schema::hasColumn('cart_items', $column);
        }

        return $cache[$column];
    }

    private function formatWeightLabel(float $kg): string
    {
        if ($kg < 2) {
            return round($kg * 1000) . ' g';
        }

        return rtrim(rtrim(number_format($kg, 3, '.', ''), '0'), '.') . ' kg';
    }

    private function resolveStandardUnitWeightKg(Product $product, ?ProductVariant $variant = null): float
    {
        $variantWeight = round((float) ($variant?->product_weight ?? 0), 3);
        if ($variantWeight > 0) {
            return $variantWeight;
        }

        return round((float) ($product->product_weight ?? 0), 3);
    }

    private function resolvePricingUnit(Product $product, ?ProductVariant $variant = null): string
    {
        $unit = strtolower((string) ($variant?->pricing_unit ?? ($product->sell_unit === 'kg' ? 'kg' : 'pack')));

        return in_array($unit, ['kg', 'pack'], true) ? $unit : 'pack';
    }

    private function calculateStandardItemWeight(Product $product, ?ProductVariant $variant, float $qty): ?float
    {
        $sellUnit = strtolower((string) ($product->sell_unit ?? 'piece'));

        if ($sellUnit === 'kg') {
            return round($qty, 3);
        }

        $unitWeightKg = $this->resolveStandardUnitWeightKg($product, $variant);

        return $unitWeightKg > 0
            ? round($qty * $unitWeightKg, 3)
            : null;
    }

    private function calculateStandardLineTotal(
        Product $product,
        ?ProductVariant $variant,
        float $qty,
        float $unitPrice,
        ?float $lineWeight = null
    ): float {
        $pricingUnit = $this->resolvePricingUnit($product, $variant);

        if ($pricingUnit === 'kg') {
            $weight = $lineWeight ?? $this->calculateStandardItemWeight($product, $variant, $qty);
            $weight = (float) ($weight ?? 0);

            return round($weight * $unitPrice, 2);
        }

        return round($qty * $unitPrice, 2);
    }

    private function refreshStandardCartItemWeight(CartService $cartService, int $productId, ?int $variantId): void
    {
        $cart = $cartService->currentCart(false);
        if (! $cart) {
            return;
        }

        $items = CartItem::with(['product', 'productVariant'])
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->when($variantId === null, fn ($q) => $q->whereNull('product_variant_id'))
            ->when($variantId !== null, fn ($q) => $q->where('product_variant_id', $variantId))
            ->orderBy('id')
            ->get();

        $item = $items->first(function ($row) {
            return ! $this->isSelectedPieceItem($row);
        });

        if (! $item || ! $item->product) {
            return;
        }

        $qty = (float) ($item->quantity ?? 0);
        $newWeight = $this->calculateStandardItemWeight($item->product, $item->productVariant, $qty);
        $newTotal = $this->calculateStandardLineTotal(
            $item->product,
            $item->productVariant,
            $qty,
            (float) ($item->unit_price ?? 0),
            $newWeight
        );

        $currentWeight = $item->item_weight !== null ? round((float) $item->item_weight, 3) : null;
        $currentTotal = round((float) ($item->total ?? 0), 2);
        $targetWeight = $newWeight !== null ? round((float) $newWeight, 3) : null;

        if ($currentWeight !== $targetWeight || abs($currentTotal - $newTotal) > 0.0001) {
            $item->item_weight = $newWeight;
            $item->total = $newTotal;
            $item->save();
        }
    }
}
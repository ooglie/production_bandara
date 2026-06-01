# B2B ordering rework: cart-first + weight finalization

This patch removes the temporary B2B variable order request/allocation/finalization module and replaces it with a simpler flow:

- Assigned fixed sellable units (for example Dimsum 10pc/20pc/box) are normal B2B cart items.
- Assigned weight-based products are added to the B2B cart/order with final invoice amount pending.
- Staff enters actual supplied weight from an admin finalization screen.
- Unassigned products still use the existing portfolio access request flow.

## Removed obsolete request/allocation module

Deleted code/files:

- `app/Http/Controllers/Admin/B2BOrderRequestController.php`
- `app/Http/Controllers/Customer/B2BOrderRequestController.php`
- `app/Models/B2BOrderRequest.php`
- `app/Models/B2BOrderRequestItem.php`
- `app/Models/B2BOrderItemAllocation.php`
- `app/Services/B2BOrderRequestFinalizationService.php`
- `resources/views/admin/b2b/order-requests/*`
- `resources/views/b2b/requests/*`
- the old B2B order-request migrations

A cleanup migration drops these tables if they already exist:

- `b2b_order_item_allocations`
- `b2b_order_request_items`
- `b2b_order_requests`

## New order flow

### Fixed pack/unit products

Example: `Dimsum 20pc Pack`.

If assigned to a B2B customer and linked to an orderable variant, it behaves as a normal B2B cart item:

- B2B price applies
- MOQ applies
- B2B cart/checkout applies
- stock commits through the existing order inventory service
- repacked inventory pack consumption remains intact

### Weight-based products

Example: Pork belly by pieces or approximate kg.

The B2B product page now says `Add to B2B order`, not `Submit request`.

Customer can enter:

- requested pieces, or
- approximate kg

The item is added to the B2B cart as a pending-weight line. At checkout:

- an order is created
- invoice is marked as requiring weight finalization
- no Razorpay payment is started yet
- no stock is committed yet

## New staff screen

Admin/Manager/Stores can use:

`Admin -> B2B Weight Finalization`

Routes:

- `admin.b2b.weight-finalization.index`
- `admin.b2b.weight-finalization.show`
- `admin.b2b.weight-finalization.finalize`

Staff enters actual piece count and actual kg. The system then:

- recalculates order items
- recalculates invoice items
- recalculates order/invoice totals and GST
- clears `requires_weight_finalization`
- for Pay Later orders, commits stock after finalization
- for Pay Now orders, leaves stock commitment to Razorpay success as before

## What this patch does not touch

- B2C shop/product/checkout UX
- Razorpay verification code
- Bandara Credit/rewards services
- vendor invoice inward
- inventory repack creation
- product access request workflow for unassigned B2B products

## Apply

```bash
git apply b2b_ordering_rework_cart_weight_finalization.patch
php artisan migrate
php artisan route:list
php artisan view:cache
php artisan optimize:clear
```

## Test checklist

1. Assign Dimsum 20pc Pack to a B2B customer.
2. Confirm it can be added to the B2B cart normally.
3. Place order and confirm stock/pack consumption still happens normally.
4. Assign a weight-based product to a B2B customer.
5. Add it to B2B cart by pieces or approx kg.
6. Checkout and confirm order is created with invoice requiring weight finalization.
7. Open Admin -> B2B Weight Finalization.
8. Enter actual pieces and actual kg.
9. Confirm order/invoice totals update.
10. Confirm Pay Later stock commits only after finalization; Pay Now still commits after payment success.

# Stock double-decrement fix

## Bug

Customer checkout was decrementing product/variant stock immediately after creating the order. Later, after Razorpay payment verification, `PaymentController` called `OrderInventoryService::commitPaidOrder()`, which decremented stock again.

Example before fix:

```text
Initial stock: 10
Customer buys qty 1
CheckoutController deducts: 10 -> 9
Payment callback deducts: 9 -> 8
Final stock: 8   ❌ expected 9
```

## Fix

`CheckoutController` no longer decrements stock during order placement.

Stock is now committed only through:

```php
OrderInventoryService::commitPaidOrder($order)
```

from the Razorpay success callback.

That service is already idempotent because it records a `stock_movements` row with:

```text
movement_type = sale
reference_type = order_item
reference_id = order_items.id
```

If the payment callback is repeated, the existing movement prevents a second stock deduction.

## Files changed

```text
app/Http/Controllers/Customer/CheckoutController.php
```

## Validation done

```text
php -l app/Http/Controllers/Customer/CheckoutController.php
```

Passed.

## Deployment note

After applying this fix, new orders should reduce stock once, after successful payment.

For any orders that were created before this fix but not yet paid, review them manually before payment completion if possible, because the old checkout flow may already have reduced stock once without creating a `stock_movements` idempotency record.

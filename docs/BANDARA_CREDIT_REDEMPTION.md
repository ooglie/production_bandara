# Bandara Credit checkout redemption

This module implements checkout redemption with reserve/post/release ledger accounting.

## Recommended flags

Enable earning + redemption only after migrations and wallet reconciliation pass:

```env
BANDARA_CREDIT_ENABLED=true
BANDARA_CREDIT_SHADOW_MODE=false
BANDARA_CREDIT_EARN_ENABLED=true
BANDARA_CREDIT_REDEEM_ENABLED=true
BANDARA_CREDIT_AUTO_POST_ENABLED=true
BANDARA_CREDIT_REDEEM_MINIMUM_POINTS=500
BANDARA_CREDIT_REDEEM_MAX_ORDER_PERCENTAGE=20
BANDARA_CREDIT_REDEEM_MINIMUM_PAYABLE_AMOUNT=1
BANDARA_CREDIT_RESERVATION_TTL_MINUTES=180
```

## Ledger flow

### Checkout submit

When a customer enters Bandara Credit points at checkout:

1. The controller previews the maximum allowed redemption.
2. The order is created with `bandara_credit_redeemed_points` and `bandara_credit_redeemed_amount`.
3. A `bandara_credit_transactions` row is created:
   - `type = redeem`
   - `amount = -points`
   - `status = reserved`
   - `idempotency_key = order:{order_id}:redeem`
4. Wallet balance is synced using posted credits and reserved debits, so reserved credits cannot be spent twice.

### Payment success

The Razorpay callback converts the reserved debit to posted:

- `type = redeem`
- `status = posted`

The wallet balance stays consistent because reserved debits already reduced availability.

### Payment failure

The Razorpay failed/invalid callback releases the reservation:

- `status = cancelled`
- wallet balance is restored by reconciliation/sync

### Order cancellation after payment

If an order with posted redemption is cancelled, the original posted redemption remains for audit history and a positive posted reversal is created:

- `type = redeem_reversal`
- `amount = +points`
- `status = posted`

### Abandoned checkout reservations

Run this on schedule:

```bash
php artisan bandara-credit:release-stale-reservations --minutes=180
```

The command releases stale unpaid reservations and marks pending payment orders as failed so a customer cannot later pay a discounted order after credits were restored.

Use dry run first:

```bash
php artisan bandara-credit:release-stale-reservations --minutes=180 --dry-run
```

## Deployment sequence

```bash
php artisan migrate
php artisan optimize:clear
php artisan bandara-credit:reconcile --all
php artisan bandara-credit:release-stale-reservations --dry-run
```

Then enable `BANDARA_CREDIT_REDEEM_ENABLED=true`.

## Notes

- Bandara Credit is treated as a payment adjustment, not a GST/tax discount.
- Coupon discount continues to reduce taxable value before GST.
- Bandara Credit is applied after GST/shipping to reduce the payable total.
- Full-credit checkout is intentionally prevented by `BANDARA_CREDIT_REDEEM_MINIMUM_PAYABLE_AMOUNT=1` because the current payment/inventory flow commits stock after Razorpay success.

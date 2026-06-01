# Bandara Credit production notes

## Scope implemented

Bandara Credit is now treated as a ledger-backed rewards program with one production engine:

```text
App\Services\BandaraCreditService
```

`BandaraCreditLedgerService` remains only as a compatibility wrapper so older call sites cannot bypass feature flags, shadow mode, configured earning rules, idempotency, cancellation reversal, or wallet reconciliation.

## Production behavior

| Order status | Reward behavior |
| --- | --- |
| `processing`, `shipped` | Queue component-level pending credits when auto-posting is enabled. |
| `delivered`, `completed` | Post eligible credits and sync wallet balance/tier. |
| `cancelled` | Cancel pending credits and reverse posted credits through negative ledger rows. |

Cancellation is corrective accounting. It can reverse already-posted credits even if earning, auto-posting, shadow mode, or the master program flag has later been switched off, so customer balances do not remain inflated after a cancelled order.

## Earning rule

Configured in `config/bandara_credit.php`:

```text
₹100 eligible spend = 1 Bandara Credit
```

Eligible spend maps to `orders.subtotal` by default. This keeps rewards based on merchandise subtotal, not `grand_total`.

## Bonuses

The production engine supports:

- base earned credit
- welcome bonus
- repeat order bonus
- tier preview/sync
- birthday-credit preview values by tier

Welcome credit is guarded against double-awards even when orders are delivered or manually posted out of chronological order.

Birthday credit posting is not scheduled yet; current behavior is preview/config only.

## Redemption

Redemption remains intentionally disabled by default:

```text
BANDARA_CREDIT_REDEEM_ENABLED=false
```

Checkout redemption should be implemented as a separate phase with reserve/apply/release accounting before this flag is enabled.

## Environment flags

Safe staging/shadow configuration:

```env
BANDARA_CREDIT_ENABLED=true
BANDARA_CREDIT_SHADOW_MODE=true
BANDARA_CREDIT_EARN_ENABLED=true
BANDARA_CREDIT_REDEEM_ENABLED=false
BANDARA_CREDIT_AUTO_POST_ENABLED=false
```

Live earn-only configuration:

```env
BANDARA_CREDIT_ENABLED=true
BANDARA_CREDIT_SHADOW_MODE=false
BANDARA_CREDIT_EARN_ENABLED=true
BANDARA_CREDIT_REDEEM_ENABLED=false
BANDARA_CREDIT_AUTO_POST_ENABLED=true
```

## Commands

Preview/post one successful order:

```bash
php artisan bandara-credit:post-earned {order_id} --dry-run
php artisan bandara-credit:post-earned {order_id}
```

Recalculate wallet balances and tiers from posted ledger rows:

```bash
php artisan bandara-credit:reconcile {user_id}
php artisan bandara-credit:reconcile --all
```

Run reconciliation after deploying this patch if legacy reward rows exist.

## Audit model

Wallet balance is derived from posted ledger rows:

```text
bandara_credit_wallets.balance = max(sum(posted transaction amounts), 0)
```

Posted order credits are kept as positive ledger rows. If an order is cancelled, the system inserts negative `earn_reversal` rows instead of mutating the original posted rows. This preserves audit history and prevents double-subtraction of unrelated credits.

## Validation performed in this handoff

- PHP syntax lint passed for modified PHP files.
- `php artisan route:list --json` passed with 253 routes.
- `php artisan list bandara-credit` shows both reward commands.
- Blade compile check passed for 182 Blade files.
- Targeted PHPUnit was attempted but could not run in this sandbox because PHP extensions `dom`, `mbstring`, `xml`, and `xmlwriter` are missing.

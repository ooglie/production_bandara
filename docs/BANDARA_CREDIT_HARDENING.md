# Bandara Credit production hardening

This pass keeps rewards B2C-only and adds operational safety around reporting, customer communication, partial order corrections, scheduled safeguards, and eligibility audit.

## Added features

- Admin rewards reports at `Admin → Rewards → Reports`.
- CSV export for reward liability and campaign performance.
- Monthly reward movement report.
- Campaign bonus report.
- B2C eligibility audit summary in admin and CLI.
- Customer-facing Bandara Credit rules page at `/account/rewards/terms`.
- Database notification helper for earn/redeem/release/restore/manual adjustment events.
- Order-linked reward correction flow for partial refunds/cancellations.
- CLI command for proportional order reward correction.
- Scheduler entries for stale reservation release, nightly reconciliation, and B2C eligibility audit.

## Partial refund / cancellation correction

Use the admin order page or Rewards Reports page for simple corrections.

Use CLI for proportional corrections:

```bash
php artisan bandara-credit:adjust-order ORDER_ID --refund-amount=4000 --dry-run
php artisan bandara-credit:adjust-order ORDER_ID --refund-amount=4000 --note="Partial refund for returned item"
```

Manual CLI examples:

```bash
php artisan bandara-credit:adjust-order ORDER_ID --wallet-delta=-40 --tier-delta=-40 --note="Partial item cancellation"
php artisan bandara-credit:adjust-order ORDER_ID --redeem-restore=40 --note="Restore redeemed credits for partial refund"
```

Corrections write offsetting ledger rows only. Existing ledger rows are not edited.

## B2C eligibility audit

```bash
php artisan bandara-credit:audit-eligibility
php artisan bandara-credit:audit-eligibility --json
php artisan bandara-credit:audit-eligibility --fail-on-issues
```

Rewards must stay limited to B2C customers. B2B users may have the Customer role, so eligibility must continue to check `users.customer_type = b2c`.

## Scheduler

Ensure server cron runs Laravel scheduler:

```bash
* * * * * cd /path/to/BandaraFrozen && php artisan schedule:run >> /dev/null 2>&1
```

Configured schedules:

```text
bandara-credit:release-stale-reservations    every 30 minutes
bandara-credit:reconcile --all               daily at 02:00
bandara-credit:audit-eligibility           daily at 02:30
```

## Customer communication

The customer rewards page now links to `/account/rewards/terms` and the service posts database notifications for important reward events when the notifications table exists.

## Reports

Admin report includes:

- credits issued
- credits redeemed
- earn reversals
- promo bonus issued
- tier points issued
- reserved redemptions
- pending earned credits
- outstanding wallet balance
- monthly movement
- campaign performance
- tier liability
- B2C eligibility audit


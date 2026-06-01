# Bandara Credit Rewards Module

## Scope

Rewards are B2C-only. B2B customers are excluded even if they use the `Customer` role.

Wallet accounting and tier qualification are separated:

- `bandara_credit_transactions.amount` = wallet effect.
- `bandara_credit_transactions.tier_points` = annual tier progress effect.

Redemption reduces wallet balance but does not reduce tier progress.

## Tier policy

Default tiers:

| Tier | Annual tier points | Reward rate |
| --- | ---: | ---: |
| Silver | 0-999 | 1% |
| Gold | 1000-3499 | 2% |
| Platinum | 3500+ | 4% |

Tier points are calculated from posted annual `tier_points` rows. Normal base earning and tier bonus count. Promo bonuses only count when the campaign has `counts_toward_tier` enabled. When a B2C customer reaches Gold or Platinum, the tier is retained for the qualifying year plus the full next calendar year.

## Campaign policy

Admin can manage campaigns from:

`Admin > Rewards > Campaigns`

Campaigns support:

- order-level promotions,
- product-level promotions,
- category-level promotions,
- fixed bonus promotions,
- minimum order amount,
- eligible tiers,
- max bonus per order,
- max bonus per customer,
- total campaign budget,
- pending/posted campaign budget protection,
- wallet-only promo bonus by default,
- optional tier accelerator using `counts_toward_tier`.

Default stacking rule is `best_wins`.

## Admin URLs

- `/admin/rewards` dashboard and preview.
- `/admin/rewards/tiers` tier management.
- `/admin/rewards/campaigns` campaign management.
- `/admin/rewards/customers` B2C rewards customers and manual adjustments.
- `/admin/rewards/ledger` ledger audit.

Requires permissions:

- `view rewards` to view dashboard/ledger/customers.
- `manage rewards` to edit tiers/campaigns/manual adjustments.

## Migration notes

The migration is additive and idempotent. It creates/updates:

- `bandara_credit_tiers`
- `bandara_credit_tier_histories`
- `bandara_credit_campaigns`
- `bandara_credit_campaign_products`
- `bandara_credit_campaign_categories`
- `bandara_credit_transactions.tier_points`
- `bandara_credit_transactions.campaign_id`
- `bandara_credit_transactions.expires_at`
- `bandara_credit_transactions.created_by_id`

No existing reward transactions or wallets are deleted.

After deployment:

```bash
php artisan migrate
php artisan optimize:clear
php artisan bandara-credit:reconcile --all
```

## Important

B2C eligibility is enforced in `BandaraCreditService::isEligibleUserForBandaraCredit()` using `users.customer_type = b2c` by default.

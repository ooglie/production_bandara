# Production Run Reversal

This update adds a controlled **full reversal** for completed Production / Repack runs.

## Behaviour

An Admin or Manager can open a completed Production Run and review:

- source stock that will be restored;
- output lots that will be cancelled;
- quantity, weight, and piece balances involved.

A mandatory reason and confirmation are required.

The reversal is performed in one database transaction. It:

1. restores consumed quantity, weight, and selected source pieces;
2. marks generated output lots as `cancelled` and sets their available balances to zero;
3. marks generated output pieces as `cancelled`;
4. reverses the corresponding product-level stock balances;
5. writes `production_run_reversal` stock movement audit rows;
6. marks the Production Run as `reversed`;
7. stores reversal timestamp, user, reason, and a JSON snapshot.

The original Production Run, inputs, outputs, lots, and pieces are not deleted.

## Reversal blockers

A run cannot be reversed when:

- it is not `completed`, or it was already reversed;
- an output lot quantity, weight, piece count, totals, or status changed;
- an output piece was sold, consumed, reserved, or modified;
- an output product/variant has an active checkout reservation;
- an output lot was used by another active Production Run;
- an output lot was used by Transform Stock / repack;
- legacy direct pack records are linked to the run;
- restoring the source would exceed the source lot's original balances;
- current product stock is insufficient to cancel the recorded output.

Downstream Production Runs should be reversed in reverse order. Once a downstream run is reversed, its cancelled child lots no longer block reversal of the upstream run.

## Access

- View Production Runs: unchanged (`Admin`, `Manager`, `Stores`).
- Reverse Production Runs: `Admin` and `Manager` only.

## Installation

Extract this package directly into the Laravel project root, preserving the `app/`, `database/`, `resources/`, `routes/`, and `tests/` directories.

Run:

```bash
php artisan migrate
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

Confirm the route:

```bash
php artisan route:list --name=production.reverse
```

Expected route name:

```text
admin.production.reverse
```

## Validation

The changed PHP files and migration were checked with `php -l`. Blade directive pairs were checked for balance. A Feature test is included at:

```text
tests/Feature/Services/ProductionRunReversalServiceTest.php
```

The supplied source archive does not include `vendor/`, so a full Laravel test run could not be executed in the review environment.

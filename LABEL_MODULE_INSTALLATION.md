# Product Label Module Installation

This package includes the single-label designer and variable-weight batch printing.

## Safe deployment

Do not overwrite the live website folder without a backup. Extract this ZIP into a separate folder, compare it with the current deployment, and preserve the live `.env` file and uploaded files under `storage/app/public`.

From the deployed application folder, run:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Ensure the normal Laravel writable folders remain writable by the web server:

- `bootstrap/cache`
- `storage`

## Using variable-weight labels

1. Set the product to sell by `kg` or set its pack type to `variable_weight`.
2. Store the retail price per kg on the product. The label screen pre-fills this price from the database.
3. Record each available item's measured weight as an inventory piece or inventory pack.
4. Open **Admin → Product Labels** and select **Batch by weight**.
5. Select the inventory items, or enter additional weights manually.
6. Preview or download the batch PDF. Each item becomes one 4 × 3 inch page.

The server recalculates every price when the PDF is generated. For example, at ₹1,100 per kg, weights of 3.5 kg, 4.2 kg, and 5.5 kg produce ₹3,850, ₹4,620, and ₹6,050 labels in one PDF.

Print the PDF at **100% / Actual size** with printer scaling disabled.

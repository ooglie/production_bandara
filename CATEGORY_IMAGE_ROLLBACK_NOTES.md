# Category image rollback

This removes the homepage/admin category image and generated collage feature.

The feature did not create a dedicated table. It added these columns to `categories`:

- `image_path`
- `collage_image_path`
- `collage_updated_at`

The included migration deletes referenced files inside `storage/app/public/category-images/` and `storage/app/public/category-collages/`, then drops those columns.

## Apply

From the Laravel project root:

```bash
bash remove_category_image_feature.sh
php artisan migrate
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```

The replacement `routes/web.php` retains the Transform Stock output-options route and Production Run reversal route.

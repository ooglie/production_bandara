Hotfix: Product Variant Option sync must write product_attribute_values.attribute_id.

Changed file:
app/Http/Controllers/Admin/ProductController.php

If you prefer not to overwrite files, apply:
patch -p1 < patches/product_variant_option_attribute_id_sync_fix.diff

Then run:
php artisan view:clear
php artisan optimize:clear

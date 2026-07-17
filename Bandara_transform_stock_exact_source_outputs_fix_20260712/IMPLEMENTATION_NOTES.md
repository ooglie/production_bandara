# Transform Stock exact source-output filter

Replace the included files at the same project paths.

This update changes the Transform Stock screen so each source lot has its own exact output product payload. The browser no longer uses a flat product catalogue shared across all source lots.

Behaviour:
- Selecting a source lot rebuilds every output row from only that lot's source product relationship.
- The source product itself remains available for same-product variant repacking, for example 240pc Dimsum to 10pc/20pc variants.
- Explicit Product Transformation targets for that source are also included.
- Products associated with other sources are not present in the selected lot's dropdown.
- When only one output product is associated, it is selected automatically.
- Existing server-side validation remains in place.

No migration is required.

After copying files:

```bash
php artisan view:clear
php artisan optimize:clear
```

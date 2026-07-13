# Bandara Transform Stock — multiple output variants

This is an incremental update for the previously installed source-carton repack fix.

## Files changed

- `app/Http/Controllers/Admin/InventoryPackController.php`
- `resources/views/admin/inventory/packs/create.blade.php`

No database migration is required.

## Behaviour

A single Transform Stock transaction can now create multiple output rows from one source lot/carton.

Example for one 240-piece master carton:

- Output row 1: 10-piece variant × 20 packs = 200 pieces
- Output row 2: 20-piece variant × 2 packs = 40 pieces
- Total source consumption: 240 pieces

The transaction is atomic: either all output variants and stock movements are saved, or none are saved.

## Carton balance rule

For one physical 240-piece carton, Vendor Invoice must receive:

- Stock Qty: `1`
- Source variant pieces per pack: `240`
- Source variant: `Can be used as source in Transform Stock = Yes`

When only 200 pieces are transformed:

- Unopened/full carton stock becomes `0` because the carton has been opened.
- Loose piece balance remains `40` in the source inventory lot.
- The same source lot remains available in Transform Stock for those 40 pieces.

When the remaining 40 pieces are later transformed, source product/variant stock is not deducted again because the carton was already removed from full-carton saleable stock when it was first opened.

## Existing incorrect inward record

If one physical carton was entered as `Stock Qty = 240`, the system correctly interprets that as 240 cartons, not one carton containing 240 pieces. Reverse/correct that inward record and receive it again as quantity `1`. Do not automatically edit only the product stock figure because the inventory lot and physical pack rows must remain consistent.

## Installation

Copy the two files into the matching project paths, then run:

```bash
php artisan optimize:clear
```

No migration command is needed for this incremental tweak.

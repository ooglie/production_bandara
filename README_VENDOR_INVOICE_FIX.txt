Vendor invoice simplified GST/MRP fix

Copy these files over the existing project files.

Changed behavior:
- The row UI is simplified into a Quantity & pricing box.
- "Unit cost already includes GST" is now a simple checkbox.
- When the checkbox is enabled, tax amount is forced to 0.00 and no extra GST is added/extracted.
- When the checkbox is disabled, tax amount is auto-calculated from the selected/product HSN and can still be manually edited.
- MRP incl. GST can be entered on the vendor invoice and updates product MRP using the same normalized storage approach as the product form.
- Invoice saving no longer requires new vendor_invoice_items columns. Optional audit columns are only written if they already exist.

No database migration is required for this corrected fix.
If you copied the previous migration file named:
database/migrations/2026_06_11_000001_add_gst_cost_and_mrp_fields_to_vendor_invoice_items.php
it is no longer required. Leaving it in place is harmless, but the corrected code does not depend on it.

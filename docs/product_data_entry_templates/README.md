# Bandara Product Data Entry Templates

These CSV templates are for planning and bulk preparation before production data entry.
They do not add import logic by themselves.

Files:

- `public/templates/bandara_product_import_template.csv`
- `public/templates/bandara_variant_import_template.csv`
- `public/templates/bandara_product_transformations_template.csv`

Recommended order:

1. Create categories and HSN codes.
2. Create products from the product template.
3. Create variant options and product variants for Variant Choice products.
4. Add product transformation mappings.
5. Add stock through Vendor Invoice / Transform Stock, not directly through the template unless you are intentionally opening with stock.

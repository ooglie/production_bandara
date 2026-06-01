SET @batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

INSERT INTO migrations (migration, batch)
SELECT '2026_01_15_001812_2026_01_13_000010_add_print_tracking_to_orders_table', @batch
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_01_15_001812_2026_01_13_000010_add_print_tracking_to_orders_table'
);

INSERT INTO migrations (migration, batch)
SELECT '2026_01_22_174920_2026_01_13_000010_add_print_tracking_to_orders_table', @batch
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_01_22_174920_2026_01_13_000010_add_print_tracking_to_orders_table'
);

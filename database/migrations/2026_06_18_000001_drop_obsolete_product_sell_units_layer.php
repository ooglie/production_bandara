<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // The destructive cleanup below intentionally uses MySQL metadata and
        // ALTER TABLE syntax. For SQLite test/fresh databases, keep the obsolete
        // compatibility layer in place and add only the replacement variant link
        // required by the current application. Production MySQL behaviour is unchanged.
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->prepareB2BCustomerProductsForVariantsPortable();

            return;
        }

        $this->prepareB2BCustomerProductsForVariants();

        foreach ([
            ['b2b_customer_products', 'product_sell_unit_id'],
            ['b2b_product_requests', 'product_sell_unit_id'],
            ['cart_items', 'product_sell_unit_id'],
            ['customer_product_prices', 'product_sell_unit_id'],
            ['inventory_lots', 'product_sell_unit_id'],
            ['inventory_packs', 'product_sell_unit_id'],
            ['invoice_items', 'product_sell_unit_id'],
            ['order_items', 'product_sell_unit_id'],
            ['product_variants', 'product_sell_unit_id'],
            ['stock_movements', 'product_sell_unit_id'],
            ['vendor_invoice_items', 'product_sell_unit_id'],
        ] as [$table, $column]) {
            $this->dropForeignKeysForColumn($table, $column);
            $this->dropIndexesForColumn($table, $column);
            $this->dropColumnIfExists($table, $column);
        }

        $this->dropForeignIfExists('product_sell_units', 'product_sell_units_product_id_foreign');
        Schema::dropIfExists('product_sell_units');
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_sell_units')) {
            Schema::create('product_sell_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('name');
                $table->string('sku', 120)->nullable()->index();
                $table->string('barcode', 120)->nullable()->index();
                $table->string('unit_type', 40)->default('pack')->index();
                $table->string('pricing_unit', 40)->default('pack');
                $table->string('sale_type', 40)->default('fixed_piece_pack')->index();
                $table->decimal('pieces_per_unit', 12, 3)->nullable();
                $table->decimal('weight_per_unit_kg', 12, 3)->nullable();
                $table->decimal('base_price', 12, 2)->nullable();
                $table->decimal('mrp_price', 12, 2)->nullable();
                $table->boolean('b2c_price_includes_gst')->default(true);
                $table->decimal('standard_b2b_price', 12, 2)->nullable();
                $table->decimal('standard_b2b_min_order_quantity', 12, 3)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_retail_visible')->default(true)->index();
                $table->boolean('is_b2b_visible')->default(true)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['product_id', 'is_active', 'sort_order']);
            });
        }

        foreach ([
            ['product_variants', 'product_variant_id'],
            ['vendor_invoice_items', 'product_variant_id'],
            ['cart_items', 'product_variant_id'],
            ['order_items', 'product_variant_id'],
            ['stock_movements', 'product_variant_id'],
            ['inventory_lots', 'product_variant_id'],
            ['inventory_packs', 'product_variant_id'],
            ['b2b_customer_products', 'product_id'],
            ['b2b_product_requests', 'product_id'],
            ['customer_product_prices', 'product_id'],
            ['invoice_items', 'order_item_id'],
        ] as [$table, $after]) {
            $this->addProductSellUnitColumnIfMissing($table, $after);
        }

        $this->dropForeignKeysForColumn('b2b_customer_products', 'product_variant_id');
        $this->dropIndexesForColumn('b2b_customer_products', 'product_variant_id');
        $this->dropColumnIfExists('b2b_customer_products', 'product_variant_id');
    }

    private function prepareB2BCustomerProductsForVariantsPortable(): void
    {
        if (! Schema::hasTable('b2b_customer_products')) {
            return;
        }

        if (! Schema::hasColumn('b2b_customer_products', 'product_variant_id')) {
            Schema::table('b2b_customer_products', function (Blueprint $table) {
                $table->unsignedBigInteger('product_variant_id')->nullable();
            });
        }
    }

    private function prepareB2BCustomerProductsForVariants(): void
    {
        if (! Schema::hasTable('b2b_customer_products')) {
            return;
        }

        $this->dropUniqueIndexesForColumns('b2b_customer_products', ['user_id', 'product_id']);

        if (! Schema::hasColumn('b2b_customer_products', 'product_variant_id')) {
            Schema::table('b2b_customer_products', function (Blueprint $table) {
                $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
            });
        }

        if (Schema::hasTable('product_variants')) {
            $this->addIndexIfMissing('b2b_customer_products', 'b2b_customer_products_product_variant_id_index', ['product_variant_id']);
            $this->addForeignIfMissing(
                'b2b_customer_products',
                'b2b_customer_products_product_variant_id_foreign',
                'product_variant_id',
                'product_variants',
                'id',
                'SET NULL'
            );
        }

        $this->addIndexIfMissing(
            'b2b_customer_products',
            'b2b_customer_product_variant_active_idx',
            ['user_id', 'product_id', 'product_variant_id', 'is_active']
        );
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->dropColumn($column);
        });
    }

    private function addProductSellUnitColumnIfMissing(string $table, string $after): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'product_sell_unit_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->unsignedBigInteger('product_sell_unit_id')->nullable()->index();
        });
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $schema = $this->databaseName();
        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->unique()
            ->values();

        foreach ($constraints as $constraint) {
            $this->dropForeignIfExists($table, (string) $constraint);
        }
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $this->databaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` DROP FOREIGN KEY `' . str_replace('`', '``', $constraint) . '`');
        }
    }

    private function dropIndexesForColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $indexes = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $this->databaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('INDEX_NAME', '<>', 'PRIMARY')
            ->pluck('INDEX_NAME')
            ->unique()
            ->values();

        foreach ($indexes as $index) {
            $this->dropIndexIfExists($table, (string) $index);
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $this->databaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();

        if ($exists) {
            DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` DROP INDEX `' . str_replace('`', '``', $index) . '`');
        }
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $this->databaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();

        if ($exists) {
            return;
        }

        $escapedColumns = collect($columns)
            ->filter(fn (string $column): bool => Schema::hasColumn($table, $column))
            ->map(fn (string $column): string => '`' . str_replace('`', '``', $column) . '`')
            ->implode(', ');

        if ($escapedColumns === '') {
            return;
        }

        DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD INDEX `' . str_replace('`', '``', $index) . '` (' . $escapedColumns . ')');
    }

    private function addForeignIfMissing(string $table, string $constraint, string $column, string $referenceTable, string $referenceColumn, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! Schema::hasTable($referenceTable)) {
            return;
        }

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $this->databaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`%s`) ON DELETE %s',
            str_replace('`', '``', $table),
            str_replace('`', '``', $constraint),
            str_replace('`', '``', $column),
            str_replace('`', '``', $referenceTable),
            str_replace('`', '``', $referenceColumn),
            $onDelete
        ));
    }

    private function dropUniqueIndexesForColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $rows = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $this->databaseName())
            ->where('TABLE_NAME', $table)
            ->where('NON_UNIQUE', 0)
            ->where('INDEX_NAME', '<>', 'PRIMARY')
            ->orderBy('INDEX_NAME')
            ->orderBy('SEQ_IN_INDEX')
            ->get(['INDEX_NAME', 'COLUMN_NAME']);

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[(string) $row->INDEX_NAME][] = (string) $row->COLUMN_NAME;
        }

        foreach ($indexes as $index => $indexedColumns) {
            if (array_values($indexedColumns) === array_values($columns)) {
                $this->dropIndexIfExists($table, $index);
            }
        }
    }

    private function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }
};

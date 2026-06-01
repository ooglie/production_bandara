<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('b2b_customer_products')) {
            $this->dropUniqueIndexIfExactly('b2b_customer_products', ['user_id', 'product_id']);

            Schema::table('b2b_customer_products', function (Blueprint $table) {
                if (! Schema::hasColumn('b2b_customer_products', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')
                        ->nullable()
                        ->after('product_id')
                        ->constrained('product_sell_units')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('customer_product_prices')) {
            $this->dropUniqueIndexIfExactly('customer_product_prices', ['user_id', 'product_id', 'product_variant_id']);

            Schema::table('customer_product_prices', function (Blueprint $table) {
                if (! Schema::hasColumn('customer_product_prices', 'product_sell_unit_id')) {
                    $table->foreignId('product_sell_unit_id')
                        ->nullable()
                        ->after('product_id')
                        ->constrained('product_sell_units')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_product_prices') && Schema::hasColumn('customer_product_prices', 'product_sell_unit_id')) {
            Schema::table('customer_product_prices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }

        if (Schema::hasTable('b2b_customer_products') && Schema::hasColumn('b2b_customer_products', 'product_sell_unit_id')) {
            Schema::table('b2b_customer_products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('product_sell_unit_id');
            });
        }
    }

    private function dropUniqueIndexIfExactly(string $table, array $columns): void
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");
        } catch (Throwable) {
            return;
        }

        $unique = [];
        foreach ($indexes as $index) {
            if ((int) ($index->Non_unique ?? 1) !== 0) {
                continue;
            }

            $name = (string) ($index->Key_name ?? '');
            if ($name === 'PRIMARY') {
                continue;
            }

            $unique[$name][(int) ($index->Seq_in_index ?? 0)] = (string) ($index->Column_name ?? '');
        }

        foreach ($unique as $name => $indexedColumns) {
            ksort($indexedColumns);
            if (array_values($indexedColumns) === array_values($columns)) {
                Schema::table($table, function (Blueprint $table) use ($name) {
                    $table->dropUnique($name);
                });
            }
        }
    }
};

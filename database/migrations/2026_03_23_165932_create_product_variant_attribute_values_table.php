<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE_NAME = 'product_variant_attribute_values';
    private const VALUE_FK_NAME = 'pvav_value_fk';

    public function up(): void
    {
        $valueTable = null;
        $valueForeignKey = null;

        if (Schema::hasTable('product_attribute_values')) {
            $valueTable = 'product_attribute_values';
            $valueForeignKey = 'product_attribute_value_id';
        } elseif (Schema::hasTable('attribute_values')) {
            $valueTable = 'attribute_values';
            $valueForeignKey = 'attribute_value_id';
        }

        if (! $valueTable || ! $valueForeignKey) {
            throw new RuntimeException(
                'Neither product_attribute_values nor attribute_values table exists. Create the attribute values table before this pivot table.'
            );
        }

        if (Schema::hasTable(self::TABLE_NAME)) {
            $this->addMissingValueForeignKey($valueTable, $valueForeignKey);

            return;
        }

        Schema::create(self::TABLE_NAME, function (Blueprint $table) use ($valueTable, $valueForeignKey) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->unsignedBigInteger($valueForeignKey);

            $table->timestamps();

            $table->unique(
                ['product_variant_id', $valueForeignKey],
                'pvav_variant_value_unique'
            );

            $table->foreign($valueForeignKey, self::VALUE_FK_NAME)
                ->references('id')
                ->on($valueTable)
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }

    private function addMissingValueForeignKey(string $valueTable, string $valueForeignKey): void
    {
        if (! Schema::hasColumn(self::TABLE_NAME, $valueForeignKey)) {
            return;
        }

        // These duplicate legacy migrations are repair passes. The first migration
        // already creates the FK on a fresh SQLite database, and SQLite cannot add
        // the same named foreign key to the existing table a second time.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        if ($this->hasForeignKey(self::TABLE_NAME, $valueForeignKey)) {
            return;
        }

        Schema::table(self::TABLE_NAME, function (Blueprint $table) use ($valueTable, $valueForeignKey) {
            $table->foreign($valueForeignKey, self::VALUE_FK_NAME)
                ->references('id')
                ->on($valueTable)
                ->cascadeOnDelete();
        });
    }

    private function hasForeignKey(string $tableName, string $columnName): bool
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $connection->getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $columnName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }
};

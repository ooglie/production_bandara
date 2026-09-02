<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSnapshots('orders');
        $this->addSnapshots('invoices');
    }

    public function down(): void
    {
        $this->dropSnapshots('invoices');
        $this->dropSnapshots('orders');
    }

    private function addSnapshots(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (! Schema::hasColumn($tableName, 'supplier_gstin')) {
                $table->string('supplier_gstin', 15)->nullable()->after('gst_type');
            }

            if (! Schema::hasColumn($tableName, 'supplier_gst_state_code')) {
                $table->char('supplier_gst_state_code', 2)->nullable()->after('supplier_gstin');
            }

            if (! Schema::hasColumn($tableName, 'bill_to_gstin')) {
                $table->string('bill_to_gstin', 15)->nullable()->after('supplier_gst_state_code');
            }

            if (! Schema::hasColumn($tableName, 'bill_to_gst_state_code')) {
                $table->char('bill_to_gst_state_code', 2)->nullable()->after('bill_to_gstin');
            }

            if (! Schema::hasColumn($tableName, 'ship_to_gst_state_code')) {
                $table->char('ship_to_gst_state_code', 2)->nullable()->after('bill_to_gst_state_code');
            }

            if (! Schema::hasColumn($tableName, 'place_of_supply_gst_state_code')) {
                $table->char('place_of_supply_gst_state_code', 2)->nullable()->after('ship_to_gst_state_code');
            }

            if (! Schema::hasColumn($tableName, 'gst_determination_basis')) {
                $table->string('gst_determination_basis', 32)->nullable()->after('place_of_supply_gst_state_code');
            }

            if (! Schema::hasColumn($tableName, 'is_bill_to_ship_to')) {
                $table->boolean('is_bill_to_ship_to')->default(false)->after('gst_determination_basis');
            }
        });
    }

    private function dropSnapshots(string $tableName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $columns = collect([
            'supplier_gstin',
            'supplier_gst_state_code',
            'bill_to_gstin',
            'bill_to_gst_state_code',
            'ship_to_gst_state_code',
            'place_of_supply_gst_state_code',
            'gst_determination_basis',
            'is_bill_to_ship_to',
        ])->filter(fn (string $column): bool => Schema::hasColumn($tableName, $column))->all();

        if ($columns !== []) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

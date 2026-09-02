<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GstPlaceOfSupplySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_and_invoices_have_immutable_gst_place_of_supply_snapshots(): void
    {
        foreach (['orders', 'invoices'] as $table) {
            foreach ([
                'supplier_gstin',
                'supplier_gst_state_code',
                'bill_to_gstin',
                'bill_to_gst_state_code',
                'ship_to_gst_state_code',
                'place_of_supply_gst_state_code',
                'gst_determination_basis',
                'is_bill_to_ship_to',
            ] as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Expected {$table}.{$column} to exist.",
                );
            }
        }
    }
}

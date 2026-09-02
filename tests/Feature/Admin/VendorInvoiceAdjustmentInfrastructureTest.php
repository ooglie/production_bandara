<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VendorInvoiceAdjustmentInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjustment_return_tables_routes_and_permission_exist(): void
    {
        foreach ([
            'vendor_invoice_adjustments',
            'vendor_invoice_adjustment_items',
            'vendor_returns',
            'vendor_return_items',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        foreach ([
            'admin.vendor-invoices.edit-details',
            'admin.vendor-invoices.update-details',
            'admin.vendor-invoices.adjustments.create',
            'admin.vendor-invoices.adjustments.store',
            'admin.vendor-invoices.adjustments.show',
            'admin.vendor-invoices.adjustments.post',
            'admin.vendor-invoices.adjustments.reverse',
            'admin.vendor-invoices.adjustments.destroy',
            'admin.vendor-invoices.returns.create',
            'admin.vendor-invoices.returns.store',
            'admin.vendor-invoices.returns.show',
            'admin.vendor-invoices.returns.post',
            'admin.vendor-invoices.returns.credit-note',
            'admin.vendor-invoices.returns.destroy',
            'admin.vendor-invoices.reverse.confirm',
            'admin.vendor-invoices.reverse.store',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route {$routeName}");
        }

        $this->assertTrue(DB::table('permissions')
            ->where('name', 'adjust vendor invoices')
            ->where('guard_name', 'web')
            ->exists());
    }
}

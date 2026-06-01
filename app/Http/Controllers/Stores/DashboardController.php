<?php

namespace App\Http\Controllers\Stores;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    public function index()
    {
        // Try common route names (adjust if yours differ)
        $vendorInvoicesIndex =
            Route::has('admin.vendor_invoices.index') ? route('admin.vendor-invoices.index')
            : (Route::has('admin.vendor-invoices.index') ? route('admin.vendor-invoices.index')
            : null);

        $inventoryLotsIndex =
            Route::has('admin.inventory.lots.index') ? route('admin.inventory.lots.index')
            : (Route::has('admin.inventory_lots.index') ? route('admin.inventory.lots.index')
            : null);

        $productionRunsIndex = $inventoryPacksIndex =
            Route::has('admin.production.index') ? route('admin.production.index')
            : (Route::has('admin.production.index') ? route('admin.production.index')
            : null);

        // $inventoryPacksIndex =
        //     Route::has('admin.inventory.packs.index') ? route('admin.inventory.packs.index')
        //     : (Route::has('admin.inventory_packs.index') ? route('admin.inventory-packs.index')
        //     : null);

        // Basic counters (won’t crash if tables exist, and yours do)
        $stats = [
            'vendor_invoices_pending' => DB::table('vendor_invoices')->where('status', 'pending')->count(),
            'lots_total'              => DB::table('inventory_lots')->count(),
            'packs_available'         => DB::table('inventory_packs')->where('status', 'available')->count(),
            'production_runs_total'   => DB::table('production_runs')->count(),
        ];

        return view('dashboard.stores', [
            'vendorInvoicesIndex' => $vendorInvoicesIndex,
            'inventoryLotsIndex'  => $inventoryLotsIndex,
            'productionRunsIndex' => $productionRunsIndex,
            'inventoryPacksIndex' => $inventoryPacksIndex,
            'stats'               => $stats,
        ]);
    }
}

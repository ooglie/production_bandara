<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class InventoryLotController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $lotsQuery = InventoryLot::query()->orderByDesc('id');

        if ($q !== '') {
            // search by batch_code or lot id
            $lotsQuery->where(function ($w) use ($q) {
                $w->where('batch_code', 'like', "%{$q}%")
                  ->orWhere('id', (int) $q);
            });
        }

        $lots = $lotsQuery->paginate(25);

        $productIds = $lots->pluck('product_id')->filter()->unique()->values()->all();
        $variantIds = $lots->pluck('product_variant_id')->filter()->unique()->values()->all();

        $productsById = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $variantsById = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        // For piece lots, compute quick stats
        $pieceStats = [];
        $pieceLotIds = $lots->where('inward_mode', 'pieces')->pluck('id')->values()->all();
        if (!empty($pieceLotIds)) {
            $rows = InventoryPiece::query()
                ->selectRaw('inventory_lot_id, COUNT(*) as cnt, SUM(weight_kg) as kg')
                ->whereIn('inventory_lot_id', $pieceLotIds)
                ->where('status', 'available')
                ->groupBy('inventory_lot_id')
                ->get();

            foreach ($rows as $r) {
                $pieceStats[(int)$r->inventory_lot_id] = [
                    'count' => (int) $r->cnt,
                    'kg'    => (float) $r->kg,
                ];
            }
        }

        return view('admin.inventory.lots.index', compact('lots', 'productsById', 'variantsById', 'pieceStats', 'q'));
    }

    public function show(InventoryLot $lot)
    {
        $product = $lot->product_id ? Product::find($lot->product_id) : null;
        $variant = $lot->product_variant_id ? ProductVariant::find($lot->product_variant_id) : null;

        $availablePieces = null;
        $availableKg = null;

        if (($lot->inward_mode ?? 'qty') === 'pieces') {
            $availablePieces = InventoryPiece::where('inventory_lot_id', $lot->id)
                ->where('status', 'available')
                ->count();

            $availableKg = (float) InventoryPiece::where('inventory_lot_id', $lot->id)
                ->where('status', 'available')
                ->sum('weight_kg');
        }

        $remaining = null;
        if (($lot->inward_mode ?? 'qty') === 'qty') {
            $remaining = max(((float)($lot->received_quantity ?? 0)) - ((float)($lot->consumed_quantity ?? 0)), 0);
        }

        return view('admin.inventory.lots.show', compact('lot', 'product', 'variant', 'availablePieces', 'availableKg', 'remaining'));
    }
}

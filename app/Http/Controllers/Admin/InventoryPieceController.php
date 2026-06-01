<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Models\InventoryPiece;
use Illuminate\Http\Request;

class InventoryPieceController extends Controller
{
    public function index(InventoryLot $lot)
    {
        $pieces = InventoryPiece::query()
            ->where('inventory_lot_id', $lot->id)
            ->orderBy('piece_no')
            ->paginate(50);

        return view('admin.inventory.lots.pieces', compact('lot', 'pieces'));
    }

    // AJAX options for production create (available pieces only)
    public function options(Request $request, InventoryLot $lot)
    {
        $pieces = InventoryPiece::query()
            ->where('inventory_lot_id', $lot->id)
            ->where('status', 'available')
            ->orderBy('piece_no')
            ->get()
            ->map(function ($p) {
                return [
                    'id'      => (int) $p->id,
                    'piece_no'=> (int) $p->piece_no,
                    'weight_kg' => (float) $p->weight_kg,
                    'label'   => 'Piece #' . $p->piece_no . ' — ' . number_format((float)$p->weight_kg, 3) . ' kg',
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'lot_id' => (int) $lot->id,
            'pieces' => $pieces,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\HsnCode;
use App\Models\InventoryLot;
use App\Models\InventoryPiece;


class VendorInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorInvoice::with(['vendor', 'payments'])
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('vendor', function ($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                      });
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        $invoices = $query->paginate(20)->withQueryString();
        $vendors  = Vendor::orderBy('name')->get();
        $statuses = ['pending', 'partially_paid', 'paid', 'cancelled'];

        return view('admin.vendor_invoices.index', compact('invoices', 'vendors', 'statuses'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get();

        // Vendor inward can receive stock for inactive draft products.
        // Keeping the product inactive still prevents storefront/cart sale until it is completed.
        $products = Product::query()
            ->select(['id', 'name', 'sku', 'barcode', 'type', 'hsn_code_id', 'is_active'])
            ->with(['variants' => function ($query) {
                $query->select(['id', 'product_id', 'sku', 'barcode', 'name', 'is_active'])
                    ->orderBy('sku')
                    ->orderBy('id');
            }])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $hsnCodes = HsnCode::orderBy('code')->get();

        $vendor = null;
        if ($request->filled('vendor_id')) {
            $vendor = $vendors->firstWhere('id', (int) $request->vendor_id);
        }

        return view('admin.vendor_invoices.create', compact('vendors', 'products', 'vendor', 'hsnCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id'      => ['required', 'exists:vendors,id'],
            'invoice_number' => ['required', 'string', 'max:50'],
            'invoice_date'   => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],
            'notes'          => ['nullable', 'string'],

            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity'           => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_cost'          => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount'         => ['nullable', 'numeric', 'min:0'],
            'items.*.hsn_code_id'        => ['nullable', 'exists:hsn_codes,id'],

            // inward fields
            'items.*.batch_code'    => ['nullable', 'string', 'max:80'],
            'items.*.mfg_date'      => ['nullable', 'date'],
            'items.*.packed_date'   => ['nullable', 'date'],
            'items.*.expiry_date'   => ['nullable', 'date'],
            'items.*.inward_mode'   => ['nullable', 'in:qty,pieces'],
            'items.*.piece_weights' => ['nullable', 'string', 'max:5000'],

            // new weight fields
            'items.*.unit_weight_kg'  => ['nullable', 'numeric', 'min:0'],
            'items.*.total_weight_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $vendorId  = (int) $validated['vendor_id'];
        $itemsData = $this->normalizeInvoiceItems($validated['items']);

        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($itemsData as $row) {
            $qty  = (float) ($row['_stock_quantity'] ?? $row['quantity'] ?? 0);
            $cost = (float) ($row['unit_cost'] ?? 0);
            $tax  = isset($row['tax_amount']) ? (float) $row['tax_amount'] : 0.0;

            $subtotal += ($qty * $cost);
            $taxTotal += $tax;
        }

        $totalAmount = $subtotal + $taxTotal;

        $invoice = null;

        DB::transaction(function () use ($validated, $vendorId, $itemsData, $subtotal, $taxTotal, $totalAmount, &$invoice, $request) {
            $invoice = VendorInvoice::create([
                'vendor_id'      => $vendorId,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date'   => $validated['invoice_date'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxTotal,
                'total_amount'   => $totalAmount,
                'status'         => 'pending',
                'due_date'       => $validated['due_date'] ?? null,
                'notes'          => $validated['notes'] ?? null,
            ]);

            foreach ($itemsData as $idx => $row) {
                $product = Product::findOrFail($row['product_id']);

                // HSN locking
                $submittedHsnId = $row['hsn_code_id'] ?? null;
                $finalHsnId = null;

                if (!empty($product->hsn_code_id)) {
                    $finalHsnId = (int) $product->hsn_code_id;

                    if (!empty($submittedHsnId) && (int) $submittedHsnId !== $finalHsnId) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.$idx.hsn_code_id" => "HSN is locked for {$product->name} and cannot be changed.",
                        ]);
                    }
                } else {
                    if (empty($submittedHsnId)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.$idx.hsn_code_id" => "Please select an HSN code for {$product->name}.",
                        ]);
                    }

                    $finalHsnId = (int) $submittedHsnId;
                    $hsn = HsnCode::findOrFail($finalHsnId);

                    $product->hsn_code_id = $hsn->id;

                    if (isset($product->gst_rate)) {
                        $product->gst_rate = (float) $hsn->gst_rate;
                    }

                    $product->save();
                }

                $inwardMode = $row['_inward_mode'] ?? ($row['inward_mode'] ?? 'qty');
                $receiptUnits = $this->receiptUnitsForRow($product, $row, $idx);
                $lineCounter = 0;

                foreach ($receiptUnits as $unit) {
                    $lineCounter++;

                    $variant = $unit['variant'] ?? null;

                    if (! $variant && ! empty($unit['auto_create_variant'])) {
                        $variant = $this->createAutoPieceVariant(
                            product: $product,
                            weightKg: (float) $unit['variant_weight_kg'],
                            invoiceId: (int) $invoice->id,
                            itemIndex: (int) $idx,
                            pieceIndex: (int) ($unit['piece_index'] ?? $lineCounter)
                        );
                    }

                    $variantId = $variant?->id;

                    // IMPORTANT: quantity/stock quantity is always the count of units/pieces.
                    // In pieces mode, each auto-created piece variant receives quantity 1.
                    // Total kg is stored separately in total_weight_kg/inventory_pieces.
                    $qty  = round((float) ($unit['quantity'] ?? 0), 3);
                    $cost = round((float) ($row['unit_cost'] ?? 0), 2);
                    $tax  = round((float) ($unit['tax_amount'] ?? 0), 2);

                    $unitWeight = $unit['unit_weight_kg'] ?? null;
                    $totalWeight = $unit['total_weight_kg'] ?? null;
                    $pieceWeights = $unit['piece_weights'] ?? [];
                    $unitInwardMode = $unit['inward_mode'] ?? $inwardMode;

                    $lineTotal = ($qty * $cost) + $tax;

                    // Save invoice item explicitly to avoid fillable issues
                    $item = new VendorInvoiceItem();
                    $item->vendor_invoice_id  = $invoice->id;
                    $item->product_id         = $product->id;
                    $item->product_variant_id = $variantId;
                    $item->quantity           = $qty;
                    $item->unit_weight_kg     = $unitWeight;
                    $item->total_weight_kg    = $totalWeight;
                    $item->unit_cost          = $cost;
                    $item->tax_amount         = $tax;
                    $item->total              = $lineTotal;
                    $item->save();

                    // Stock update. Variant stock is the source of truth for variant products;
                    // the parent product stock is kept as an aggregate count for admin/listing views.
                    if ($variant) {
                        $variant->stock_quantity = round((float) ($variant->stock_quantity ?? 0) + $qty, 3);
                        $variant->save();

                        $this->syncParentProductStockFromVariants($product);
                    } else {
                        $product->stock_quantity = round((float) ($product->stock_quantity ?? 0) + $qty, 3);
                        $product->save();
                    }

                    StockMovement::create([
                        'product_id'         => $product->id,
                        'product_variant_id' => $variantId,
                        'vendor_id'          => $vendorId,
                        'quantity'           => $qty,
                        'movement_type'      => 'purchase',
                        'reference_type'     => 'vendor_invoice',
                        'reference_id'       => $invoice->id,
                        'cost_price'         => $cost,
                        'notes'              => 'Vendor invoice ' . $invoice->invoice_number,
                        'created_at'         => now(),
                    ]);

                    // inward lot
                    $lot = new InventoryLot();
                    $lot->lot_code               = 'VIN-' . $invoice->id . '-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT) . '-' . str_pad((string) $lineCounter, 2, '0', STR_PAD_LEFT);

                    $lot->vendor_invoice_id      = $invoice->id;
                    $lot->vendor_invoice_item_id = $item->id;
                    $lot->vendor_id              = $vendorId;
                    $lot->product_id             = $product->id;
                    $lot->product_variant_id     = $variantId;

                    $lot->production_run_id      = null;
                    $lot->parent_inventory_lot_id = null;
                    $lot->root_inventory_lot_id   = null;

                    // Use product defaults from Step 1, with safe fallback
                    $lot->lot_stage = $product->lot_stage_default ?: 'raw';
                    $lot->is_saleable = (bool) ($product->inventory_is_saleable ?? true);
                    $lot->can_repack = (bool) ($product->inventory_can_repack ?? false);
                    $lot->lot_status = 'available';

                    $lot->inward_mode = $unitInwardMode;

                    $lot->batch_code  = $row['batch_code'] ?? null;
                    $lot->mfg_date    = $row['mfg_date'] ?? null;
                    $lot->packed_date = $row['packed_date'] ?? null;
                    $lot->expiry_date = $row['expiry_date'] ?? null;
                    $lot->received_date = $invoice->invoice_date ?? now()->toDateString();

                    $lot->received_quantity  = $qty;
                    $lot->available_quantity = $qty;

                    $lot->unit_weight_kg      = $unitWeight;
                    $lot->total_weight_kg     = $totalWeight;
                    $lot->available_weight_kg = $totalWeight;

                    $lot->piece_count           = $unitInwardMode === 'pieces' ? count($pieceWeights) : null;
                    $lot->available_piece_count = $unitInwardMode === 'pieces' ? count($pieceWeights) : null;
                    $lot->pack_size_kg          = $unitInwardMode === 'qty' ? $unitWeight : null;

                    $lot->unit_cost   = $cost;
                    $lot->cost_per_kg = ($totalWeight && $totalWeight > 0)
                        ? round((($qty * $cost) / $totalWeight), 2)
                        : null;
                    $lot->total_cost  = round($qty * $cost, 2);

                    $lot->notes = 'Vendor inward from invoice ' . $invoice->invoice_number;
                    $lot->created_by_id = $request->user()?->id;

                    $lot->save();

                    // Root lot for vendor inward is itself
                    $lot->root_inventory_lot_id = $lot->id;
                    $lot->save();

                    if ($unitInwardMode === 'pieces') {
                        foreach ($pieceWeights as $pIdx => $w) {
                            InventoryPiece::create([
                                'inventory_lot_id' => $lot->id,
                                'piece_no'         => $pIdx + 1,
                                'weight_kg'        => $w,
                                'status'           => 'available',
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('admin.vendor-invoices.show', $invoice)
            ->with('status', 'Vendor invoice created and stock updated.');
    }

    /**
     * Build one or more physical receipt units for a submitted invoice row.
     *
     * For variable/variant products in pieces mode with no selected variant, each
     * entered weight becomes its own auto-created variant and its own invoice item,
     * stock movement, inventory lot and inventory piece. This keeps variant stock
     * correct: four weight rows means four variants with stock quantity 1 each.
     */
    private function receiptUnitsForRow(Product $product, array $row, int $idx): array
    {
        $inwardMode = $row['_inward_mode'] ?? ($row['inward_mode'] ?? 'qty');
        $variant = null;

        if (! empty($row['product_variant_id'])) {
            $variant = ProductVariant::where('id', (int) $row['product_variant_id'])
                ->where('product_id', $product->id)
                ->firstOrFail();
        }

        $hasVariants = ProductVariant::where('product_id', $product->id)->exists();
        $isVariantProduct = strtolower((string) ($product->type ?? '')) === 'variable' || $hasVariants;

        $tax = isset($row['tax_amount']) ? round((float) $row['tax_amount'], 2) : 0.0;

        if ($isVariantProduct && ! $variant && $inwardMode === 'pieces') {
            $weights = $row['_piece_weights'] ?? [];
            $taxParts = $this->splitAmount($tax, count($weights));
            $units = [];

            foreach ($weights as $pieceIndex => $weightKg) {
                $weightKg = round((float) $weightKg, 3);

                $units[] = [
                    'variant' => null,
                    'auto_create_variant' => true,
                    'variant_weight_kg' => $weightKg,
                    'piece_index' => $pieceIndex + 1,
                    'quantity' => 1,
                    'unit_weight_kg' => $weightKg,
                    'total_weight_kg' => $weightKg,
                    'piece_weights' => [$weightKg],
                    'inward_mode' => 'pieces',
                    'tax_amount' => $taxParts[$pieceIndex] ?? 0.0,
                ];
            }

            return $units;
        }

        if ($isVariantProduct && ! $variant) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "items.$idx.product_variant_id" => "Select a variant for {$product->name}, or use Pieces mode so the invoice can auto-create one variant per weight row.",
            ]);
        }

        return [[
            'variant' => $variant,
            'auto_create_variant' => false,
            'quantity' => round((float) ($row['_stock_quantity'] ?? $row['quantity'] ?? 0), 3),
            'unit_weight_kg' => $row['_unit_weight_kg'] ?? null,
            'total_weight_kg' => $row['_total_weight_kg'] ?? null,
            'piece_weights' => $row['_piece_weights'] ?? [],
            'inward_mode' => $inwardMode,
            'tax_amount' => $tax,
        ]];
    }

    private function syncParentProductStockFromVariants(Product $product): void
    {
        $productId = (int) $product->id;

        $variantStock = round((float) ProductVariant::query()
            ->where('product_id', $productId)
            ->sum('stock_quantity'), 3);

        $freshProduct = Product::query()
            ->lockForUpdate()
            ->find($productId);

        if (! $freshProduct) {
            return;
        }

        $freshProduct->stock_quantity = $variantStock;
        $freshProduct->save();

        // Keep the already-loaded model in sync for any later calculations in this transaction.
        $product->stock_quantity = $variantStock;
    }

    private function createAutoPieceVariant(Product $product, float $weightKg, int $invoiceId, int $itemIndex, int $pieceIndex): ProductVariant
    {
        $weightKg = round($weightKg, 3);
        $baseSku = trim((string) ($product->sku ?: 'P' . $product->id));
        $weightToken = $this->weightSkuToken($weightKg);
        $skuBase = $this->safeSkuBase($baseSku . '-' . $weightToken . 'KG');
        $sku = $this->uniqueVariantSku($skuBase);

        return ProductVariant::create([
            'product_id' => $product->id,
            'barcode' => null,
            'sku' => $sku,
            'name' => trim($product->name . ' - ' . number_format($weightKg, 3) . ' kg'),
            'manage_stock' => true,
            // Stock is added by the central stock update immediately after creation.
            'stock_quantity' => 0,
            'low_stock_threshold' => $this->nullableDecimal($product->low_stock_threshold),
            'min_order_quantity' => $this->nullableDecimal($product->min_order_quantity),
            'product_weight' => $weightKg,
            'price' => max(0, round((float) ($product->base_price ?? 0), 2)),
            'pricing_unit' => 'kg',
            // Draft parent products create draft variants. Active variants can be
            // reviewed/activated later if the parent itself is inactive or unpriced.
            'is_active' => (bool) $product->is_active && (float) ($product->base_price ?? 0) > 0,
        ]);
    }

    private function splitAmount(float $amount, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $amountPaise = (int) round($amount * 100);
        $base = intdiv($amountPaise, $parts);
        $remainder = $amountPaise % $parts;
        $out = [];

        for ($i = 0; $i < $parts; $i++) {
            $part = $base + ($i < $remainder ? 1 : 0);
            $out[] = round($part / 100, 2);
        }

        return $out;
    }

    private function uniqueVariantSku(string $base): string
    {
        $base = Str::limit($base, 230, '');
        $candidate = $base;
        $suffix = 1;

        while (ProductVariant::withTrashed()->where('sku', $candidate)->exists()) {
            $suffix++;
            $candidate = Str::limit($base, 230, '') . '-' . str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    private function safeSkuBase(string $sku): string
    {
        $sku = strtoupper(trim($sku));
        $sku = preg_replace('/[^A-Z0-9\-]+/', '-', $sku) ?: 'VARIANT';
        $sku = trim(preg_replace('/\-+/', '-', $sku), '-');

        return $sku !== '' ? $sku : 'VARIANT';
    }

    private function weightSkuToken(float $weightKg): string
    {
        $token = rtrim(rtrim(number_format($weightKg, 3, '.', ''), '0'), '.');
        $token = str_replace('.', 'P', $token);

        return $token !== '' ? $token : '0';
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 3);
    }

    private function normalizeInvoiceItems(array $items): array
    {
        foreach ($items as $idx => $row) {
            $inwardMode = $row['inward_mode'] ?? 'qty';
            $inwardMode = $inwardMode === 'pieces' ? 'pieces' : 'qty';

            $row['_inward_mode'] = $inwardMode;

            if ($inwardMode === 'pieces') {
                $weights = $this->parsePieceWeights($row['piece_weights'] ?? '', $idx);
                $pieceCount = count($weights);
                $totalWeight = round(array_sum($weights), 3);

                if ($pieceCount < 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.piece_weights" => 'Enter at least one piece weight for pieces inward mode.',
                    ]);
                }

                $row['quantity'] = $pieceCount;
                $row['_stock_quantity'] = $pieceCount;
                $row['_piece_weights'] = $weights;
                $row['_unit_weight_kg'] = null;
                $row['_total_weight_kg'] = $totalWeight;
                $row['total_weight_kg'] = $totalWeight;
            } else {
                $qty = round((float) ($row['quantity'] ?? 0), 3);

                if ($qty <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.$idx.quantity" => 'Enter a quantity for normal quantity inward mode.',
                    ]);
                }

                $unitWeight = (isset($row['unit_weight_kg']) && $row['unit_weight_kg'] !== '')
                    ? round((float) $row['unit_weight_kg'], 3)
                    : null;

                $row['_stock_quantity'] = $qty;
                $row['_piece_weights'] = [];
                $row['_unit_weight_kg'] = $unitWeight;
                $row['_total_weight_kg'] = ($qty > 0 && $unitWeight !== null)
                    ? round($qty * $unitWeight, 3)
                    : null;
            }

            $items[$idx] = $row;
        }

        return $items;
    }

    private function parsePieceWeights(mixed $pieceText, int $idx): array
    {
        $text = trim((string) $pieceText);

        if ($text === '') {
            return [];
        }

        $weights = [];
        $invalidLines = [];

        foreach (preg_split("/\r\n|\n|\r/", $text) as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (! is_numeric($line) || (float) $line <= 0) {
                $invalidLines[] = $lineNumber + 1;
                continue;
            }

            $weights[] = round((float) $line, 3);
        }

        if ($invalidLines) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "items.$idx.piece_weights" => 'Piece weights must be positive numbers. Check line(s): ' . implode(', ', $invalidLines) . '.',
            ]);
        }

        return $weights;
    }

    public function show(VendorInvoice $vendorInvoice)
    {
        $vendorInvoice->load([
            'vendor',
            'items.product',
            'items.productVariant',
            'payments',
        ]);

        $lots = InventoryLot::query()
            ->where('vendor_invoice_id', $vendorInvoice->id)
            ->orderBy('id')
            ->get();

        if ($lots->isNotEmpty()) {
            $pieceCounts = InventoryPiece::query()
                ->whereIn('inventory_lot_id', $lots->pluck('id'))
                ->selectRaw('inventory_lot_id, COUNT(*) as piece_count')
                ->groupBy('inventory_lot_id')
                ->pluck('piece_count', 'inventory_lot_id');

            foreach ($lots as $lot) {
                $lot->piece_count = (int) ($pieceCounts[$lot->id] ?? 0);
            }
        }

        // Exact direct mapping using vendor_invoice_item_id
        $directMap = $lots
            ->filter(fn ($lot) => !empty($lot->vendor_invoice_item_id))
            ->keyBy('vendor_invoice_item_id')
            ->all();

        /*
        * Safety fallback for any historical rows that still don't have vendor_invoice_item_id.
        * This keeps old data working if any unmatched lots remain after backfill.
        */
        $fallbackLots = $lots
            ->filter(fn ($lot) => empty($lot->vendor_invoice_item_id))
            ->values();

        $fallbackIndex = 0;

        foreach ($vendorInvoice->items->sortBy('id') as $item) {
            if (!isset($directMap[$item->id]) && isset($fallbackLots[$fallbackIndex])) {
                $directMap[$item->id] = $fallbackLots[$fallbackIndex];
                $fallbackIndex++;
            }
        }

        return view('admin.vendor_invoices.show', [
            'invoice' => $vendorInvoice,
            'itemLotMap' => $directMap,
        ]);
    }

    public function outstandingSummary(Request $request)
    {
        $invoiceAgg = \App\Models\VendorInvoice::query()
            ->selectRaw('vendor_id, COUNT(*) as inv_count, SUM(total_amount) as inv_total')
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('vendor_id');

        $paymentAgg = \App\Models\VendorPayment::query()
            ->join('vendor_invoices', 'vendor_invoices.id', '=', 'vendor_payments.vendor_invoice_id')
            ->selectRaw('vendor_invoices.vendor_id as vendor_id, SUM(vendor_payments.amount) as paid_total')
            ->whereNotIn('vendor_invoices.status', ['cancelled'])
            ->groupBy('vendor_invoices.vendor_id');

        $rows = \App\Models\Vendor::query()
            ->leftJoinSub($invoiceAgg, 'ia', function ($join) {
                $join->on('vendors.id', '=', 'ia.vendor_id');
            })
            ->leftJoinSub($paymentAgg, 'pa', function ($join) {
                $join->on('vendors.id', '=', 'pa.vendor_id');
            })
            ->selectRaw('
                vendors.id as vendor_id,
                vendors.name as vendor_name,
                COALESCE(ia.inv_count, 0) as inv_count,
                COALESCE(ia.inv_total, 0) as inv_total,
                COALESCE(pa.paid_total, 0) as paid_total,
                GREATEST(COALESCE(ia.inv_total, 0) - COALESCE(pa.paid_total, 0), 0) as outstanding_total
            ')
            ->whereRaw('COALESCE(ia.inv_total, 0) > 0')
            ->orderByDesc('outstanding_total')
            ->get();

        $totalOutstandingAllVendors = (float) $rows->sum('outstanding_total');

        return view('admin.vendor_invoices.outstanding', compact('rows', 'totalOutstandingAllVendors'));
    }
}
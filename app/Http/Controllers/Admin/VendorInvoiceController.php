<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use App\Models\InventoryLot;
use App\Models\InventoryPack;
use App\Models\InventoryPiece;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VendorInvoiceController extends Controller
{
    private const INWARD_PIECES_WEIGHT = 'pieces_weight';
    private const INWARD_QUANTITY = 'quantity';

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
        $vendors = Vendor::orderBy('name')->get();
        $statuses = ['pending', 'partially_paid', 'paid', 'cancelled'];

        return view('admin.vendor_invoices.index', compact('invoices', 'vendors', 'statuses'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get();

        $productColumns = $this->existingColumns('products', [
            'id',
            'name',
            'sku',
            'barcode',
            'type',
            'inventory_role',
            'pack_type',
            'sell_unit',
            'product_weight',
            'pieces_per_pack',
            'hsn_code_id',
            'gst_rate',
            'base_price',
            'mrp_price',
            'b2c_price_includes_gst',
            'manage_stock',
            'stock_quantity',
            'inventory_is_saleable',
            'inventory_can_repack',
            'is_active',
        ]);

        $products = Product::query()
            ->select($productColumns)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $variantColumns = $this->existingColumns('product_variants', [
            'id',
            'product_id',
            'sku',
            'barcode',
            'name',
            'pack_type',
            'pieces_per_pack',
            'product_weight',
            'pricing_unit',
            'price',
            'mrp_price',
            'manage_stock',
            'stock_quantity',
            'is_active',
        ]);

        $productVariants = ProductVariant::query()
            ->select($variantColumns)
            ->whereIn('product_id', $products->pluck('id')->all())
            ->orderBy('product_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $hsnCodes = HsnCode::orderBy('code')->get();

        $vendor = null;
        if ($request->filled('vendor_id')) {
            $vendor = $vendors->firstWhere('id', (int) $request->vendor_id);
        }

        return view('admin.vendor_invoices.create', compact('vendors', 'products', 'productVariants', 'vendor', 'hsnCodes'));
    }

    /**
     * Keep vendor-invoice setup compatible with DBs that have not had every
     * historical product/pricing column added.
     */
    private function existingColumns(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return $columns;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));

        return $existing !== [] ? $existing : $columns;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'invoice_number' => ['required', 'string', 'max:50'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.receipt_type' => ['required', Rule::in([
                self::INWARD_PIECES_WEIGHT,
                self::INWARD_QUANTITY,
                // legacy aliases accepted so older browser sessions do not break
                'bulk_weight',
                'loose_pieces',
                'finished_pack',
            ])],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.individual_weights_text' => ['nullable', 'string', 'max:5000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost_includes_gst' => ['nullable', 'boolean'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_manual' => ['nullable', 'boolean'],
            'items.*.hsn_code_id' => ['nullable', 'exists:hsn_codes,id'],
            'items.*.mrp_incl_gst' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_code' => ['nullable', 'string', 'max:120'],
            'items.*.mfg_date' => ['nullable', 'date'],
            'items.*.packed_date' => ['nullable', 'date'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ]);

        $itemsData = $this->normalizeInvoiceItems($validated['items']);

        $subtotal = collect($itemsData)->sum(fn (array $row) => (float) $row['line_subtotal']);
        $taxTotal = collect($itemsData)->sum(fn (array $row) => (float) $row['tax_amount']);
        $totalAmount = round($subtotal + $taxTotal, 2);

        $invoice = null;

        DB::transaction(function () use ($validated, $itemsData, $subtotal, $taxTotal, $totalAmount, &$invoice, $request) {
            $invoice = VendorInvoice::create([
                'vendor_id' => (int) $validated['vendor_id'],
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'subtotal' => round((float) $subtotal, 2),
                'tax_amount' => round((float) $taxTotal, 2),
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'due_date' => $validated['due_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsData as $idx => $row) {
                /** @var Product $product */
                $product = Product::query()->lockForUpdate()->findOrFail((int) $row['product_id']);
                $variant = $this->resolveVariantForProduct($product, $row['product_variant_id'] ?? null, $idx);
                $hsn = $this->resolveHsnForRow($product, $row, $idx);
                $gstRate = round((float) ($hsn->gst_rate ?? 0), 2);
                $inwardMode = (string) $row['receipt_type'];

                if ($variant && $inwardMode === self::INWARD_PIECES_WEIGHT) {
                    throw ValidationException::withMessages([
                        "items.$idx.product_variant_id" => 'Pack variants can be selected only for quantity/finished-pack inward. Pieces-with-weight inward is product-level stock.',
                    ]);
                }
                $quantity = round((float) $row['quantity'], 3);
                $totalWeight = $row['total_weight_kg'] !== null ? round((float) $row['total_weight_kg'], 3) : null;
                $unitCost = round((float) $row['unit_cost'], 2);
                $taxAmount = round((float) $row['tax_amount'], 2);
                $lineTotal = round(((float) $row['line_subtotal']) + $taxAmount, 2);

                $item = new VendorInvoiceItem();
                $item->vendor_invoice_id = $invoice->id;
                $item->product_id = $product->id;
                $item->product_variant_id = $variant?->id;
                $this->assignIfColumn($item, 'vendor_invoice_items', 'product_sell_unit_id', null);
                $this->assignIfColumn($item, 'vendor_invoice_items', 'receipt_type', $inwardMode);
                $item->quantity = $quantity;
                $item->unit_cost = $unitCost;
                $this->assignIfColumn($item, 'vendor_invoice_items', 'unit_cost_includes_gst', (bool) ($row['unit_cost_includes_gst'] ?? false));
                $this->assignIfColumn($item, 'vendor_invoice_items', 'tax_manual', (bool) ($row['tax_manual'] ?? false));
                $this->assignIfColumn($item, 'vendor_invoice_items', 'hsn_code_id', $hsn->id);
                $this->assignIfColumn($item, 'vendor_invoice_items', 'gst_rate', $gstRate);
                $this->assignIfColumn($item, 'vendor_invoice_items', 'mrp_incl_gst', $row['mrp_incl_gst'] ?? null);
                $item->tax_amount = $taxAmount;
                $item->total = $lineTotal;

                $unitWeight = $this->invoiceItemUnitWeight($inwardMode, $quantity, $totalWeight);
                $this->assignIfColumn($item, 'vendor_invoice_items', 'unit_weight_kg', $unitWeight);
                $this->assignIfColumn($item, 'vendor_invoice_items', 'total_weight_kg', $totalWeight);
                $item->save();

                $lot = $this->createInventoryLot(
                    product: $product,
                    variant: $variant,
                    item: $item,
                    vendorId: (int) $validated['vendor_id'],
                    invoiceId: (int) $invoice->id,
                    inwardMode: $inwardMode,
                    quantity: $quantity,
                    totalWeightKg: $totalWeight,
                    unitCost: $unitCost,
                    lineSubtotal: (float) $row['line_subtotal'],
                    row: $row,
                    userId: $request->user()?->id
                );

                $this->createInventoryPieces($lot, $row['piece_weights'] ?? [], $request->user()?->id);
                $stockIncrease = $this->increaseStockTarget($product, $variant, $inwardMode, $quantity, $totalWeight);

                if ($inwardMode === self::INWARD_QUANTITY) {
                    $this->createInventoryPacksForSaleableQuantityLot(
                        lot: $lot,
                        product: $product,
                        variant: $variant,
                        quantity: $quantity,
                        unitCost: $unitCost,
                        row: $row,
                        userId: $request->user()?->id
                    );
                }

                $this->updateMrpIfEntered($product, $variant, $row, $gstRate);

                $this->writeStockMovement(
                    productId: (int) $product->id,
                    variantId: $variant?->id,
                    sellUnitId: null,
                    vendorId: (int) $validated['vendor_id'],
                    quantity: $stockIncrease,
                    referenceId: (int) $invoice->id,
                    costPrice: $unitCost,
                    notes: $this->receiptLabel($inwardMode) . " received on vendor invoice {$invoice->invoice_number}."
                );
            }
        });

        return redirect()
            ->route('admin.vendor-invoices.show', $invoice)
            ->with('status', 'Vendor invoice created. Inward stock, lots, and individual piece weights were recorded.');
    }

    public function show(VendorInvoice $vendorInvoice)
    {
        $vendorInvoice->load([
            'vendor',
            'items.product',
            'items.productVariant.sellUnit',
            'items.sellUnit',
            'items.hsnCode',
            'payments',
        ]);

        $lots = InventoryLot::query()
            ->with(['sellUnit', 'productVariant', 'pieces'])
            ->where('vendor_invoice_id', $vendorInvoice->id)
            ->orderBy('id')
            ->get();

        $directMap = $lots
            ->filter(fn ($lot) => ! empty($lot->vendor_invoice_item_id))
            ->keyBy('vendor_invoice_item_id')
            ->all();

        $fallbackLots = $lots
            ->filter(fn ($lot) => empty($lot->vendor_invoice_item_id))
            ->values();

        $fallbackIndex = 0;
        foreach ($vendorInvoice->items->sortBy('id') as $item) {
            if (! isset($directMap[$item->id]) && isset($fallbackLots[$fallbackIndex])) {
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

    private function normalizeInvoiceItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $idx => $row) {
            $inwardMode = $this->normalizeInwardMode((string) ($row['receipt_type'] ?? self::INWARD_QUANTITY));
            $quantity = round((float) ($row['quantity'] ?? 0), 3);
            $totalWeight = $row['total_weight_kg'] ?? null;
            $totalWeight = ($totalWeight === null || $totalWeight === '') ? null : round((float) $totalWeight, 3);
            $pieceWeights = $this->parseIndividualWeights($row['individual_weights_text'] ?? null);
            $unitCost = round((float) ($row['unit_cost'] ?? 0), 2);
            $unitCostIncludesGst = (bool) ($row['unit_cost_includes_gst'] ?? false);
            $taxManual = (bool) ($row['tax_manual'] ?? false);

            if ($inwardMode === self::INWARD_PIECES_WEIGHT) {
                if ($pieceWeights !== []) {
                    $quantity = count($pieceWeights);
                    $totalWeight = round(array_sum($pieceWeights), 3);
                }

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.quantity" => 'Enter the number of pieces for pieces-with-weight inward.',
                    ]);
                }

                if ($totalWeight === null || $totalWeight <= 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.total_weight_kg" => 'Enter total weight or individual weights for pieces-with-weight inward.',
                    ]);
                }

                $lineSubtotal = round($totalWeight * $unitCost, 2);
            } else {
                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.quantity" => 'Enter received quantity.',
                    ]);
                }

                $lineSubtotal = round($quantity * $unitCost, 2);
                $totalWeight = $totalWeight !== null && $totalWeight > 0 ? $totalWeight : null;
            }

            $taxAmount = 0.0;

            if (! $unitCostIncludesGst) {
                $product = Product::find((int) ($row['product_id'] ?? 0));
                $hsn = null;
                if ($product && $product->hsn_code_id) {
                    $hsn = HsnCode::find((int) $product->hsn_code_id);
                } elseif (! empty($row['hsn_code_id'])) {
                    $hsn = HsnCode::find((int) $row['hsn_code_id']);
                }

                $rate = round((float) ($hsn?->gst_rate ?? 0), 2);
                $taxAmount = $taxManual
                    ? round((float) ($row['tax_amount'] ?? 0), 2)
                    : round(($lineSubtotal * $rate) / 100, 2);
            }

            $row['receipt_type'] = $inwardMode;
            $row['product_variant_id'] = isset($row['product_variant_id']) && $row['product_variant_id'] !== '' ? (int) $row['product_variant_id'] : null;
            $row['quantity'] = $quantity;
            $row['total_weight_kg'] = $totalWeight;
            $row['piece_weights'] = $pieceWeights;
            $row['unit_cost'] = $unitCost;
            $row['unit_cost_includes_gst'] = $unitCostIncludesGst;
            $row['tax_manual'] = $unitCostIncludesGst ? false : $taxManual;
            $row['tax_amount'] = $unitCostIncludesGst ? 0.0 : $taxAmount;
            $row['line_subtotal'] = $lineSubtotal;
            $row['mrp_incl_gst'] = isset($row['mrp_incl_gst']) && $row['mrp_incl_gst'] !== ''
                ? round((float) $row['mrp_incl_gst'], 2)
                : null;

            $normalized[] = $row;
        }

        return $normalized;
    }

    private function normalizeInwardMode(string $raw): string
    {
        return match ($raw) {
            'bulk_weight' => self::INWARD_PIECES_WEIGHT,
            'loose_pieces', 'finished_pack' => self::INWARD_QUANTITY,
            self::INWARD_PIECES_WEIGHT => self::INWARD_PIECES_WEIGHT,
            default => self::INWARD_QUANTITY,
        };
    }

    /**
     * Keep inventory_lots.inward_mode on the existing legacy values used by
     * storefront slab selectors, cart selection, lot screens, and production.
     * Vendor invoice items still keep the richer receipt_type values
     * pieces_weight / quantity for invoice display and calculation.
     */
    private function inventoryLotInwardMode(string $receiptType): string
    {
        return $receiptType === self::INWARD_PIECES_WEIGHT ? 'pieces' : 'qty';
    }

    private function parseIndividualWeights(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $tokens = $value;
        } else {
            $tokens = preg_split('/[\s,;]+/', (string) $value) ?: [];
        }

        return collect($tokens)
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '')
            ->map(fn ($token) => round((float) $token, 3))
            ->filter(fn (float $weight): bool => $weight > 0)
            ->values()
            ->all();
    }


    private function resolveVariantForProduct(Product $product, mixed $variantId, int $idx): ?ProductVariant
    {
        if ($variantId === null || $variantId === '') {
            return null;
        }

        $variant = ProductVariant::query()
            ->lockForUpdate()
            ->findOrFail((int) $variantId);

        if ((int) $variant->product_id !== (int) $product->id) {
            throw ValidationException::withMessages([
                "items.$idx.product_variant_id" => 'Selected pack variant does not belong to the selected product.',
            ]);
        }

        return $variant;
    }

    private function resolveHsnForRow(Product $product, array $row, int $idx): HsnCode
    {
        $submittedHsnId = $row['hsn_code_id'] ?? null;

        if (! empty($product->hsn_code_id)) {
            $finalHsnId = (int) $product->hsn_code_id;

            if (! empty($submittedHsnId) && (int) $submittedHsnId !== $finalHsnId) {
                throw ValidationException::withMessages([
                    "items.$idx.hsn_code_id" => "HSN is locked for {$product->name} and cannot be changed from the invoice.",
                ]);
            }

            return HsnCode::findOrFail($finalHsnId);
        }

        if (empty($submittedHsnId)) {
            throw ValidationException::withMessages([
                "items.$idx.hsn_code_id" => "Select an HSN code for {$product->name}.",
            ]);
        }

        $hsn = HsnCode::findOrFail((int) $submittedHsnId);
        $product->hsn_code_id = $hsn->id;

        if (Schema::hasColumn('products', 'gst_rate')) {
            $product->gst_rate = round((float) $hsn->gst_rate, 2);
        }

        $product->save();

        return $hsn;
    }

    private function createInventoryLot(Product $product, ?ProductVariant $variant, VendorInvoiceItem $item, int $vendorId, int $invoiceId, string $inwardMode, float $quantity, ?float $totalWeightKg, float $unitCost, float $lineSubtotal, array $row, ?int $userId): InventoryLot
    {
        $batchCode = trim((string) ($row['batch_code'] ?? '')) ?: null;
        $isPiecesWeight = $inwardMode === self::INWARD_PIECES_WEIGHT;
        $productRole = (string) ($product->inventory_role ?? (($product->is_active ?? false) ? 'saleable' : 'internal'));
        $isSaleable = (bool) ($product->is_active ?? false) && $productRole !== 'internal';
        $productWeight = $this->targetWeightKg($product, $variant);
        $piecesPerPack = $this->targetPiecesPerPack($product, $variant);

        $lot = new InventoryLot();
        $lot->lot_code = $this->generateLotCode($product, $invoiceId, (int) $item->id);
        $lot->product_id = $product->id;
        $lot->product_variant_id = $variant?->id;
        $this->assignIfColumn($lot, 'inventory_lots', 'product_sell_unit_id', null);
        $lot->vendor_id = $vendorId;
        $lot->vendor_invoice_id = $invoiceId;
        $this->assignIfColumn($lot, 'inventory_lots', 'vendor_invoice_item_id', $item->id);
        $lot->lot_stage = $isPiecesWeight ? 'raw' : ($isSaleable ? 'pack' : 'raw');
        $lot->inward_mode = $this->inventoryLotInwardMode($inwardMode);
        $lot->is_saleable = $isSaleable;
        $lot->can_repack = $isPiecesWeight || (bool) ($product->inventory_can_repack ?? false) || $productRole === 'internal';
        $lot->lot_status = 'available';
        $lot->batch_code = $batchCode;
        $lot->mfg_date = $row['mfg_date'] ?? null;
        $lot->packed_date = $row['packed_date'] ?? null;
        $lot->expiry_date = $row['expiry_date'] ?? null;
        $lot->received_date = now()->toDateString();
        $lot->unit_cost = $unitCost;
        $lot->total_cost = round($lineSubtotal, 2);
        $lot->created_by_id = $userId;
        $lot->updated_by_id = $userId;

        if ($isPiecesWeight) {
            $lot->received_quantity = $totalWeightKg ?? 0;
            $lot->available_quantity = $totalWeightKg ?? 0;
            $lot->unit_weight_kg = 1;
            $lot->total_weight_kg = $totalWeightKg;
            $lot->available_weight_kg = $totalWeightKg;
            $lot->piece_count = (int) round($quantity);
            $lot->available_piece_count = (int) round($quantity);
            $lot->cost_per_kg = $unitCost;
            $lot->notes = 'Vendor inward as pieces with individual/total weight. Use Create Pack Stock to convert this raw lot into saleable products.';
        } else {
            $lot->received_quantity = $quantity;
            $lot->available_quantity = $quantity;
            $lot->unit_weight_kg = $productWeight;
            $lot->total_weight_kg = $totalWeightKg ?? ($productWeight !== null ? round($productWeight * $quantity, 3) : null);
            $lot->available_weight_kg = $lot->total_weight_kg;
            $lot->pack_count = $this->isPackLikeTarget($product, $variant) ? (int) round($quantity) : null;
            $lot->available_pack_count = $lot->pack_count;
            $lot->pieces_per_pack = $piecesPerPack;
            $lot->pack_size_kg = $productWeight;
            $lot->piece_count = $piecesPerPack !== null ? (int) round($piecesPerPack * $quantity) : null;
            $lot->available_piece_count = $lot->piece_count;
            $lot->cost_per_kg = ($lot->total_weight_kg && $lot->total_weight_kg > 0) ? round($lineSubtotal / (float) $lot->total_weight_kg, 2) : null;
            $lot->notes = $isSaleable
                ? 'Vendor inward as finished saleable product stock.'
                : 'Vendor inward as internal quantity stock.';
        }

        $lot->save();

        $lot->root_inventory_lot_id = $lot->id;
        $lot->save();

        return $lot;
    }

    private function createInventoryPieces(InventoryLot $lot, array $weights, ?int $userId): void
    {
        if ($weights === []) {
            return;
        }

        foreach (array_values($weights) as $index => $weight) {
            $attrs = [
                'inventory_lot_id' => $lot->id,
                'piece_no' => $index + 1,
                'label' => 'Piece ' . ($index + 1),
                'weight_kg' => round((float) $weight, 3),
                'available_weight_kg' => round((float) $weight, 3),
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            InventoryPiece::create($this->filterAttributesForSchema('inventory_pieces', $attrs));
        }
    }

    private function increaseStockTarget(Product $product, ?ProductVariant $variant, string $inwardMode, float $quantity, ?float $totalWeightKg): float
    {
        $stockIncrease = $inwardMode === self::INWARD_PIECES_WEIGHT
            ? round((float) ($totalWeightKg ?? 0), 3)
            : round($quantity, 3);

        if ($variant) {
            $variant->manage_stock = true;
            $variant->stock_quantity = round((float) ($variant->stock_quantity ?? 0) + $stockIncrease, 3);
            $variant->save();
            $this->syncProductStockFromVariants($product);

            return $stockIncrease;
        }

        $product->manage_stock = true;
        $product->stock_quantity = round((float) ($product->stock_quantity ?? 0) + $stockIncrease, 3);
        $product->save();

        return $stockIncrease;
    }

    private function createInventoryPacksForSaleableQuantityLot(InventoryLot $lot, Product $product, ?ProductVariant $variant, float $quantity, float $unitCost, array $row, ?int $userId): void
    {
        $productRole = (string) ($product->inventory_role ?? (($product->is_active ?? false) ? 'saleable' : 'internal'));
        if (! (bool) ($product->is_active ?? false) || $productRole === 'internal' || ! $this->isPackLikeTarget($product, $variant)) {
            return;
        }

        if ($variant && isset($variant->is_active) && ! (bool) $variant->is_active) {
            return;
        }

        if (abs($quantity - round($quantity)) > 0.0005) {
            return;
        }

        $packCount = (int) round($quantity);
        if ($packCount < 1) {
            return;
        }

        $piecesPerPack = $this->targetPiecesPerPack($product, $variant);
        $packWeight = $this->targetWeightKg($product, $variant);
        $batchCode = trim((string) ($row['batch_code'] ?? '')) ?: ($lot->batch_code ?: 'VIN-' . $lot->id);
        $startNo = ((int) InventoryPack::query()
            ->where('source_inventory_lot_id', $lot->id)
            ->where('product_id', $product->id)
            ->when($variant, fn ($query) => $query->where('product_variant_id', $variant->id), fn ($query) => $query->whereNull('product_variant_id'))
            ->max('pack_no')) + 1;

        for ($i = 0; $i < $packCount; $i++) {
            InventoryPack::create([
                'production_run_id' => null,
                'source_inventory_lot_id' => $lot->id,
                'source_inventory_piece_id' => null,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_sell_unit_id' => null,
                'pack_no' => $startNo + $i,
                'pack_code' => $batchCode . '-' . str_pad((string) ($startNo + $i), 3, '0', STR_PAD_LEFT),
                'pack_quantity' => 1,
                'available_pack_quantity' => 1,
                'pieces_per_pack' => $piecesPerPack,
                'total_pieces' => $piecesPerPack,
                'available_pieces' => $piecesPerPack,
                'source_pieces_per_unit' => $piecesPerPack,
                'source_quantity_consumed' => 1,
                'source_weight_kg_consumed' => $packWeight,
                'unit_weight_kg' => $packWeight,
                'actual_weight_kg' => $packWeight,
                'total_weight_kg' => $packWeight,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost,
                'packed_date' => $row['packed_date'] ?? now()->toDateString(),
                'expiry_date' => $row['expiry_date'] ?? $lot->expiry_date,
                'batch_code' => $batchCode,
                'status' => 'available',
                'notes' => 'Created directly from vendor invoice quantity inward.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ]);
        }
    }

    private function updateMrpIfEntered(Product $product, ?ProductVariant $variant, array $row, float $gstRate): void
    {
        $mrpInclGst = $row['mrp_incl_gst'] ?? null;

        if ($mrpInclGst === null || (float) $mrpInclGst <= 0) {
            return;
        }

        $storedMrp = $this->normalizeInclusivePrice((float) $mrpInclGst, $gstRate);

        if ($variant && Schema::hasColumn('product_variants', 'mrp_price')) {
            $variant->mrp_price = $storedMrp;
            $variant->save();
            return;
        }

        if (Schema::hasColumn('products', 'mrp_price')) {
            $product->mrp_price = $storedMrp;
            if (Schema::hasColumn('products', 'b2c_price_includes_gst')) {
                $product->b2c_price_includes_gst = true;
            }
            $product->save();
        }
    }

    private function invoiceItemUnitWeight(string $inwardMode, float $quantity, ?float $totalWeightKg): ?float
    {
        if ($inwardMode === self::INWARD_PIECES_WEIGHT && $quantity > 0 && $totalWeightKg !== null) {
            return round($totalWeightKg / $quantity, 3);
        }

        return null;
    }

    private function isPackLikeTarget(Product $product, ?ProductVariant $variant): bool
    {
        if ($variant) {
            $packType = $this->targetPackType($product, $variant);
            return in_array($packType, ['quantity', 'fixed_weight_pack', 'fixed_piece_pack'], true)
                || (string) ($variant->pricing_unit ?? 'pack') === 'pack';
        }

        $sellUnit = (string) ($product->sell_unit ?? 'piece');
        $packType = (string) ($product->pack_type ?? 'quantity');

        return $sellUnit !== 'kg'
            || in_array($packType, ['fixed_weight_pack', 'fixed_piece_pack', 'quantity'], true);
    }

    private function targetPackType(Product $product, ?ProductVariant $variant): string
    {
        if ($variant && Schema::hasColumn('product_variants', 'pack_type') && ! empty($variant->pack_type)) {
            return (string) $variant->pack_type;
        }

        if ($variant) {
            return (float) ($variant->product_weight ?? 0) > 0 ? 'fixed_weight_pack' : 'quantity';
        }

        return (string) ($product->pack_type ?? 'quantity');
    }

    private function targetWeightKg(Product $product, ?ProductVariant $variant): ?float
    {
        if ($variant) {
            return $this->positiveOrNull($variant->product_weight ?? null);
        }

        return $this->positiveOrNull($product->product_weight ?? null);
    }

    private function targetPiecesPerPack(Product $product, ?ProductVariant $variant): ?float
    {
        if ($variant && Schema::hasColumn('product_variants', 'pieces_per_pack')) {
            return $this->positiveOrNull($variant->pieces_per_pack ?? null);
        }

        return $this->positiveOrNull($product->pieces_per_pack ?? null);
    }

    private function syncProductStockFromVariants(Product $product): void
    {
        $sum = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->sum('stock_quantity');

        $product->type = 'variable';
        $product->manage_stock = true;
        $product->stock_quantity = round((float) $sum, 3);
        $product->save();
    }

    private function positiveDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return max(round((float) $value, 3), 0.0);
    }

    private function positiveOrNull(mixed $value): ?float
    {
        $value = $this->positiveDecimal($value);

        return $value > 0 ? $value : null;
    }

    private function normalizeInclusivePrice(float $amountInclGst, float $gstRate): float
    {
        $factor = 1 + max($gstRate, 0) / 100;

        return round($factor > 0 ? ($amountInclGst / $factor) : $amountInclGst, 2);
    }

    private function writeStockMovement(int $productId, ?int $variantId, ?int $sellUnitId, int $vendorId, float $quantity, int $referenceId, ?float $costPrice, string $notes): void
    {
        $attrs = [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'product_sell_unit_id' => $sellUnitId,
            'vendor_id' => $vendorId,
            'quantity' => round($quantity, 3),
            'movement_type' => 'purchase',
            'reference_type' => 'vendor_invoice',
            'reference_id' => $referenceId,
            'cost_price' => $costPrice,
            'notes' => $notes,
            'created_at' => now(),
        ];

        StockMovement::create($this->filterAttributesForSchema('stock_movements', $attrs));
    }

    private function receiptLabel(string $inwardMode): string
    {
        return match ($inwardMode) {
            self::INWARD_PIECES_WEIGHT => 'Pieces with weight stock',
            default => 'Quantity stock',
        };
    }

    private function generateLotCode(Product $product, int $invoiceId, int $itemId): string
    {
        $sku = strtoupper(trim((string) ($product->sku ?: 'P' . $product->id)));
        $sku = preg_replace('/[^A-Z0-9\-]+/', '-', $sku) ?: 'LOT';
        $sku = trim(preg_replace('/\-+/', '-', $sku), '-');

        return substr($sku, 0, 24) . '-VI' . $invoiceId . '-' . $itemId;
    }

    private function assignIfColumn(Model $model, string $table, string $column, mixed $value): void
    {
        if (Schema::hasColumn($table, $column)) {
            $model->{$column} = $value;
        }
    }

    private function filterAttributesForSchema(string $table, array $attrs): array
    {
        return Arr::where($attrs, fn ($value, string $column) => Schema::hasColumn($table, $column));
    }
}

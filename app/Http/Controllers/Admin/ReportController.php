<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const DEFAULT_LIMIT = 500;
    private const MAX_LIMIT = 2000;

    public function index()
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return view('admin.reports.index', [
            'monthStart' => $monthStart,
            'today' => $today,
            'reportCards' => [
                [
                    'title' => 'Sales Summary',
                    'description' => 'Order totals, revenue, discounts, tax, delivery, payment split and B2C/B2B split.',
                    'route' => route('admin.reports.sales-summary', ['from' => $monthStart, 'to' => $today]),
                    'accent' => 'Sales',
                ],
                [
                    'title' => 'Product Sales',
                    'description' => 'Product, variant, quantity, kg sold, revenue, GST and order count.',
                    'route' => route('admin.reports.product-sales', ['from' => $monthStart, 'to' => $today]),
                    'accent' => 'Products',
                ],
                [
                    'title' => 'Inventory Stock',
                    'description' => 'Product, variant, lot, piece and pack stock in one operational view.',
                    'route' => route('admin.reports.inventory-stock'),
                    'accent' => 'Inventory',
                ],
            ],
        ]);
    }

    public function salesSummary(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $filters = $this->salesFilters($request);

        $summary = $this->salesSummaryData($from, $to, $filters);

        return view('admin.reports.sales-summary', array_merge($summary, [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'statusOptions' => $this->orderStatusFilterOptions(),
            'paymentStatusOptions' => $this->paymentStatusFilterOptions(),
            'customerTypeOptions' => $this->customerTypeFilterOptions(),
        ]));
    }

    public function exportSalesSummary(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $filters = $this->salesFilters($request);
        $data = $this->salesSummaryData($from, $to, $filters);

        $rows = collect([
            ['Section', 'Metric', 'Value'],
            ['Summary', 'Orders', $data['totals']['orders']],
            ['Summary', 'Revenue excluding cancelled', $data['totals']['revenue']],
            ['Summary', 'Subtotal', $data['totals']['subtotal']],
            ['Summary', 'Discounts', $data['totals']['discount']],
            ['Summary', 'Tax', $data['totals']['tax']],
            ['Summary', 'Delivery fee', $data['totals']['delivery_fee']],
            ['Summary', 'Handling fee', $data['totals']['handling_fee']],
            ['Summary', 'Cancelled value', $data['totals']['cancelled_value']],
            ['Summary', 'Average order value', $data['totals']['average_order_value']],
            [],
            ['Payment Method', 'Orders', 'Amount'],
        ]);

        foreach ($data['paymentMethodRows'] as $row) {
            $rows->push([$row->payment_method ?: '—', (int) $row->orders, round((float) $row->amount, 2)]);
        }

        $rows->push([]);
        $rows->push(['Payment Status', 'Orders', 'Amount']);
        foreach ($data['paymentStatusRows'] as $row) {
            $rows->push([$row->payment_status ?: '—', (int) $row->orders, round((float) $row->amount, 2)]);
        }

        $rows->push([]);
        $rows->push(['Customer Type', 'Orders', 'Amount']);
        foreach ($data['customerTypeRows'] as $row) {
            $rows->push([$row->customer_type ?: 'unknown', (int) $row->orders, round((float) $row->amount, 2)]);
        }

        return $this->csvResponse("sales-summary-{$from}-to-{$to}.csv", $rows);
    }

    public function productSales(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $filters = $this->productSalesFilters($request);
        $rows = $this->productSalesRows($from, $to, $filters);
        $summary = $this->productSalesSummary($rows);

        return view('admin.reports.product-sales', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'categories' => $this->categoryOptions(),
            'products' => $this->productOptions(),
            'statusOptions' => $this->orderStatusFilterOptions(),
            'customerTypeOptions' => $this->customerTypeFilterOptions(),
        ]);
    }

    public function exportProductSales(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $filters = $this->productSalesFilters($request);
        $rows = $this->productSalesRows($from, $to, $filters, false);

        $csvRows = collect([
            ['Product', 'Variant', 'SKU', 'Quantity sold', 'Weight sold kg', 'Revenue', 'Subtotal', 'Discount', 'Tax', 'Orders'],
        ]);

        foreach ($rows as $row) {
            $csvRows->push([
                $row->product_name,
                $row->variant_name ?: '',
                $row->sku ?: '',
                round((float) $row->quantity_sold, 3),
                round((float) $row->weight_sold_kg, 3),
                round((float) $row->revenue, 2),
                round((float) $row->subtotal, 2),
                round((float) $row->discount, 2),
                round((float) $row->tax, 2),
                (int) $row->orders_count,
            ]);
        }

        return $this->csvResponse("product-sales-{$from}-to-{$to}.csv", $csvRows);
    }

    public function inventoryStock(Request $request)
    {
        $filters = $this->inventoryFilters($request);
        $limit = $this->reportLimit($request);
        $rows = $this->inventoryStockRows($filters, $limit);
        $summary = $this->inventorySummary($filters);

        return view('admin.reports.inventory-stock', [
            'filters' => $filters,
            'rows' => $rows,
            'summary' => $summary,
            'categories' => $this->categoryOptions(),
            'products' => $this->productOptions(),
            'stockTypeOptions' => $this->stockTypeOptions(),
            'limit' => $limit,
            'maxLimit' => self::MAX_LIMIT,
        ]);
    }

    public function exportInventoryStock(Request $request): StreamedResponse
    {
        $filters = $this->inventoryFilters($request);
        $rows = $this->inventoryStockRows($filters, self::MAX_LIMIT);

        $csvRows = collect([
            ['Stock type', 'Product', 'Variant', 'Reference', 'Batch', 'Status', 'Quantity', 'Weight kg', 'Pieces', 'Packs', 'Expiry', 'Source'],
        ]);

        foreach ($rows as $row) {
            $csvRows->push([
                $row['type_label'],
                $row['product_name'],
                $row['variant_name'] ?? '',
                $row['reference'] ?? '',
                $row['batch'] ?? '',
                $row['status'] ?? '',
                $row['quantity'] ?? '',
                $row['weight_kg'] ?? '',
                $row['pieces'] ?? '',
                $row['packs'] ?? '',
                $row['expiry_date'] ?? '',
                $row['source'] ?? '',
            ]);
        }

        return $this->csvResponse('inventory-stock-' . now()->format('Ymd-His') . '.csv', $csvRows);
    }

    private function dateRange(Request $request): array
    {
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        try {
            $fromDate = Carbon::parse($from)->toDateString();
        } catch (\Throwable) {
            $fromDate = now()->startOfMonth()->toDateString();
        }

        try {
            $toDate = Carbon::parse($to)->toDateString();
        } catch (\Throwable) {
            $toDate = now()->toDateString();
        }

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    private function salesFilters(Request $request): array
    {
        return [
            'status' => $request->query('status', 'non_cancelled'),
            'payment_status' => $request->query('payment_status', ''),
            'customer_type' => $request->query('customer_type', ''),
        ];
    }

    private function productSalesFilters(Request $request): array
    {
        return [
            'status' => $request->query('status', 'non_cancelled'),
            'customer_type' => $request->query('customer_type', ''),
            'category_id' => (int) $request->query('category_id', 0),
            'product_id' => (int) $request->query('product_id', 0),
        ];
    }

    private function inventoryFilters(Request $request): array
    {
        return [
            'stock_type' => $request->query('stock_type', ''),
            'category_id' => (int) $request->query('category_id', 0),
            'product_id' => (int) $request->query('product_id', 0),
            'status' => $request->query('status', 'available'),
            'expiry' => $request->query('expiry', ''),
        ];
    }

    private function reportLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', self::DEFAULT_LIMIT);
        if ($limit <= 0) {
            return self::DEFAULT_LIMIT;
        }

        return min($limit, self::MAX_LIMIT);
    }

    private function baseOrdersQuery(string $from, string $to, array $filters): Builder
    {
        $query = DB::table('orders')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->whereRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) >= ?', [$from])
            ->whereRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) <= ?', [$to]);

        $status = (string) ($filters['status'] ?? 'non_cancelled');
        if ($status === 'non_cancelled' || $status === '') {
            $query->whereNotIn('orders.status', ['cancelled', 'pending_payment', 'payment_failed', 'payment_expired']);
        } elseif ($status !== 'all') {
            $query->where('orders.status', $status);
        }

        $paymentStatus = (string) ($filters['payment_status'] ?? '');
        if ($paymentStatus !== '' && $paymentStatus !== 'all') {
            $query->where('orders.payment_status', $paymentStatus);
        }

        $customerType = (string) ($filters['customer_type'] ?? '');
        if ($customerType !== '' && $customerType !== 'all') {
            $query->where('users.customer_type', $customerType);
        }

        return $query;
    }

    private function salesSummaryData(string $from, string $to, array $filters): array
    {
        $base = $this->baseOrdersQuery($from, $to, $filters);

        $totals = (clone $base)
            ->selectRaw('COUNT(orders.id) as orders')
            ->selectRaw("SUM(CASE WHEN orders.status = 'cancelled' THEN 0 ELSE orders.grand_total END) as revenue")
            ->selectRaw('SUM(orders.subtotal) as subtotal')
            ->selectRaw('SUM(orders.discount_total + COALESCE(orders.bandara_credit_discount_total, 0)) as discount')
            ->selectRaw('SUM(orders.tax_total + COALESCE(orders.delivery_tax_amount, 0) + COALESCE(orders.handling_tax_amount, 0)) as tax')
            ->selectRaw('SUM(COALESCE(orders.delivery_fee, 0)) as delivery_fee')
            ->selectRaw('SUM(COALESCE(orders.handling_fee, 0)) as handling_fee')
            ->selectRaw("SUM(CASE WHEN orders.status = 'cancelled' THEN orders.grand_total ELSE 0 END) as cancelled_value")
            ->first();

        $ordersCount = (int) ($totals->orders ?? 0);
        $revenue = round((float) ($totals->revenue ?? 0), 2);

        $paymentMethodRows = (clone $base)
            ->selectRaw("COALESCE(NULLIF(orders.payment_method, ''), 'unknown') as payment_method")
            ->selectRaw('COUNT(orders.id) as orders')
            ->selectRaw('SUM(orders.grand_total) as amount')
            ->groupBy('payment_method')
            ->orderByDesc('amount')
            ->get();

        $paymentStatusRows = (clone $base)
            ->selectRaw("COALESCE(NULLIF(orders.payment_status, ''), 'unknown') as payment_status")
            ->selectRaw('COUNT(orders.id) as orders')
            ->selectRaw('SUM(orders.grand_total) as amount')
            ->groupBy('payment_status')
            ->orderByDesc('amount')
            ->get();

        $customerTypeRows = (clone $base)
            ->selectRaw("COALESCE(NULLIF(users.customer_type, ''), 'unknown') as customer_type")
            ->selectRaw('COUNT(orders.id) as orders')
            ->selectRaw('SUM(orders.grand_total) as amount')
            ->groupBy('customer_type')
            ->orderByDesc('amount')
            ->get();

        return [
            'totals' => [
                'orders' => $ordersCount,
                'revenue' => $revenue,
                'subtotal' => round((float) ($totals->subtotal ?? 0), 2),
                'discount' => round((float) ($totals->discount ?? 0), 2),
                'tax' => round((float) ($totals->tax ?? 0), 2),
                'delivery_fee' => round((float) ($totals->delivery_fee ?? 0), 2),
                'handling_fee' => round((float) ($totals->handling_fee ?? 0), 2),
                'cancelled_value' => round((float) ($totals->cancelled_value ?? 0), 2),
                'average_order_value' => $ordersCount > 0 ? round($revenue / $ordersCount, 2) : 0.0,
            ],
            'paymentMethodRows' => $paymentMethodRows,
            'paymentStatusRows' => $paymentStatusRows,
            'customerTypeRows' => $customerTypeRows,
        ];
    }

    private function productSalesRows(string $from, string $to, array $filters, bool $limit = true): Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->whereRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) >= ?', [$from])
            ->whereRaw('DATE(COALESCE(orders.placed_at, orders.created_at)) <= ?', [$to]);

        $status = (string) ($filters['status'] ?? 'non_cancelled');
        if ($status === 'non_cancelled' || $status === '') {
            $query->whereNotIn('orders.status', ['cancelled', 'pending_payment', 'payment_failed', 'payment_expired']);
        } elseif ($status !== 'all') {
            $query->where('orders.status', $status);
        }

        $customerType = (string) ($filters['customer_type'] ?? '');
        if ($customerType !== '' && $customerType !== 'all') {
            $query->where('users.customer_type', $customerType);
        }

        $productId = (int) ($filters['product_id'] ?? 0);
        if ($productId > 0) {
            $query->where('order_items.product_id', $productId);
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0 && Schema::hasTable('category_product')) {
            $query->whereExists(function ($sub) use ($categoryId) {
                $sub->selectRaw('1')
                    ->from('category_product')
                    ->whereColumn('category_product.product_id', 'order_items.product_id')
                    ->where('category_product.category_id', $categoryId);
            });
        }

        $query->selectRaw('order_items.product_id')
            ->selectRaw('order_items.product_variant_id')
            ->selectRaw('COALESCE(MAX(products.name), MAX(order_items.product_name)) as product_name')
            ->selectRaw("COALESCE(MAX(product_variants.name), MAX(product_variants.sku), '') as variant_name")
            ->selectRaw('COALESCE(MAX(product_variants.sku), MAX(order_items.sku), MAX(products.sku), "") as sku')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('SUM(COALESCE(order_items.item_weight, 0)) as weight_sold_kg')
            ->selectRaw('SUM(order_items.subtotal) as subtotal')
            ->selectRaw('SUM(order_items.discount_amount) as discount')
            ->selectRaw('SUM(order_items.tax_amount) as tax')
            ->selectRaw('SUM(order_items.total) as revenue')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->groupBy('order_items.product_id', 'order_items.product_variant_id')
            ->orderByDesc('revenue')
            ->orderBy('product_name');

        if ($limit) {
            $query->limit(500);
        }

        return $query->get();
    }

    private function productSalesSummary(Collection $rows): array
    {
        return [
            'lines' => $rows->count(),
            'quantity_sold' => round((float) $rows->sum(fn ($row) => (float) $row->quantity_sold), 3),
            'weight_sold_kg' => round((float) $rows->sum(fn ($row) => (float) $row->weight_sold_kg), 3),
            'revenue' => round((float) $rows->sum(fn ($row) => (float) $row->revenue), 2),
            'tax' => round((float) $rows->sum(fn ($row) => (float) $row->tax), 2),
        ];
    }

    private function inventoryStockRows(array $filters, int $limit): Collection
    {
        $requestedType = (string) ($filters['stock_type'] ?? '');
        $rows = collect();

        foreach ($this->stockTypeOptions() as $type => $label) {
            if ($requestedType !== '' && $requestedType !== $type) {
                continue;
            }

            $method = 'inventory' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type))) . 'Rows';
            if (method_exists($this, $method)) {
                $rows = $rows->merge($this->{$method}($filters, $limit));
            }
        }

        return $rows
            ->sortBy([
                ['product_name', 'asc'],
                ['type_label', 'asc'],
                ['expiry_date', 'asc'],
            ])
            ->take($limit)
            ->values();
    }

    private function inventoryProductRows(array $filters, int $limit): Collection
    {
        $query = DB::table('products')
            ->selectRaw("'product' as stock_type")
            ->selectRaw('products.id as product_id')
            ->selectRaw('NULL as variant_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('NULL as variant_name')
            ->selectRaw("CONCAT('Product #', products.id) as reference")
            ->selectRaw('NULL as batch')
            ->selectRaw("CASE WHEN products.is_active = 1 THEN 'active' ELSE 'inactive' END as status")
            ->selectRaw('products.stock_quantity as quantity')
            ->selectRaw("CASE WHEN products.sell_unit = 'kg' THEN products.stock_quantity ELSE NULL END as weight_kg")
            ->selectRaw('products.pieces_per_pack as pieces')
            ->selectRaw("CASE WHEN products.sell_unit IN ('pack','piece') THEN products.stock_quantity ELSE NULL END as packs")
            ->selectRaw('NULL as expiry_date')
            ->selectRaw("'Product stock' as source");

        $this->applyProductFilters($query, $filters, 'products.id');
        $this->applyInventoryStatusFilter($query, $filters, 'products.stock_quantity');

        return $query->orderBy('products.name')->limit($limit)->get()->map(fn ($row) => $this->inventoryRow($row, 'Product stock'));
    }

    private function inventoryVariantRows(array $filters, int $limit): Collection
    {
        $query = DB::table('product_variants')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->selectRaw("'variant' as stock_type")
            ->selectRaw('products.id as product_id')
            ->selectRaw('product_variants.id as variant_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(product_variants.name, product_variants.sku) as variant_name')
            ->selectRaw("CONCAT('Variant #', product_variants.id) as reference")
            ->selectRaw('NULL as batch')
            ->selectRaw("CASE WHEN product_variants.is_active = 1 THEN 'active' ELSE 'inactive' END as status")
            ->selectRaw('product_variants.stock_quantity as quantity')
            ->selectRaw("CASE WHEN product_variants.pricing_unit = 'kg' THEN product_variants.stock_quantity ELSE NULL END as weight_kg")
            ->selectRaw('product_variants.pieces_per_pack as pieces')
            ->selectRaw('product_variants.stock_quantity as packs')
            ->selectRaw('NULL as expiry_date')
            ->selectRaw("'Variant stock' as source");

        $this->applyProductFilters($query, $filters, 'products.id');
        $this->applyInventoryStatusFilter($query, $filters, 'product_variants.stock_quantity');

        return $query->orderBy('products.name')->orderBy('product_variants.name')->limit($limit)->get()->map(fn ($row) => $this->inventoryRow($row, 'Variant stock'));
    }

    private function inventoryLotRows(array $filters, int $limit): Collection
    {
        $query = DB::table('inventory_lots')
            ->join('products', 'products.id', '=', 'inventory_lots.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_lots.product_variant_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'inventory_lots.vendor_id')
            ->selectRaw("'lot' as stock_type")
            ->selectRaw('products.id as product_id')
            ->selectRaw('product_variants.id as variant_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(product_variants.name, product_variants.sku) as variant_name')
            ->selectRaw("CONCAT('Lot ', COALESCE(inventory_lots.lot_code, inventory_lots.id)) as reference")
            ->selectRaw('inventory_lots.batch_code as batch')
            ->selectRaw('inventory_lots.lot_status as status')
            ->selectRaw('inventory_lots.available_quantity as quantity')
            ->selectRaw('inventory_lots.available_weight_kg as weight_kg')
            ->selectRaw('inventory_lots.available_piece_count as pieces')
            ->selectRaw('inventory_lots.available_pack_count as packs')
            ->selectRaw('inventory_lots.expiry_date as expiry_date')
            ->selectRaw("CONCAT('Lot', CASE WHEN vendors.name IS NOT NULL THEN CONCAT(' · ', vendors.name) ELSE '' END) as source");

        $this->applyProductFilters($query, $filters, 'products.id');
        $this->applyInventoryStatusFilter($query, $filters, 'inventory_lots.available_quantity', 'inventory_lots.available_weight_kg', 'inventory_lots.available_piece_count', 'inventory_lots.available_pack_count');
        $this->applyExpiryFilter($query, $filters, 'inventory_lots.expiry_date');

        return $query->orderBy('products.name')->orderBy('inventory_lots.expiry_date')->limit($limit)->get()->map(fn ($row) => $this->inventoryRow($row, 'Lot stock'));
    }

    private function inventoryPieceRows(array $filters, int $limit): Collection
    {
        $query = DB::table('inventory_pieces')
            ->join('inventory_lots', 'inventory_lots.id', '=', 'inventory_pieces.inventory_lot_id')
            ->join('products', 'products.id', '=', 'inventory_lots.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_lots.product_variant_id')
            ->selectRaw("'piece' as stock_type")
            ->selectRaw('products.id as product_id')
            ->selectRaw('product_variants.id as variant_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(product_variants.name, product_variants.sku) as variant_name')
            ->selectRaw("CONCAT('Piece ', inventory_pieces.piece_no, CASE WHEN inventory_pieces.label IS NOT NULL THEN CONCAT(' · ', inventory_pieces.label) ELSE '' END) as reference")
            ->selectRaw('inventory_lots.batch_code as batch')
            ->selectRaw('inventory_pieces.status as status')
            ->selectRaw('1 as quantity')
            ->selectRaw('COALESCE(inventory_pieces.available_weight_kg, inventory_pieces.weight_kg) as weight_kg')
            ->selectRaw('1 as pieces')
            ->selectRaw('NULL as packs')
            ->selectRaw('inventory_lots.expiry_date as expiry_date')
            ->selectRaw("CONCAT('Lot ', COALESCE(inventory_lots.lot_code, inventory_lots.id)) as source");

        $this->applyProductFilters($query, $filters, 'products.id');
        $this->applyPieceStatusFilter($query, $filters);
        $this->applyExpiryFilter($query, $filters, 'inventory_lots.expiry_date');

        return $query->orderBy('products.name')->orderBy('inventory_lots.expiry_date')->limit($limit)->get()->map(fn ($row) => $this->inventoryRow($row, 'Inventory piece'));
    }

    private function inventoryPackRows(array $filters, int $limit): Collection
    {
        $query = DB::table('inventory_packs')
            ->join('products', 'products.id', '=', 'inventory_packs.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'inventory_packs.product_variant_id')
            ->selectRaw("'pack' as stock_type")
            ->selectRaw('products.id as product_id')
            ->selectRaw('product_variants.id as variant_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('COALESCE(product_variants.name, product_variants.sku) as variant_name')
            ->selectRaw("CONCAT('Pack ', COALESCE(inventory_packs.pack_code, inventory_packs.id)) as reference")
            ->selectRaw('inventory_packs.batch_code as batch')
            ->selectRaw('inventory_packs.status as status')
            ->selectRaw('inventory_packs.available_pack_quantity as quantity')
            ->selectRaw('COALESCE(inventory_packs.total_weight_kg - COALESCE(inventory_packs.sold_weight_kg, 0), inventory_packs.actual_weight_kg, inventory_packs.total_weight_kg) as weight_kg')
            ->selectRaw('inventory_packs.available_pieces as pieces')
            ->selectRaw('inventory_packs.available_pack_quantity as packs')
            ->selectRaw('inventory_packs.expiry_date as expiry_date')
            ->selectRaw("CONCAT('Pack stock', CASE WHEN inventory_packs.source_inventory_lot_id IS NOT NULL THEN CONCAT(' · Source lot #', inventory_packs.source_inventory_lot_id) ELSE '' END) as source");

        $this->applyProductFilters($query, $filters, 'products.id');
        $this->applyInventoryStatusFilter($query, $filters, 'inventory_packs.available_pack_quantity', 'inventory_packs.available_pieces');
        $this->applyExpiryFilter($query, $filters, 'inventory_packs.expiry_date');

        return $query->orderBy('products.name')->orderBy('inventory_packs.expiry_date')->limit($limit)->get()->map(fn ($row) => $this->inventoryRow($row, 'Pack stock'));
    }

    private function inventoryRow(object $row, string $typeLabel): array
    {
        return [
            'stock_type' => $row->stock_type,
            'type_label' => $typeLabel,
            'product_id' => $row->product_id,
            'variant_id' => $row->variant_id,
            'product_name' => $row->product_name ?: '—',
            'variant_name' => $row->variant_name ?: null,
            'reference' => $row->reference ?: '',
            'batch' => $row->batch ?: '',
            'status' => $row->status ?: '',
            'quantity' => $this->formatNumber($row->quantity ?? null, 3),
            'weight_kg' => $this->formatNumber($row->weight_kg ?? null, 3),
            'pieces' => $this->formatNumber($row->pieces ?? null, 0),
            'packs' => $this->formatNumber($row->packs ?? null, 3),
            'expiry_date' => $row->expiry_date ?: '',
            'source' => $row->source ?: '',
        ];
    }

    private function inventorySummary(array $filters): array
    {
        return [
            'product_rows' => $this->safeCount('products'),
            'variant_rows' => $this->safeCount('product_variants'),
            'available_lots' => $this->safeWherePositiveCount('inventory_lots', ['available_quantity', 'available_weight_kg', 'available_piece_count', 'available_pack_count']),
            'available_pieces' => $this->safeWherePositiveCount('inventory_pieces', ['available_weight_kg']),
            'available_packs' => $this->safeWherePositiveCount('inventory_packs', ['available_pack_quantity', 'available_pieces']),
        ];
    }

    private function safeCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function safeWherePositiveCount(string $table, array $columns): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $availableColumns = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));
        if (empty($availableColumns)) {
            return 0;
        }

        $query = DB::table($table);
        $query->where(function ($sub) use ($availableColumns) {
            foreach ($availableColumns as $column) {
                $sub->orWhere($column, '>', 0);
            }
        });

        return (int) $query->count();
    }

    private function applyProductFilters(Builder $query, array $filters, string $productColumn): void
    {
        $productId = (int) ($filters['product_id'] ?? 0);
        if ($productId > 0) {
            $query->where($productColumn, $productId);
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0 && Schema::hasTable('category_product')) {
            $query->whereExists(function ($sub) use ($categoryId, $productColumn) {
                $sub->selectRaw('1')
                    ->from('category_product')
                    ->whereColumn('category_product.product_id', $productColumn)
                    ->where('category_product.category_id', $categoryId);
            });
        }
    }

    private function applyPieceStatusFilter(Builder $query, array $filters): void
    {
        $status = (string) ($filters['status'] ?? 'available');
        if ($status === '' || $status === 'all') {
            return;
        }

        if ($status === 'available') {
            $query->whereRaw('COALESCE(inventory_pieces.available_weight_kg, inventory_pieces.weight_kg) > 0');
            return;
        }

        if ($status === 'zero') {
            $query->whereRaw('COALESCE(inventory_pieces.available_weight_kg, inventory_pieces.weight_kg, 0) <= 0');
        }
    }

    private function applyInventoryStatusFilter(Builder $query, array $filters, ?string ...$columns): void
    {
        $status = (string) ($filters['status'] ?? 'available');
        if ($status === '' || $status === 'all') {
            return;
        }

        if ($status === 'available') {
            $query->where(function ($sub) use ($columns) {
                foreach (array_filter($columns) as $column) {
                    $sub->orWhere($column, '>', 0);
                }
            });
        }

        if ($status === 'zero') {
            $query->where(function ($sub) use ($columns) {
                foreach (array_filter($columns) as $column) {
                    $sub->where(function ($inner) use ($column) {
                        $inner->whereNull($column)->orWhere($column, '<=', 0);
                    });
                }
            });
        }
    }

    private function applyExpiryFilter(Builder $query, array $filters, string $column): void
    {
        $expiry = (string) ($filters['expiry'] ?? '');
        if ($expiry === '') {
            return;
        }

        $today = now()->toDateString();
        if ($expiry === 'expired') {
            $query->whereNotNull($column)->whereDate($column, '<', $today);
            return;
        }

        if (str_starts_with($expiry, 'next_')) {
            $days = (int) str_replace('next_', '', $expiry);
            if ($days > 0) {
                $query->whereNotNull($column)
                    ->whereDate($column, '>=', $today)
                    ->whereDate($column, '<=', now()->addDays($days)->toDateString());
            }
        }
    }

    private function categoryOptions(): Collection
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }

        return DB::table('categories')
            ->select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    private function productOptions(): Collection
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        return DB::table('products')
            ->select('id', 'name', 'sku')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(1000)
            ->get();
    }

    private function orderStatusFilterOptions(): array
    {
        return [
            'non_cancelled' => 'Confirmed / fulfillable orders',
            'all' => 'All orders',
            'pending_payment' => 'Pending Payment',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'payment_failed' => 'Payment Failed',
            'payment_expired' => 'Payment Expired',
        ];
    }

    private function paymentStatusFilterOptions(): array
    {
        return [
            '' => 'All payment statuses',
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'expired' => 'Expired',
            'refunded' => 'Refunded',
        ];
    }

    private function customerTypeFilterOptions(): array
    {
        return [
            '' => 'All customer types',
            'b2c' => 'B2C',
            'b2b' => 'B2B',
            'staff' => 'Staff',
        ];
    }

    private function stockTypeOptions(): array
    {
        return [
            'product' => 'Product stock',
            'variant' => 'Variant stock',
            'lot' => 'Lots',
            'piece' => 'Pieces / slabs',
            'pack' => 'Pack stock',
        ];
    }

    private function formatNumber($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = number_format((float) $value, $decimals, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function csvResponse(string $filename, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, is_array($row) ? $row : (array) $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

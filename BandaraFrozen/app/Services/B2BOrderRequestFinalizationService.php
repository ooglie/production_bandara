<?php

namespace App\Services;

use App\Models\B2BOrderItemAllocation;
use App\Models\B2BOrderRequest;
use App\Models\B2BOrderRequestItem;
use App\Models\CustomerAddress;
use App\Models\InventoryPiece;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class B2BOrderRequestFinalizationService
{
    protected static array $columnCache = [];

    public function finalize(B2BOrderRequest $request, ?User $actor = null): array
    {
        return DB::transaction(function () use ($request, $actor) {
            $request = B2BOrderRequest::query()
                ->with([
                    'user',
                    'finalizedOrder',
                    'finalizedInvoice',
                    'items.product',
                    'items.sellUnit',
                    'items.allocations.inventoryPiece.inventoryLot.productVariant',
                    'items.allocations.inventoryLot.productVariant',
                    'items.allocations.variant',
                ])
                ->where('id', $request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->finalized_order_id) {
                return [
                    'order' => $request->finalizedOrder,
                    'invoice' => $request->finalizedInvoice,
                    'created' => false,
                ];
            }

            if ($request->status !== B2BOrderRequest::STATUS_ALLOCATED) {
                throw new RuntimeException('Only fully allocated B2B requests can be finalized into an order/invoice.');
            }

            $user = $request->user;
            if (! $user || (($user->customer_type ?? 'b2c') !== 'b2b')) {
                throw new RuntimeException('The linked customer is not a valid B2B customer.');
            }

            if ($request->items->isEmpty()) {
                throw new RuntimeException('This B2B request has no items to finalize.');
            }

            $address = $this->defaultAddressForUser((int) $user->id);
            if (! $address) {
                throw new RuntimeException('Cannot finalize this request because the B2B customer has no saved delivery address.');
            }

            $rows = $this->buildRows($request);
            if (empty($rows)) {
                throw new RuntimeException('There are no reserved allocations to finalize.');
            }

            $subtotal = round(array_sum(array_column($rows, 'subtotal')), 2);
            $gst = $this->calculateGst($rows, (string) ($address->state ?? ''));
            $grandTotal = round($subtotal + (float) $gst['tax_total'], 2);

            $payLater = app(B2BPayLaterService::class)->checkoutOptionFor($user, $grandTotal);
            $paymentMethod = ($payLater['eligible'] ?? false) ? 'pay_later' : 'razorpay';
            $termsDays = $paymentMethod === 'pay_later'
                ? (int) ($payLater['terms_days'] ?? 7)
                : 7;
            $dueAt = now()->addDays(max($termsDays, 1));

            $order = new Order();
            $order->order_number = $this->generateOrderNumber();
            $order->user_id = $user->id;
            $order->status = 'processing';
            $order->subtotal = $subtotal;
            $order->discount_total = 0;
            $order->tax_total = round((float) $gst['tax_total'], 2);
            $order->shipping_total = 0;
            $order->grand_total = $grandTotal;
            $order->coupon_id = null;
            $order->gst_type = $gst['gst_type'];
            $order->cgst_amount = $gst['cgst_amount'];
            $order->sgst_amount = $gst['sgst_amount'];
            $order->igst_amount = $gst['igst_amount'];
            $order->payment_status = 'pending';
            if ($this->hasColumn('orders', 'payment_method')) {
                $order->payment_method = $paymentMethod;
            }
            if ($paymentMethod === 'pay_later') {
                if ($this->hasColumn('orders', 'payment_terms_days')) {
                    $order->payment_terms_days = $termsDays;
                }
                if ($this->hasColumn('orders', 'payment_due_at')) {
                    $order->payment_due_at = $dueAt;
                }
                if ($this->hasColumn('orders', 'pay_later_approved_at')) {
                    $order->pay_later_approved_at = now();
                }
            }
            $order->customer_note = trim((string) ($request->customer_note ?? '')) ?: null;
            $order->placed_at = now();
            $order->save();

            $this->copyAddressToOrder($order, $address);

            $orderItemsByRequestItem = [];
            $invoiceItemsByRequestItem = [];
            $lineTaxMap = $gst['line_tax_map'] ?? [];
            foreach ($rows as $index => $row) {
                $tax = $lineTaxMap[$index] ?? ['tax' => 0, 'cgst' => null, 'sgst' => null, 'igst' => null];

                $orderItem = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $row['product_id'],
                    'product_variant_id' => $row['product_variant_id'],
                    'product_sell_unit_id' => $row['product_sell_unit_id'],
                    'product_name' => $row['product_name'],
                    'sku' => $row['sku'],
                    'attributes_snapshot' => $row['snapshot'],
                    'quantity' => 1,
                    'unit_price' => $row['unit_price'],
                    'subtotal' => $row['subtotal'],
                    'discount_amount' => 0,
                    'tax_amount' => round((float) ($tax['tax'] ?? 0), 2),
                    'total' => round($row['subtotal'] + (float) ($tax['tax'] ?? 0), 2),
                    'cgst_amount' => $tax['cgst'],
                    'sgst_amount' => $tax['sgst'],
                    'igst_amount' => $tax['igst'],
                    'item_weight' => $row['weight_kg'],
                    'sell_unit' => $row['sell_unit'],
                    'pricing_unit' => $row['pricing_unit'],
                ]);

                $allocation = $row['allocation'];
                $allocation->sold_order_id = $order->id;
                $allocation->sold_order_item_id = $orderItem->id;
                $allocation->status = B2BOrderItemAllocation::STATUS_SOLD;
                $allocation->sold_at = now();
                $allocation->save();

                $this->markPieceSoldWithoutFurtherStockDeduction($allocation, $orderItem);

                $orderItemsByRequestItem[$row['request_item_id']] ??= $orderItem->id;
            }

            $invoice = Invoice::query()->create([
                'order_id' => $order->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'status' => $paymentMethod === 'pay_later' ? 'due' : 'pending',
                'invoice_date' => now()->toDateString(),
                'due_date' => $dueAt->toDateString(),
                'subtotal' => $subtotal,
                'tax_total' => round((float) $gst['tax_total'], 2),
                'discount_total' => 0,
                'grand_total' => $grandTotal,
            ]);

            $invoiceItemsByRequestItem = [];
            foreach ($order->items()->get() as $orderItem) {
                $invoiceItem = InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'order_item_id' => $orderItem->id,
                    'product_sell_unit_id' => $orderItem->product_sell_unit_id,
                    'description' => $orderItem->product_name,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'subtotal' => $orderItem->subtotal,
                    'tax_amount' => $orderItem->tax_amount,
                    'total' => $orderItem->total,
                    'item_weight' => $orderItem->item_weight,
                    'sell_unit' => $orderItem->sell_unit,
                    'pricing_unit' => $orderItem->pricing_unit,
                ]);

                $snapshot = is_array($orderItem->attributes_snapshot) ? $orderItem->attributes_snapshot : [];
                $requestItemId = (int) ($snapshot['b2b_order_request_item_id'] ?? 0);
                if ($requestItemId > 0 && ! isset($invoiceItemsByRequestItem[$requestItemId])) {
                    $invoiceItemsByRequestItem[$requestItemId] = $invoiceItem->id;
                }
            }

            foreach ($request->items as $item) {
                $item->status = B2BOrderRequestItem::STATUS_FINALIZED;
                $item->finalized_order_item_id = $orderItemsByRequestItem[$item->id] ?? null;
                $item->finalized_invoice_item_id = $invoiceItemsByRequestItem[$item->id] ?? null;
                $item->finalized_at = now();
                $item->save();
            }

            $request->status = B2BOrderRequest::STATUS_FINALIZED;
            $request->finalized_order_id = $order->id;
            $request->finalized_invoice_id = $invoice->id;
            $request->finalized_by_id = $actor?->id;
            $request->finalized_at = now();
            $request->save();

            return [
                'order' => $order,
                'invoice' => $invoice,
                'created' => true,
            ];
        }, 3);
    }

    protected function buildRows(B2BOrderRequest $request): array
    {
        $rows = [];

        foreach ($request->items as $item) {
            if ($item->status !== B2BOrderRequestItem::STATUS_ALLOCATED) {
                throw new RuntimeException('All request items must be allocated before finalization.');
            }

            $product = $item->product;
            if (! $product) {
                throw new RuntimeException('One of the requested products no longer exists.');
            }

            $allocations = $item->allocations->where('status', B2BOrderItemAllocation::STATUS_RESERVED)->values();
            if ($allocations->isEmpty()) {
                throw new RuntimeException("{$product->name} has no reserved allocation to finalize.");
            }

            foreach ($allocations as $allocation) {
                $weight = round((float) ($allocation->weight_kg ?? 0), 3);
                $unitPrice = round((float) ($allocation->unit_price ?? $item->quoted_unit_price ?? 0), 2);
                if ($unitPrice <= 0) {
                    throw new RuntimeException("{$product->name} has an allocated piece without a valid B2B price.");
                }

                $pricingUnit = strtolower((string) ($item->pricing_unit ?? 'kg')) ?: 'kg';
                $subtotal = $pricingUnit === 'kg'
                    ? round($weight * $unitPrice, 2)
                    : round($unitPrice, 2);

                if ($subtotal <= 0) {
                    throw new RuntimeException("{$product->name} has an allocated piece with zero invoice value.");
                }

                $piece = $allocation->inventoryPiece;
                $lot = $piece?->inventoryLot ?: $allocation->inventoryLot;
                $variant = $allocation->variant ?: $lot?->productVariant;

                $rows[] = [
                    'request_item_id' => $item->id,
                    'allocation' => $allocation,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $allocation->product_variant_id ?: $lot?->product_variant_id,
                    'product_sell_unit_id' => $item->product_sell_unit_id,
                    'product_name' => $product->name,
                    'sku' => $variant?->sku ?? $product->sku,
                    'weight_kg' => $weight,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'pricing_unit' => $pricingUnit,
                    'sell_unit' => 'kg',
                    'gst_rate' => max(0, (float) ($product->gst_rate ?? 0)),
                    'snapshot' => [
                        'b2b_order_request_id' => $item->b2b_order_request_id,
                        'b2b_order_request_item_id' => $item->id,
                        'b2b_order_item_allocation_id' => $allocation->id,
                        'b2b_request_mode' => $item->request_mode,
                        'product_sell_unit_id' => $item->product_sell_unit_id,
                        'selected_piece' => [
                            'piece_id' => $allocation->inventory_piece_id,
                            'lot_id' => $allocation->inventory_lot_id,
                            'variant_id' => $allocation->product_variant_id ?: $lot?->product_variant_id,
                            'weight_kg' => $weight,
                            'lot_code' => $lot?->lot_code,
                        ],
                    ],
                ];
            }
        }

        return $rows;
    }

    protected function calculateGst(array $rows, string $state): array
    {
        $isMaharashtra = strcasecmp(trim($state), 'Maharashtra') === 0;
        $lineTaxMap = [];
        $taxTotal = 0.0;

        foreach ($rows as $index => $row) {
            $taxable = round((float) ($row['subtotal'] ?? 0), 2);
            $rate = max(0, (float) ($row['gst_rate'] ?? 0));
            $tax = round($taxable * ($rate / 100), 2);
            $taxTotal += $tax;

            if ($isMaharashtra) {
                $cgst = round($tax / 2, 2);
                $sgst = round($tax - $cgst, 2);
                $igst = null;
            } else {
                $cgst = null;
                $sgst = null;
                $igst = $tax;
            }

            $lineTaxMap[$index] = compact('tax', 'cgst', 'sgst', 'igst');
        }

        $taxTotal = round($taxTotal, 2);
        $cgstTotal = $isMaharashtra ? round($taxTotal / 2, 2) : null;

        return [
            'gst_type' => $isMaharashtra ? 'intra_state' : 'inter_state',
            'cgst_amount' => $cgstTotal,
            'sgst_amount' => $isMaharashtra ? round($taxTotal - (float) $cgstTotal, 2) : null,
            'igst_amount' => $isMaharashtra ? null : $taxTotal,
            'tax_total' => $taxTotal,
            'line_tax_map' => $lineTaxMap,
        ];
    }

    protected function copyAddressToOrder(Order $order, CustomerAddress $address): void
    {
        foreach (['shipping', 'billing'] as $type) {
            OrderAddress::query()->create([
                'order_id' => $order->id,
                'type' => $type,
                'full_name' => $address->full_name,
                'phone' => $address->phone,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'city' => $address->city,
                'state' => $address->state,
                'state_code' => $address->state_code,
                'country' => $address->country ?? 'India',
                'pincode' => $address->pincode,
                'gstin' => $address->gstin,
            ]);
        }
    }

    protected function markPieceSoldWithoutFurtherStockDeduction(B2BOrderItemAllocation $allocation, OrderItem $orderItem): void
    {
        if (! $allocation->inventory_piece_id) {
            return;
        }

        $piece = InventoryPiece::query()->lockForUpdate()->find($allocation->inventory_piece_id);
        if (! $piece) {
            return;
        }

        $piece->sold_order_item_id = $orderItem->id;
        $piece->status = 'sold';
        $piece->save();
    }

    protected function defaultAddressForUser(int $userId): ?CustomerAddress
    {
        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->orderByDesc('id')
            ->first();
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'B2B-' . now()->format('dmy') . '-' . Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    protected function generateInvoiceNumber(): string
    {
        do {
            $number = 'BA-' . now()->format('dmy') . '-' . Str::upper(Str::random(6));
        } while (Invoice::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $cacheKey = "{$table}.{$column}";

        if (! array_key_exists($cacheKey, self::$columnCache)) {
            self::$columnCache[$cacheKey] = Schema::hasColumn($table, $column);
        }

        return self::$columnCache[$cacheKey];
    }
}

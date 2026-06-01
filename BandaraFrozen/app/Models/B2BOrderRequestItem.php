<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2BOrderRequestItem extends Model
{
    public const MODE_PIECES = 'pieces';
    public const MODE_WEIGHT = 'weight';

    public const STATUS_PENDING_ALLOCATION = 'pending_allocation';
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_RELEASED = 'released';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'b2b_order_request_id',
        'product_id',
        'product_sell_unit_id',
        'request_mode',
        'requested_piece_count',
        'requested_weight_kg',
        'weight_tolerance_kg',
        'quoted_unit_price',
        'pricing_unit',
        'estimated_min_weight_kg',
        'estimated_max_weight_kg',
        'allocated_piece_count',
        'allocated_weight_kg',
        'allocated_subtotal',
        'allocated_by_id',
        'allocated_at',
        'finalized_order_item_id',
        'finalized_invoice_item_id',
        'finalized_at',
        'status',
        'customer_note',
        'admin_note',
    ];

    protected $casts = [
        'requested_piece_count' => 'integer',
        'requested_weight_kg' => 'decimal:3',
        'weight_tolerance_kg' => 'decimal:3',
        'quoted_unit_price' => 'decimal:2',
        'estimated_min_weight_kg' => 'decimal:3',
        'estimated_max_weight_kg' => 'decimal:3',
        'allocated_piece_count' => 'integer',
        'allocated_weight_kg' => 'decimal:3',
        'allocated_subtotal' => 'decimal:2',
        'allocated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(B2BOrderRequest::class, 'b2b_order_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sellUnit(): BelongsTo
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(B2BOrderItemAllocation::class, 'b2b_order_request_item_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_id');
    }

    public function finalizedOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'finalized_order_item_id');
    }

    public function finalizedInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'finalized_invoice_item_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'finalized_order_item_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'finalized_invoice_item_id');
    }

    public function getRequestSummaryAttribute(): string
    {
        if ($this->request_mode === self::MODE_WEIGHT) {
            $weight = $this->requested_weight_kg !== null
                ? rtrim(rtrim(number_format((float) $this->requested_weight_kg, 3), '0'), '.')
                : '—';

            return $weight . ' kg';
        }

        return number_format((int) ($this->requested_piece_count ?? 0)) . ' piece(s)';
    }

    public function getToleranceRangeAttribute(): ?string
    {
        if ($this->request_mode !== self::MODE_WEIGHT || $this->requested_weight_kg === null) {
            return null;
        }

        $target = (float) $this->requested_weight_kg;
        $tolerance = (float) ($this->weight_tolerance_kg ?? 0);
        $min = max($target - $tolerance, 0);
        $max = $target + $tolerance;

        return rtrim(rtrim(number_format($min, 3), '0'), '.')
            . ' kg – '
            . rtrim(rtrim(number_format($max, 3), '0'), '.')
            . ' kg';
    }
}

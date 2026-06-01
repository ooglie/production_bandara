<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class B2BOrderRequest extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING_ALLOCATION = 'pending_allocation';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_PARTIALLY_ALLOCATED = 'partially_allocated';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'request_number',
        'user_id',
        'status',
        'customer_note',
        'admin_note',
        'submitted_at',
        'reviewed_by_id',
        'reviewed_at',
        'allocated_by_id',
        'allocated_at',
        'finalized_order_id',
        'finalized_invoice_id',
        'finalized_by_id',
        'finalized_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'allocated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(B2BOrderRequestItem::class, 'b2b_order_request_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_id');
    }

    public function finalizedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'finalized_order_id');
    }

    public function finalizedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'finalized_invoice_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'finalized_order_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'finalized_invoice_id');
    }

    public function getOrderIdAttribute(): ?int
    {
        return $this->attributes['finalized_order_id'] ?? null;
    }

    public function getInvoiceIdAttribute(): ?int
    {
        return $this->attributes['finalized_invoice_id'] ?? null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_ALLOCATION => 'Pending allocation',
            self::STATUS_REVIEWING => 'Reviewing',
            self::STATUS_ALLOCATED => 'Allocated',
            self::STATUS_PARTIALLY_ALLOCATED => 'Partially allocated',
            self::STATUS_FINALIZED => 'Finalized',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REJECTED => 'Rejected',
            default => ucwords(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING_ALLOCATION,
            self::STATUS_REVIEWING,
            self::STATUS_PARTIALLY_ALLOCATED,
            self::STATUS_ALLOCATED,
        ]);
    }
}

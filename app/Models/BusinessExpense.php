<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessExpense extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'expense_number',
        'expense_date',
        'expense_category_id',
        'description',
        'payee',
        'taxable_amount',
        'gst_amount',
        'total_amount',
        'record_status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'due_date',
        'paid_date',
        'receipt_path',
        'receipt_original_name',
        'receipt_mime_type',
        'receipt_size',
        'notes',
        'recurring_expense_template_id',
        'generated_for_date',
        'posted_at',
        'posted_by_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'taxable_amount' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_date' => 'date',
            'generated_for_date' => 'date',
            'posted_at' => 'datetime',
            'receipt_size' => 'integer',
        ];
    }

    public static function recordStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_POSTED => 'Posted',
            self::STATUS_VOID => 'Void',
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_UNPAID => 'Unpaid',
            self::PAYMENT_PAID => 'Paid',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'bank_transfer' => 'Bank transfer',
            'upi' => 'UPI',
            'card' => 'Card',
            'cheque' => 'Cheque',
            'cash' => 'Cash',
            'payment_gateway' => 'Payment gateway',
            'other' => 'Other',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringExpenseTemplate::class, 'recurring_expense_template_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('record_status', self::STATUS_POSTED);
    }

    public function isDraft(): bool
    {
        return $this->record_status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->record_status === self::STATUS_POSTED;
    }
}

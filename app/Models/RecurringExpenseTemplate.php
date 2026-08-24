<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class RecurringExpenseTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_HALF_YEARLY = 'half_yearly';
    public const FREQUENCY_YEARLY = 'yearly';

    protected $fillable = [
        'expense_category_id',
        'description',
        'payee',
        'frequency',
        'expected_taxable_amount',
        'expected_gst_amount',
        'expected_total_amount',
        'start_date',
        'end_date',
        'next_due_date',
        'default_payment_method',
        'notes',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'expected_taxable_amount' => 'decimal:2',
            'expected_gst_amount' => 'decimal:2',
            'expected_total_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public static function frequencies(): array
    {
        return [
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_HALF_YEARLY => 'Half-yearly',
            self::FREQUENCY_YEARLY => 'Yearly',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function generatedExpenses(): HasMany
    {
        return $this->hasMany(BusinessExpense::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function scopeDueOnOrBefore(Builder $query, CarbonInterface|string $date): Builder
    {
        $date = CarbonImmutable::parse($date)->toDateString();

        return $query
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', $date)
            ->where(function (Builder $query): void {
                $query->whereNull('end_date')
                    ->orWhereColumn('next_due_date', '<=', 'end_date');
            });
    }

    public function nextDateAfter(CarbonInterface|string $date): CarbonImmutable
    {
        $date = CarbonImmutable::parse($date)->startOfDay();

        return match ($this->frequency) {
            self::FREQUENCY_MONTHLY => $date->addMonthNoOverflow(),
            self::FREQUENCY_QUARTERLY => $date->addMonthsNoOverflow(3),
            self::FREQUENCY_HALF_YEARLY => $date->addMonthsNoOverflow(6),
            self::FREQUENCY_YEARLY => $date->addYearNoOverflow(),
            default => throw new InvalidArgumentException("Unsupported recurring expense frequency [{$this->frequency}]."),
        };
    }
}

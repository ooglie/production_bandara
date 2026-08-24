<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryEntry extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_HELD = 'held';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'salary_profile_id',
        'staff_name',
        'salary_month',
        'basic_salary',
        'additions',
        'deductions',
        'net_payable',
        'payment_status',
        'payment_date',
        'payment_method',
        'payment_reference',
        'notes',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'salary_month' => 'date',
            'basic_salary' => 'decimal:2',
            'additions' => 'decimal:2',
            'deductions' => 'decimal:2',
            'net_payable' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public static function paymentStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_HELD => 'On hold',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function editablePaymentStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_HELD => 'On hold',
        ];
    }

    public function isLockedForEditing(): bool
    {
        return in_array($this->payment_status, [self::STATUS_PAID, self::STATUS_CANCELLED], true);
    }

    public static function paymentMethods(): array
    {
        return BusinessExpense::paymentMethods();
    }

    public static function calculateNet(float|string $basic, float|string $additions, float|string $deductions): string
    {
        $net = round((float) $basic + (float) $additions - (float) $deductions, 2);

        return number_format(max(0, $net), 2, '.', '');
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salaryProfile(): BelongsTo
    {
        return $this->belongsTo(SalaryProfile::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}

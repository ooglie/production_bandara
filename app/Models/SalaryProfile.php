<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryProfile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'monthly_salary',
        'effective_from',
        'effective_to',
        'payment_day',
        'notes',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'payment_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salaryEntries(): HasMany
    {
        return $this->hasMany(SalaryEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function scopeEffectiveDuring(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->where(function (Builder $query) use ($start): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $start->toDateString());
            });
    }

    public function scopeEffectiveOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date->toDateString());
            });
    }
}

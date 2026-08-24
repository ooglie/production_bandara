<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\B2BApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class B2BApplication extends Model
{
    use HasFactory;

    protected $table = 'b2b_applications';

    protected $fillable = [
        'application_number', 'user_id', 'contact_first_name', 'contact_last_name', 'email', 'phone',
        'whatsapp', 'preferred_contact_method', 'legal_business_name', 'trading_name', 'business_type',
        'gst_registered', 'gstin', 'pan', 'fssai_number', 'website', 'address_line_1', 'address_line_2',
        'state_id', 'city_id', 'state_name', 'city_name', 'postal_code', 'interested_categories',
        'estimated_monthly_purchase', 'purchase_frequency', 'requirements_message', 'terms_accepted_at',
        'status', 'assigned_to', 'reviewed_by', 'approved_by', 'rejected_by', 'customer_message',
        'approved_price_group_id', 'pay_later_enabled', 'credit_limit', 'payment_terms_days',
        'minimum_order_value', 'delivery_arrangement', 'approved_account_manager_id', 'submitted_at',
        'reviewed_at', 'information_requested_at', 'resubmitted_at', 'approved_at', 'rejected_at',
        'withdrawn_at', 'last_customer_edit_at', 'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'status' => B2BApplicationStatus::class,
            'gst_registered' => 'boolean',
            'interested_categories' => 'array',
            'pay_later_enabled' => 'boolean',
            'credit_limit' => 'decimal:2',
            'minimum_order_value' => 'decimal:2',
            'terms_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'information_requested_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'last_customer_edit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            if (! $application->application_number) {
                $application->application_number = 'B2B-APP-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_account_manager_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(B2BApplicationHistory::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(B2BCustomerProfile::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            static fn (B2BApplicationStatus $status): string => $status->value,
            [B2BApplicationStatus::Draft, B2BApplicationStatus::Submitted, B2BApplicationStatus::UnderReview, B2BApplicationStatus::MoreInformationRequired],
        ));
    }

    public function recordHistory(
        string $event,
        ?User $actor = null,
        ?B2BApplicationStatus $from = null,
        ?B2BApplicationStatus $to = null,
        ?string $message = null,
        string $visibility = 'internal',
        array $metadata = [],
    ): B2BApplicationHistory {
        return $this->histories()->create([
            'actor_user_id' => $actor?->getKey(),
            'actor_label' => $actor ? trim((string) ($actor->name ?? $actor->email ?? 'User')) : 'System',
            'event' => $event,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'visibility' => $visibility,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }
}

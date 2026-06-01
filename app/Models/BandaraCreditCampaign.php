<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BandaraCreditCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'type',
        'starts_at',
        'ends_at',
        'min_order_amount',
        'eligible_tiers',
        'multiplier',
        'fixed_bonus_points',
        'max_bonus_per_order',
        'max_bonus_per_customer',
        'budget_points',
        'used_budget_points',
        'counts_toward_tier',
        'stacking_rule',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'min_order_amount' => 'decimal:2',
        'eligible_tiers' => 'array',
        'multiplier' => 'decimal:3',
        'fixed_bonus_points' => 'integer',
        'max_bonus_per_order' => 'integer',
        'max_bonus_per_customer' => 'integer',
        'budget_points' => 'integer',
        'used_budget_points' => 'integer',
        'counts_toward_tier' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (BandaraCreditCampaign $campaign) {
            if (! $campaign->slug) {
                $campaign->slug = Str::slug($campaign->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bandara_credit_campaign_products', 'campaign_id', 'product_id')
            ->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'bandara_credit_campaign_categories', 'campaign_id', 'category_id')
            ->withTimestamps();
    }

    public function isActiveNow(?Carbon $at = null): bool
    {
        $at ??= now();

        if ($this->status !== 'active') {
            return false;
        }

        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($at)) {
            return false;
        }

        if ($this->budget_points !== null && $this->used_budget_points >= $this->budget_points) {
            return false;
        }

        return true;
    }
}

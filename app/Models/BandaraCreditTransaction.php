<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BandaraCreditTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'campaign_id',
        'amount',
        'tier_points',
        'type',
        'status',
        'idempotency_key',
        'meta',
        'note',
        'expires_at',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'tier_points' => 'integer',
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BandaraCreditCampaign::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

}
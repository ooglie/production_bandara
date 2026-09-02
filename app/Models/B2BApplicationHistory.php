<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BApplicationHistory extends Model
{
    public $timestamps = false;

    protected $table = 'b2b_application_histories';

    protected $fillable = [
        'b2b_application_id', 'actor_user_id', 'actor_label', 'event', 'from_status', 'to_status',
        'visibility', 'message', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(B2BApplication::class, 'b2b_application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'impersonated_user_id',
        'type',
        'started_at',
        'ended_at',
        'ended_reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function impersonatedUser()
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }
}

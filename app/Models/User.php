<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\CustomerAddress;
use App\Models\B2BCustomerTerm;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'gst_number',
        'fssai_number',
        'customer_type',
        'is_active',
        'email_verified_at',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'gst_number' => 'string',
            'fssai_number' => 'string',
        ];
    }

    public function customerAddresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function carts()
    {
        return $this->hasMany(\App\Models\Cart::class);
    }

    public function activeCart()
    {
        // latest cart acts as “active” cart
        return $this->hasOne(\App\Models\Cart::class)->latestOfMany();
    }

    public function b2bCustomerTerm()
    {
        return $this->hasOne(B2BCustomerTerm::class);
    }

    public function b2bTerms()
    {
        return $this->hasOne(B2BCustomerTerm::class, 'user_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatarPath = trim((string) ($this->avatar_path ?? ''));

        if ($avatarPath === '') {
            return null;
        }

        if (Str::startsWith($avatarPath, ['http://', 'https://', '//', 'data:'])) {
            return $avatarPath;
        }

        $normalized = ltrim($avatarPath, '/');

        return Str::startsWith($normalized, 'storage/')
            ? '/' . $normalized
            : '/storage/' . $normalized;
    }

    public function getInitialsAttribute(): string
    {
        $nameParts = preg_split('/\s+/', trim((string) ($this->name ?? '')));

        $initials = collect($nameParts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

}

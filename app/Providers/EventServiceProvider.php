<?php

namespace App\Providers;

use App\Listeners\MergeGuestCartOnLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        // Keep email verification working (recommended)
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Merge guest cart into user cart on login
        Login::class => [
            MergeGuestCartOnLogin::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    /**
     * Disable auto event discovery (explicit mapping is clearer).
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

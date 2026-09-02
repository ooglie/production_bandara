<?php

declare(strict_types=1);

namespace App\Services\B2B;

use App\Models\B2BApplication;
use App\Models\User;
use App\Notifications\B2BApplicationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Throwable;

final class B2BApplicationNotifier
{
    public function customer(B2BApplication $application, string $title, string $message): void
    {
        try {
            $application->loadMissing('user');
            $url = Route::has('account.business-application.show')
                ? route('account.business-application.show')
                : url('/account/business-application');
            $application->user?->notify(new B2BApplicationNotification($title, $message, $url));
        } catch (Throwable $exception) {
            Log::warning('B2B application customer notification failed.', [
                'application_id' => $application->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    public function admins(B2BApplication $application, string $title, string $message, ?User $except = null): void
    {
        try {
            $roles = (array) config('b2b_application.admin_roles', ['Admin', 'Manager']);
            $query = User::query();

            if (method_exists($query->getModel(), 'scopeRole')) {
                $query->role($roles);
            } elseif (method_exists($query->getModel(), 'roles')) {
                $query->whereHas('roles', static fn ($roleQuery) => $roleQuery->whereIn('name', $roles));
            } else {
                return;
            }

            if ($except) {
                $query->where($query->getModel()->getQualifiedKeyName(), '!=', $except->getKey());
            }

            $recipients = $query->get();

            if ($recipients->isEmpty()) {
                return;
            }

            $url = Route::has('admin.b2b-applications.show')
                ? route('admin.b2b-applications.show', $application)
                : url('/admin/b2b-applications/'.$application->getKey());
            Notification::send($recipients, new B2BApplicationNotification($title, $message, $url));
        } catch (Throwable $exception) {
            Log::warning('B2B application admin notification failed.', [
                'application_id' => $application->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}

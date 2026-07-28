<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminRoutePermission
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasRole('Admin')) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        abort_unless(Str::startsWith($routeName, 'admin.'), 403);

        if ($routeName === 'admin.dashboard') {
            abort_unless($user->hasRole('Manager'), 403);
            return $next($request);
        }

        $permission = $this->requiredPermission($request, $routeName);

        if ($permission === null) {
            Log::warning('Back-office route denied because no permission mapping exists.', [
                'route' => $routeName,
                'user_id' => $user->id,
            ]);

            abort(403);
        }

        abort_unless($user->can($permission), 403);

        return $next($request);
    }

    private function requiredPermission(Request $request, string $routeName): ?string
    {
        if (Str::startsWith($routeName, ['admin.roles.', 'admin.users.'])) {
            return 'manage users';
        }

        if (Str::startsWith($routeName, 'admin.reports.')) {
            return 'view reports';
        }

        if (Str::startsWith($routeName, 'admin.delivery.')) {
            return 'manage settings';
        }

        if (Str::startsWith($routeName, [
            'admin.announcements.',
            'admin.home-sections.',
            'admin.pages.',
            'admin.product-collections.',
            'admin.recipes.',
        ])) {
            return $this->isManagementRoute($request, $routeName) ? 'manage content' : 'view content';
        }

        if (Str::startsWith($routeName, [
            'admin.newsletter-subscribers.',
            'admin.newsletter-campaigns.',
        ])) {
            return $this->isManagementRoute($request, $routeName) ? 'manage marketing' : 'view marketing';
        }

        if (Str::startsWith($routeName, [
            'admin.products.',
            'admin.variants.',
            'admin.categories.',
            'admin.attributes.',
            'admin.values.',
            'admin.variant-option-values.',
            'admin.images.',
            'admin.hsn-codes.',
        ])) {
            return $this->isManagementRoute($request, $routeName) ? 'manage products' : 'view products';
        }

        if (Str::startsWith($routeName, 'admin.vendors.')) {
            return $this->isManagementRoute($request, $routeName) ? 'manage vendors' : 'view vendors';
        }

        if (Str::startsWith($routeName, 'admin.vendor-invoices.')) {
            if (Str::endsWith($routeName, ['.create', '.store'])) {
                return 'create vendor invoice';
            }

            return 'view vendors';
        }

        if (Str::startsWith($routeName, 'admin.vendor-payments.')) {
            return 'manage vendor payments';
        }

        if (Str::startsWith($routeName, 'admin.coupons.')) {
            return $this->isManagementRoute($request, $routeName) ? 'manage coupons' : 'view coupons';
        }

        if (Str::startsWith($routeName, 'admin.invoice-payment-submissions.')) {
            return Str::endsWith($routeName, ['.approve', '.reject'])
                ? 'manage payments'
                : 'view payments';
        }

        if (Str::startsWith($routeName, 'admin.invoices.')) {
            return $this->isManagementRoute($request, $routeName) ? 'manage invoices' : 'view invoices';
        }

        if (Str::startsWith($routeName, 'admin.payments.')) {
            return $this->isManagementRoute($request, $routeName) ? 'manage payments' : 'view payments';
        }

        if (Str::startsWith($routeName, [
            'admin.inventory.',
            'admin.production.',
            'admin.stores.',
        ])) {
            return $this->isManagementRoute($request, $routeName) ? 'manage stores' : 'view stores';
        }

        if (Str::startsWith($routeName, 'admin.orders.')) {
            return $this->isManagementRoute($request, $routeName) ? 'manage orders' : 'view orders';
        }

        if (Str::startsWith($routeName, [
            'admin.customers.',
            'admin.b2b.',
        ])) {
            return $this->isManagementRoute($request, $routeName) ? 'manage customers' : 'view customers';
        }

        if (Str::startsWith($routeName, 'admin.rewards.')) {
            return $this->isManagementRoute($request, $routeName) ? 'manage rewards' : 'view rewards';
        }

        if ($routeName === 'admin.bandara-credit.preview') {
            return 'view rewards';
        }

        if (Str::startsWith($routeName, [
            'admin.ticket-categories.',
            'admin.ticket-tags.',
        ])) {
            return $this->isManagementRoute($request, $routeName) ? 'manage tickets' : 'view tickets';
        }

        return null;
    }

    private function isManagementRoute(Request $request, string $routeName): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return true;
        }

        return Str::endsWith($routeName, [
            '.create',
            '.edit',
            '.payment-form',
        ]);
    }
}

<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Compatibility wrapper for older call sites.
 *
 * BandaraCreditService is the single production source of truth for earn,
 * queue, cancellation/reversal, idempotency, feature flags, and wallet sync.
 */
class BandaraCreditLedgerService
{
    public function __construct(protected BandaraCreditService $bandaraCreditService)
    {
    }

    public function queueOrderReward(Model $order): array
    {
        return $this->bandaraCreditService->queueEarnForOrder($order, respectAutoPost: true);
    }

    public function postOrderReward(Model $order): array
    {
        // Lifecycle posting must respect BANDARA_CREDIT_AUTO_POST_ENABLED.
        // Direct BandaraCreditService::postEarnForSuccessfulOrder() remains
        // the manual/CLI path and intentionally ignores that flag by default.
        return $this->bandaraCreditService->postEarnForSuccessfulOrder($order, respectAutoPost: true);
    }

    public function cancelOrderReward(Model $order): array
    {
        return $this->bandaraCreditService->cancelEarnForOrder($order, respectAutoPost: false);
    }

    public function syncOrderLifecycle(Model $order, ?string $previousStatus = null): array
    {
        return $this->bandaraCreditService->syncOrderLifecycle($order, $previousStatus);
    }
}

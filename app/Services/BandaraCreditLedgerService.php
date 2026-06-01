<?php

namespace App\Services;

use App\Models\Order;

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

    public function queueOrderReward(Order $order): array
    {
        return $this->bandaraCreditService->queueEarnForOrder($order, respectAutoPost: true);
    }

    public function postOrderReward(Order $order): array
    {
        return $this->bandaraCreditService->postEarnForSuccessfulOrder($order, respectAutoPost: true);
    }

    public function cancelOrderReward(Order $order): array
    {
        return $this->bandaraCreditService->cancelEarnForOrder($order, respectAutoPost: false);
    }

    public function syncOrderLifecycle(Order $order, ?string $previousStatus = null): array
    {
        return $this->bandaraCreditService->syncOrderLifecycle($order, $previousStatus);
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDeliveryEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderDeliveryService
{
    public function assign(Order $order, ?User $agent, User $actor, ?string $note = null): Order
    {
        if ($order->status === 'delivered') {
            throw ValidationException::withMessages([
                'delivery_agent_id' => 'Delivered orders cannot be reassigned.',
            ]);
        }

        if ($agent && method_exists($agent, 'hasRole') && ! $agent->hasRole('DeliveryAgent')) {
            throw ValidationException::withMessages([
                'delivery_agent_id' => 'Selected user is not a delivery agent.',
            ]);
        }

        return DB::transaction(function () use ($order, $agent, $actor, $note) {
            $order->refresh();

            $oldStatus = (string) ($order->delivery_status ?: 'pending');
            $newStatus = $agent ? ($oldStatus === 'out_for_delivery' ? 'out_for_delivery' : 'assigned') : 'pending';

            $order->forceFill([
                'delivery_agent_id' => $agent?->id,
                'delivery_status' => $newStatus,
                'delivery_note' => $note ?: $order->delivery_note,
                'delivery_failed_at' => $newStatus === 'pending' ? null : $order->delivery_failed_at,
                'delivery_failure_reason' => $newStatus === 'pending' ? null : $order->delivery_failure_reason,
            ])->save();

            $this->recordEvent($order, $actor, $agent ? 'assigned' : 'unassigned', $oldStatus, $newStatus, $note, [
                'delivery_agent_id' => $agent?->id,
                'delivery_agent_name' => $agent?->name,
            ]);

            return $order->fresh(['deliveryAgent', 'deliveryEvents.user']);
        });
    }

    public function markOutForDelivery(Order $order, User $actor, ?string $note = null): Order
    {
        $this->assertActorCanUpdateAssignedOrder($order, $actor);

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'order' => 'Cancelled orders cannot be marked out for delivery.',
            ]);
        }

        if ($order->status === 'delivered') {
            return $order;
        }

        return DB::transaction(function () use ($order, $actor, $note) {
            $order->refresh();

            $oldDeliveryStatus = (string) ($order->delivery_status ?: 'assigned');
            $oldOrderStatus = (string) ($order->status ?: 'processing');
            $now = now();

            $order->forceFill([
                'status' => $oldOrderStatus === 'processing' ? 'shipped' : $oldOrderStatus,
                'delivery_status' => 'out_for_delivery',
                'out_for_delivery_at' => $order->out_for_delivery_at ?: $now,
                'shipped_at' => $order->shipped_at ?: $now,
                'delivery_note' => $note ?: $order->delivery_note,
                'delivery_failed_at' => null,
                'delivery_failure_reason' => null,
            ])->save();

            $this->recordEvent($order, $actor, 'out_for_delivery', $oldDeliveryStatus, 'out_for_delivery', $note, [
                'old_order_status' => $oldOrderStatus,
                'new_order_status' => $order->status,
            ]);

            return $order->fresh(['deliveryAgent', 'deliveryEvents.user']);
        });
    }

    public function markDelivered(Order $order, User $actor, ?string $note = null): Order
    {
        $this->assertActorCanUpdateAssignedOrder($order, $actor);

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'order' => 'Cancelled orders cannot be marked delivered.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $note) {
            $order->refresh();

            $previousOrderStatus = (string) ($order->status ?: 'processing');
            $oldDeliveryStatus = (string) ($order->delivery_status ?: 'assigned');
            $now = now();

            $order->forceFill([
                'status' => 'delivered',
                'delivery_status' => 'delivered',
                'delivered_at' => $order->delivered_at ?: $now,
                'delivered_by_id' => $actor->id,
                'shipped_at' => $order->shipped_at ?: $now,
                'out_for_delivery_at' => $order->out_for_delivery_at ?: $now,
                'delivery_note' => $note ?: $order->delivery_note,
                'delivery_failed_at' => null,
                'delivery_failure_reason' => null,
            ])->save();

            $this->recordEvent($order, $actor, 'delivered', $oldDeliveryStatus, 'delivered', $note, [
                'old_order_status' => $previousOrderStatus,
                'new_order_status' => 'delivered',
            ]);

            $this->syncBandaraCreditForDeliveredOrder($order->fresh(), $previousOrderStatus);

            return $order->fresh(['deliveryAgent', 'deliveredBy', 'deliveryEvents.user']);
        });
    }

    public function markFailed(Order $order, User $actor, string $reason, ?string $note = null): Order
    {
        $this->assertActorCanUpdateAssignedOrder($order, $actor);

        if ($order->status === 'delivered') {
            throw ValidationException::withMessages([
                'order' => 'Delivered orders cannot be marked failed.',
            ]);
        }

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'order' => 'Cancelled orders cannot be marked failed.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $reason, $note) {
            $order->refresh();

            $oldStatus = (string) ($order->delivery_status ?: 'assigned');

            $order->forceFill([
                'delivery_status' => 'failed',
                'delivery_failed_at' => now(),
                'delivery_failure_reason' => $reason,
                'delivery_note' => $note ?: $order->delivery_note,
            ])->save();

            $this->recordEvent($order, $actor, 'failed', $oldStatus, 'failed', $note, [
                'reason' => $reason,
            ]);

            return $order->fresh(['deliveryAgent', 'deliveryEvents.user']);
        });
    }

    public function recordEvent(Order $order, ?User $actor, string $eventType, ?string $oldStatus = null, ?string $newStatus = null, ?string $note = null, array $meta = []): OrderDeliveryEvent
    {
        return OrderDeliveryEvent::create([
            'order_id' => $order->id,
            'user_id' => $actor?->id,
            'event_type' => $eventType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'meta' => $meta ?: null,
        ]);
    }

    private function assertActorCanUpdateAssignedOrder(Order $order, User $actor): void
    {
        $isBackoffice = method_exists($actor, 'hasAnyRole') && $actor->hasAnyRole(['Admin', 'Manager', 'Stores']);

        if ($isBackoffice) {
            return;
        }

        if (method_exists($actor, 'hasRole') && $actor->hasRole('DeliveryAgent') && (int) $order->delivery_agent_id === (int) $actor->id) {
            return;
        }

        throw ValidationException::withMessages([
            'order' => 'You can update only deliveries assigned to you.',
        ]);
    }

    private function syncBandaraCreditForDeliveredOrder(Order $order, string $previousOrderStatus): void
    {
        if ($previousOrderStatus === 'delivered' || ! $order->user_id) {
            return;
        }

        try {
            app(BandaraCreditService::class)->syncOrderLifecycle($order, $previousOrderStatus);
        } catch (\Throwable $e) {
            Log::error('Bandara credit sync failed from delivery workflow', [
                'order_id' => $order->id,
                'previous_status' => $previousOrderStatus,
                'new_status' => $order->status,
                'error' => $e->getMessage(),
            ]);

            report($e);
        }
    }
}

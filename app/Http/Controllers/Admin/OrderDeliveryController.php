<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderDeliveryService;
use Illuminate\Http\Request;

class OrderDeliveryController extends Controller
{
    public function assign(Request $request, Order $order, OrderDeliveryService $deliveryService)
    {
        $data = $request->validate([
            'delivery_agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $agent = null;
        if (! empty($data['delivery_agent_id'])) {
            $agent = User::findOrFail((int) $data['delivery_agent_id']);
        }

        $deliveryService->assign(
            order: $order,
            agent: $agent,
            actor: $request->user(),
            note: $data['delivery_note'] ?? null,
        );

        return back()->with('status', $agent
            ? 'Delivery assigned to ' . $agent->name . '.'
            : 'Delivery assignment cleared.'
        );
    }
}

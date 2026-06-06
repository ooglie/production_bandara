<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderDeliveryService;
use Illuminate\Http\Request;

class DeliveryDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = (string) $request->input('status', 'active');

        $query = Order::query()
            ->where('delivery_agent_id', $user->id)
            ->with(['user', 'shippingAddress', 'invoice'])
            ->orderByRaw("FIELD(delivery_status, 'out_for_delivery', 'assigned', 'failed', 'delivered', 'pending')")
            ->orderByRaw('COALESCE(placed_at, created_at) DESC')
            ->orderByDesc('id');

        if ($filter === 'delivered') {
            $query->where('delivery_status', 'delivered');
        } elseif ($filter === 'failed') {
            $query->where('delivery_status', 'failed');
        } else {
            $query->whereIn('delivery_status', ['assigned', 'out_for_delivery', 'failed'])
                ->whereNotIn('status', ['delivered', 'cancelled']);
        }

        $orders = $query->paginate(20)->withQueryString();

        $stats = [
            'active' => Order::where('delivery_agent_id', $user->id)
                ->whereIn('delivery_status', ['assigned', 'out_for_delivery', 'failed'])
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->count(),
            'delivered_today' => Order::where('delivery_agent_id', $user->id)
                ->where('delivery_status', 'delivered')
                ->whereDate('delivered_at', now()->toDateString())
                ->count(),
        ];

        return view('delivery.index', compact('orders', 'filter', 'stats'));
    }

    public function show(Request $request, Order $order)
    {
        $this->ensureAssignedToCurrentAgent($request, $order);

        $order->load(['user', 'shippingAddress', 'billingAddress', 'items', 'invoice', 'deliveryEvents.user']);

        return view('delivery.show', compact('order'));
    }

    public function markOutForDelivery(Request $request, Order $order, OrderDeliveryService $deliveryService)
    {
        $this->ensureAssignedToCurrentAgent($request, $order);

        $data = $request->validate([
            'delivery_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryService->markOutForDelivery($order, $request->user(), $data['delivery_note'] ?? null);

        return back()->with('status', 'Marked out for delivery.');
    }

    public function markDelivered(Request $request, Order $order, OrderDeliveryService $deliveryService)
    {
        $this->ensureAssignedToCurrentAgent($request, $order);

        $data = $request->validate([
            'delivery_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryService->markDelivered($order, $request->user(), $data['delivery_note'] ?? null);

        return redirect()->route('delivery.index')->with('status', 'Delivery marked as completed.');
    }

    public function markFailed(Request $request, Order $order, OrderDeliveryService $deliveryService)
    {
        $this->ensureAssignedToCurrentAgent($request, $order);

        $data = $request->validate([
            'delivery_failure_reason' => ['required', 'string', 'max:100'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryService->markFailed(
            order: $order,
            actor: $request->user(),
            reason: $data['delivery_failure_reason'],
            note: $data['delivery_note'] ?? null,
        );

        return back()->with('status', 'Delivery marked as could not deliver.');
    }

    private function ensureAssignedToCurrentAgent(Request $request, Order $order): void
    {
        abort_unless((int) $order->delivery_agent_id === (int) $request->user()->id, 404);
    }
}

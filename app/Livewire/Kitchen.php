<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Enums\ServiceMode;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class Kitchen extends Component
{
    public array $knownOrderIds = [];

    public function mount(): void
    {
        $this->knownOrderIds = Order::where('service_mode', ServiceMode::KITCHEN)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Listen to Echo OrderChanged broadcasts.
     */
    public function getListeners(): array
    {
        return [
            "echo-private:orders.operations,OrderChanged" => '$refresh',
        ];
    }

    /**
     * Get active kitchen orders (NEW or PREPARING), oldest first.
     */
    public function getOrdersProperty()
    {
        return Order::where('service_mode', ServiceMode::KITCHEN)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->with(['items', 'returnablePlans.returnableType'])
            ->orderBy('ordered_at', 'asc')
            ->get();
    }

    /**
     * Polling fallback method to detect new orders and dispatch operational sound events.
     * Uses named arguments for Livewire 4.
     */
    public function refreshOperationalOrders(): void
    {
        $user = auth()->user();
        if (!$user) return;

        /** @var \App\Services\OperationalNotificationPreferenceService $prefService */
        $prefService = app(\App\Services\OperationalNotificationPreferenceService::class);
        $shouldReceive = $prefService->shouldReceiveInApp($user, 'ORDER_CREATED');

        $currentOrders = Order::where('service_mode', ServiceMode::KITCHEN)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->with(['items'])
            ->orderBy('ordered_at', 'asc')
            ->get();

        $currentIds = $currentOrders->pluck('id')->toArray();
        $newIds = array_diff($currentIds, $this->knownOrderIds);

        if (!empty($newIds) && $shouldReceive) {
            $shouldSound = $prefService->shouldPlaySound($user, 'ORDER_CREATED');
            $shouldBrowser = $prefService->shouldSendBrowser($user, 'ORDER_CREATED');

            foreach ($currentOrders as $order) {
                if (in_array($order->id, $newIds) && $order->status === OrderStatus::NEW) {
                    $itemsSummary = $order->items->map(fn($i) => "{$i->quantity}x {$i->product_name}")->implode(', ');
                    $this->dispatch(
                        'operational-fallback-event',
                        orderId: (string) $order->id,
                        orderNumber: ltrim($order->number, '#'),
                        action: 'ORDER_CREATED',
                        soundType: 'kitchen',
                        targetUserIds: [(int) $user->id],
                        soundUserIds: $shouldSound ? [(int) $user->id] : [],
                        browserUserIds: $shouldBrowser ? [(int) $user->id] : [],
                        originUserId: null,
                        customerName: $order->customer_name_snapshot ?? 'Venta Mostrador',
                        itemsSummary: $itemsSummary
                    );
                }
            }
        }

        $this->knownOrderIds = $currentIds;
    }

    /**
     * Transition NEW -> PREPARING.
     */
    public function startPreparingOrder(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El pedido no existe.');
            return;
        }

        try {
            $orderService->startPreparing($order, auth()->user());
            $this->dispatch('notify-toast', type: 'info', title: 'En Preparación', message: "Pedido #" . ltrim($order->number, '#') . " en preparación.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo iniciar preparación del pedido.');
        }
    }

    /**
     * Transition PREPARING -> READY.
     */
    public function markOrderReady(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El pedido no existe.');
            return;
        }

        try {
            $orderService->markReady($order, auth()->user());
            $this->dispatch('notify-toast', type: 'success', title: 'Pedido Listo', message: "Pedido #" . ltrim($order->number, '#') . " listo para entrega.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo marcar el pedido como listo.');
        }
    }

    public function render()
    {
        return view('livewire.kitchen', [
            'orders' => $this->orders,
        ])->title('Cocina');
    }
}

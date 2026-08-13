<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class Kitchen extends Component
{
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
        return Order::whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->with(['items'])
            ->orderBy('ordered_at', 'asc')
            ->get();
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
            $this->dispatch('notify-toast', type: 'info', title: 'En Preparación', message: "Pedido #{$order->number} en preparación.");
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
            $this->dispatch('notify-toast', type: 'success', title: 'Pedido Listo', message: "Pedido #{$order->number} listo para entrega.");
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

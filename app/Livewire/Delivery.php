<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class Delivery extends Component
{
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

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
     * Get all ready orders (status READY) waiting for a delivery driver.
     */
    public function getReadyOrdersProperty()
    {
        return Order::where('status', OrderStatus::READY)
            ->with(['items'])
            ->orderBy('ready_at', 'asc')
            ->get();
    }

    /**
     * Get deliveries claimed by the authenticated user.
     */
    public function getMyDeliveriesProperty()
    {
        return Order::where('status', OrderStatus::DELIVERING)
            ->where('delivery_user_id', auth()->id())
            ->with(['items'])
            ->orderBy('delivering_at', 'asc')
            ->get();
    }

    /**
     * Claim order for delivery (READY -> DELIVERING).
     */
    public function claimOrder(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->errorMessage = 'El pedido no existe.';
            return;
        }

        try {
            $orderService->claimForDelivery($order, auth()->user());
            $this->successMessage = "Pedido {$order->number} asignado a tus entregas.";
            $this->errorMessage = null;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Mark claimed order as delivered (DELIVERING -> DELIVERED).
     */
    public function markOrderDelivered(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->errorMessage = 'El pedido no existe.';
            return;
        }

        try {
            $orderService->markDelivered($order, auth()->user());
            $this->successMessage = "Pedido {$order->number} marcado como entregado.";
            $this->errorMessage = null;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.delivery', [
            'readyOrders' => $this->readyOrders,
            'myDeliveries' => $this->myDeliveries,
        ])->title('Reparto');
    }
}

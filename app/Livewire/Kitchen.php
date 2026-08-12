<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class Kitchen extends Component
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
            $this->errorMessage = 'El pedido no existe.';
            return;
        }

        try {
            $orderService->startPreparing($order, auth()->user());
            $this->successMessage = "Pedido {$order->number} en preparación.";
            $this->errorMessage = null;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Transition PREPARING -> READY.
     */
    public function markOrderReady(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->errorMessage = 'El pedido no existe.';
            return;
        }

        try {
            $orderService->markReady($order, auth()->user());
            $this->successMessage = "Pedido {$order->number} listo para entrega.";
            $this->errorMessage = null;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.kitchen', [
            'orders' => $this->orders,
        ])->title('Cocina');
    }
}

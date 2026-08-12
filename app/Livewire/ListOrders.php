<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class ListOrders extends Component
{
    public string $statusFilter = 'TODOS';
    public ?int $selectedOrderId = null;
    public string $cancellationNotes = '';

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
     * Get orders for today filtered by status.
     */
    public function getOrdersProperty()
    {
        $query = Order::whereDate('ordered_at', now()->toDateString())
            ->with(['customer', 'creator', 'deliveryUser', 'items'])
            ->orderBy('ordered_at', 'desc');

        if ($this->statusFilter !== 'TODOS') {
            $query->where('status', $this->statusFilter);
        }

        return $query->get();
    }

    public function changeFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    /**
     * Select order to view details.
     */
    public function viewOrder(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->cancellationNotes = '';
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function closeModal(): void
    {
        $this->selectedOrderId = null;
        $this->cancellationNotes = '';
    }

    /**
     * Cancel the currently selected order.
     */
    public function cancelSelectedOrder(OrderService $orderService): void
    {
        if (!$this->selectedOrderId) {
            return;
        }

        $order = Order::find($this->selectedOrderId);
        if (!$order) {
            $this->closeModal();
            return;
        }

        try {
            $orderService->cancelOrder($order, auth()->user(), $this->cancellationNotes ?: 'Cancelado desde panel.');
            $this->successMessage = "Pedido {$order->number} cancelado con éxito.";
            $this->closeModal();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $selectedOrder = $this->selectedOrderId 
            ? Order::with(['customer', 'creator', 'deliveryUser', 'items', 'histories.user'])->find($this->selectedOrderId) 
            : null;

        return view('livewire.list-orders', [
            'orders' => $this->orders,
            'selectedOrder' => $selectedOrder,
            'statuses' => OrderStatus::labels(),
        ])->title('Lista de Pedidos');
    }
}
